<?php

namespace Tests\Feature;

use App\Enums\ConditionComponent;
use App\Enums\EquipmentItem;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleReceptionTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_SIGNATURE_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function validPayload(Vehicle $vehicle, array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $vehicle->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:30',
            'initial_mileage' => 1000,
            'washed' => '0',
            'fuel_level' => 'full',
            'fuel_type' => 'gasoline_super',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => '0',
            'signature_data' => self::TINY_SIGNATURE_PNG,
            'documentation' => [
                'registration_card' => '1',
                'vehicle_sticker' => '1',
                'drivers_license' => '1',
            ],
            'equipment_checks' => collect(EquipmentItem::cases())
                ->mapWithKeys(fn ($item) => [$item->value => '1'])
                ->all(),
            'condition_items' => collect(ConditionComponent::cases())
                ->mapWithKeys(fn ($item) => [$item->value => 'ok'])
                ->all(),
        ], $overrides);
    }

    public function test_agent_can_create_a_reception(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle));

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicle_receptions', [
            'vehicle_id' => $vehicle->id,
            'received_by_name' => 'Jane Doe',
            'status' => 'open',
        ]);

        $reception = VehicleReception::first();
        $this->assertCount(3, $reception->documentation);
        $this->assertCount(count(EquipmentItem::cases()), $reception->equipmentChecks);
        $this->assertCount(count(ConditionComponent::cases()), $reception->conditionItems);
    }

    public function test_reception_accepts_each_of_the_three_fuel_types_and_rejects_others(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        foreach (['gasoline_super', 'gasoline_regular', 'diesel'] as $fuelType) {
            $vehicle = Vehicle::factory()->create();

            $this->actingAs($user)
                ->post(route('receptions.store'), $this->validPayload($vehicle, ['fuel_type' => $fuelType]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['Gasolina Superior', 'Gasolina Regular', 'Diésel'],
            array_map(fn ($case) => $case->label(), \App\Enums\FuelType::cases()),
        );

        foreach (['gasoline', 'electric', ''] as $invalid) {
            $vehicle = Vehicle::factory()->create();

            $this->actingAs($user)
                ->post(route('receptions.store'), $this->validPayload($vehicle, ['fuel_type' => $invalid]))
                ->assertSessionHasErrors('fuel_type');
        }
    }

    public function test_insurance_policy_question_is_gone_from_the_reception_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('receptions.create'))
            ->assertOk()
            ->assertDontSee('Póliza de seguro vigente')
            ->assertDontSee('insurance_papers');

        $this->assertNotContains('insurance_papers', array_map(fn ($d) => $d->value, \App\Enums\DocumentType::cases()));
    }

    public function test_transmission_is_not_an_available_maintenance_category(): void
    {
        $this->assertNull(\App\Enums\MaintenanceCategory::tryFrom('transmission'));
        $this->assertCount(2, \App\Enums\MaintenanceCategory::cases());
    }

    public function test_vehicle_with_open_reception_is_not_offered_again(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle));

        $response = $this->actingAs($user)->get(route('receptions.create'));

        $response->assertOk();
        $response->assertViewHas('vehicles', fn ($vehicles) => ! $vehicles->contains('id', $vehicle->id));
    }

    public function test_vehicle_already_checked_out_cannot_be_requested_again(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle));

        // Direct resubmission (bypassing the filtered dropdown) must still
        // be rejected server-side.
        $response = $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle));

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertCount(1, VehicleReception::where('vehicle_id', $vehicle->id)->get());
    }

    public function test_reception_requires_anomaly_description_when_anomaly_reported(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $file = UploadedFile::fake()->image('anomaly.jpg');

        $response = $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle, [
            'has_anomaly' => '1',
            'anomaly_photos' => [$file],
        ]));

        $response->assertSessionHasErrors('anomaly_description');
    }

    public function test_reception_requires_anomaly_photo_when_anomaly_reported(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle, [
            'has_anomaly' => '1',
            'anomaly_description' => 'Scratch on rear bumper',
        ]));

        $response->assertSessionHasErrors('anomaly_photos');
    }

    public function test_reception_photo_uploads_are_stored_with_position(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $payload = $this->validPayload($vehicle);
        $payload['position_photos'] = [
            'front' => UploadedFile::fake()->image('front.jpg')->size(500),
        ];

        $response = $this->actingAs($user)->post(route('receptions.store'), $payload);

        $response->assertRedirect();

        $reception = VehicleReception::first();
        $this->assertCount(1, $reception->photos->where('position', 'front'));
        $this->assertSame('front', $reception->photos->firstWhere('position', 'front')->position->value);
        Storage::disk('public')->assertExists($reception->photos->firstWhere('position', 'front')->path);
    }

    public function test_oversized_photo_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $payload = $this->validPayload($vehicle);
        $payload['position_photos'] = [
            'front' => UploadedFile::fake()->image('front.jpg')->size(11000), // > 10MB
        ];

        $response = $this->actingAs($user)->post(route('receptions.store'), $payload);

        $response->assertSessionHasErrors('position_photos.front');
    }

    public function test_blank_canvas_data_uri_is_rejected_with_a_friendly_message(): void
    {
        // Reproduces the "first use" bug: a canvas measured while hidden
        // (0x0 backing bitmap) produces the bare "data:," URI instead of a
        // real PNG. It must fail with a translated message, never the raw
        // "validation.starts_with" key.
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle, [
            'signature_data' => 'data:,',
        ]));

        $response->assertSessionHasErrors('signature_data');
        $errors = $response->getSession()->get('errors')->getBag('default')->get('signature_data');
        $this->assertNotContains('validation.starts_with', $errors);
    }

    public function test_signature_data_with_valid_prefix_but_invalid_png_bytes_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->post(route('receptions.store'), $this->validPayload($vehicle, [
            'signature_data' => 'data:image/png;base64,'.base64_encode('not a real png'),
        ]));

        $response->assertSessionHasErrors('signature_data');
        $this->assertDatabaseCount('vehicle_receptions', 0);
    }

    public function test_guest_cannot_create_a_reception(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->post(route('receptions.store'), $this->validPayload($vehicle));

        $response->assertRedirect(route('login'));
    }
}
