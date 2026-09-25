<?php

namespace Tests\Feature;

use App\Enums\ConditionComponent;
use App\Enums\EquipmentItem;
use App\Enums\ReceptionStatus;
use App\Mail\VehicleMovementMail;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleMovementMailTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_SIGNATURE_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function common(array $overrides = []): array
    {
        return array_merge([
            'location' => 'Oficina central',
            'fuel_level' => 'half',
            'fuel_type' => 'gasoline_super',
            'washed' => '1',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'has_anomaly' => '0',
            'signature_data' => self::TINY_SIGNATURE_PNG,
            'equipment_checks' => collect(EquipmentItem::cases())->mapWithKeys(fn ($i) => [$i->value => '1'])->all(),
            'condition_items' => collect(ConditionComponent::cases())->mapWithKeys(fn ($i) => [$i->value => 'ok'])->all(),
        ], $overrides);
    }

    private function receptionPayload(Vehicle $vehicle, array $overrides = []): array
    {
        return $this->common(array_merge([
            'vehicle_id' => $vehicle->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:30',
            'initial_mileage' => 1000,
            'documentation' => ['registration_card' => '1', 'vehicle_sticker' => '1', 'drivers_license' => '1'],
        ], $overrides));
    }

    private function openReception(Vehicle $vehicle, User $user): VehicleReception
    {
        return VehicleReception::create([
            'vehicle_id' => $vehicle->id, 'created_by' => $user->id, 'received_by_name' => 'Jane',
            'trip_reason' => 'Visit', 'location' => 'HQ', 'reception_date' => now()->toDateString(),
            'reception_time' => '09:00', 'initial_mileage' => 1000, 'washed' => false,
            'fuel_level' => 'full', 'fuel_type' => 'gasoline_super', 'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok', 'tires_condition' => 'ok', 'dashboard_indicators' => 'ok',
            'has_anomaly' => false, 'status' => ReceptionStatus::Open,
        ]);
    }

    private function deliveryPayload(): array
    {
        return $this->common([
            'returned_by_name' => 'John Smith',
            'keys_received_by_name' => 'Front Desk',
            'return_date' => now()->toDateString(),
            'return_time' => '17:00',
            'final_mileage' => 1200,
            'documentation' => ['registration_card' => '1', 'vehicle_sticker' => '1'],
        ]);
    }

    public function test_reception_emails_every_selected_recipient(): void
    {
        Storage::fake('public');
        Mail::fake();
        User::factory()->create(['email' => 'uno@example.com', 'receives_notification_emails' => true]);
        User::factory()->create(['email' => 'dos@example.com', 'receives_notification_emails' => true]);
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['license_plate' => 'P123ABC']);

        $this->actingAs($user)->post(route('receptions.store'), $this->receptionPayload($vehicle))->assertRedirect();

        Mail::assertSent(VehicleMovementMail::class, function (VehicleMovementMail $mail) {
            return $mail->hasTo('uno@example.com') && $mail->hasTo('dos@example.com')
                && $mail->isReception()
                && str_contains($mail->envelope()->subject, 'Recepción de vehículo: P123ABC');
        });
        Mail::assertSentCount(1);
    }

    public function test_reception_email_falls_back_to_manager_email_config(): void
    {
        Storage::fake('public');
        Mail::fake();
        config(['vehicle.manager_email' => 'manager@example.com']);
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->post(route('receptions.store'), $this->receptionPayload($vehicle));

        Mail::assertSent(VehicleMovementMail::class, fn ($mail) => $mail->hasTo('manager@example.com'));
    }

    public function test_reception_email_body_contains_movement_details(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['license_plate' => 'P999XYZ']);

        $this->actingAs($user)->post(route('receptions.store'), $this->receptionPayload($vehicle));

        $html = (new VehicleMovementMail(VehicleReception::first()))->render();
        $this->assertStringContainsString('Recepción de vehículo', $html);
        $this->assertStringContainsString('P999XYZ', $html);
        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('Oficina central', $html);
        $this->assertStringContainsString('09:30', $html);
        foreach (['Kilometraje', 'combustible', 'Lavado', 'Motivo', 'Documento', 'Client visit'] as $excluded) {
            $this->assertStringNotContainsString($excluded, $html);
        }
    }

    public function test_delivery_emails_recipients(): void
    {
        Storage::fake('public');
        Mail::fake();
        User::factory()->create(['email' => 'uno@example.com', 'receives_notification_emails' => true]);
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['license_plate' => 'P555DEF']);
        $reception = $this->openReception($vehicle, $user);

        $this->actingAs($user)->post(route('deliveries.store', $reception), $this->deliveryPayload())->assertRedirect();

        Mail::assertSent(VehicleMovementMail::class, function (VehicleMovementMail $mail) {
            return $mail->hasTo('uno@example.com') && ! $mail->isReception()
                && str_contains($mail->envelope()->subject, 'Devolución de vehículo: P555DEF')
                && str_contains($mail->render(), 'John Smith');
        });
    }

    public function test_no_email_when_validation_fails(): void
    {
        Mail::fake();
        User::factory()->create(['receives_notification_emails' => true]);
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->post(route('receptions.store'), $this->receptionPayload($vehicle, ['initial_mileage' => '']))
            ->assertSessionHasErrors('initial_mileage');

        Mail::assertNothingSent();
    }

    public function test_no_email_when_persistence_fails(): void
    {
        Storage::fake('public');
        Mail::fake();
        User::factory()->create(['receives_notification_emails' => true]);
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        VehicleReception::creating(fn () => throw new \RuntimeException('db down'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('receptions.store'), $this->receptionPayload($vehicle));
            $this->fail('Expected exception.');
        } catch (\RuntimeException) {
        }

        Mail::assertNothingSent();
        $this->assertDatabaseCount('vehicle_receptions', 0);
    }

    public function test_mail_failure_does_not_fail_the_reception(): void
    {
        Storage::fake('public');
        User::factory()->create(['email' => 'uno@example.com', 'receives_notification_emails' => true]);
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->actingAs($user)->post(route('receptions.store'), $this->receptionPayload($vehicle))
            ->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseCount('vehicle_receptions', 1);
    }

    public function test_reception_form_suggests_previous_mileage_and_saves_submitted_value(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $withKm = Vehicle::factory()->create();
        $without = Vehicle::factory()->create();
        VehicleReception::create([
            'vehicle_id' => $withKm->id, 'created_by' => $user->id, 'received_by_name' => 'Old',
            'trip_reason' => 'Old', 'location' => 'HQ', 'reception_date' => now()->toDateString(),
            'reception_time' => '08:00', 'initial_mileage' => 4321, 'washed' => false,
            'fuel_level' => 'full', 'fuel_type' => 'gasoline_super', 'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok', 'tires_condition' => 'ok', 'dashboard_indicators' => 'ok',
            'has_anomaly' => false, 'status' => ReceptionStatus::Closed,
        ]);

        $html = $this->actingAs($user)->get(route('receptions.create'))->assertOk()->getContent();
        $this->assertStringContainsString('"'.$withKm->id.'":4321', str_replace(' ', '', $html));
        $this->assertStringContainsString('"'.$without->id.'":null', str_replace(' ', '', $html));

        $this->actingAs($user)->post(route('receptions.store'), $this->receptionPayload($withKm, ['initial_mileage' => 4400]))->assertRedirect();
        $this->assertDatabaseHas('vehicle_receptions', ['vehicle_id' => $withKm->id, 'initial_mileage' => 4400]);
    }

    public function test_comparison_shows_signer_names(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $reception = $this->openReception($vehicle, $user);
        $this->actingAs($user)->post(route('deliveries.store', $reception), $this->deliveryPayload())->assertRedirect();

        $this->actingAs($user)->get(route('comparisons.show', $reception))
            ->assertOk()->assertSee('Jane')->assertSee('John Smith');
    }

    public function test_comparison_and_pdf_show_signer_name_under_each_signature(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $reception = $this->openReception(Vehicle::factory()->create(), $user);
        $this->actingAs($user)->post(route('deliveries.store', $reception), $this->deliveryPayload())->assertRedirect();
        $reception->photos()->create(['position' => 'signature', 'path' => 'x/firma.png', 'disk' => 'public', 'original_filename' => 'firma.png', 'size' => 1, 'mime_type' => 'image/png']);

        $this->get(route('comparisons.show', $reception))
            ->assertSee('Firmado por:', false)->assertSee('Jane')->assertSee('John Smith');
        $this->get(route('comparisons.pdf', $reception))->assertOk();
    }
}
