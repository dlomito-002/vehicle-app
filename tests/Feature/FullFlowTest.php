<?php

namespace Tests\Feature;

use App\Enums\ConditionComponent;
use App\Enums\EquipmentItem;
use App\Enums\PhotoPosition;
use App\Enums\ReceptionStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Walks the whole reception → delivery → comparison journey over HTTP with
 * photos, checklist photos and a drawn signature, the way a real user does.
 */
class FullFlowTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function positionPhotos(): array
    {
        return collect(PhotoPosition::standardPositions())
            ->mapWithKeys(fn ($p) => [$p->value => UploadedFile::fake()->image($p->value.'.jpg', 800, 600)])
            ->all();
    }

    private function checklistPhotos(): array
    {
        return [
            'equipment_photos' => [EquipmentItem::cases()[0]->value => UploadedFile::fake()->image('eq.jpg')],
            'condition_photos' => [ConditionComponent::cases()[0]->value => UploadedFile::fake()->image('cond.jpg')],
        ];
    }

    private function formPayload(array $overrides = []): array
    {
        return array_merge([
            'location' => 'Oficina central',
            'washed' => '0',
            'fuel_level' => 'half',
            'fuel_type' => 'gasoline_super',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => '1',
            'anomaly_description' => 'Rayón en la puerta',
            'anomaly_photos' => [UploadedFile::fake()->image('anomaly.jpg')],
            'signature_data' => self::SIGNATURE,
            'documentation' => ['registration_card' => '1', 'vehicle_sticker' => '1', 'drivers_license' => '1'],
            'equipment_checks' => collect(EquipmentItem::cases())->mapWithKeys(fn ($i) => [$i->value => '1'])->all(),
            'condition_items' => collect(ConditionComponent::cases())->mapWithKeys(fn ($i) => [$i->value => 'ok'])->all(),
            'position_photos' => $this->positionPhotos(),
        ], $this->checklistPhotos(), $overrides);
    }

    public function test_full_reception_delivery_comparison_flow(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        // 1. Reception with photos, checklist photos, anomaly and drawn signature.
        $this->actingAs($user)->get(route('receptions.create'))->assertOk();

        $this->actingAs($user)->post(route('receptions.store'), $this->formPayload([
            'vehicle_id' => $vehicle->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Visita a cliente',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:30',
            'initial_mileage' => 1000,
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $reception = VehicleReception::firstOrFail();
        $this->assertSame(ReceptionStatus::Open, $reception->status);
        // 6 positions + 1 anomaly + signature
        $this->assertCount(8, $reception->photos);
        $this->assertNotNull($reception->signaturePhoto());
        $reception->photos->each(fn ($p) => Storage::disk('public')->assertExists($p->path));
        $this->assertNotNull($reception->equipmentChecks->firstWhere('photo_path', '!=', null));
        $this->assertNotNull($reception->conditionItems->firstWhere('photo_path', '!=', null));

        $this->actingAs($user)->get(route('receptions.show', $reception))->assertOk();
        $this->actingAs($user)->get(route('receptions.index'))->assertOk();

        // 2. Delivery wizard: vehicle → reception → damage report (+PDF) → form.
        $this->actingAs($user)->get(route('deliveries.select-vehicle'))->assertOk()->assertSee($vehicle->license_plate);
        $this->actingAs($user)->get(route('deliveries.select-reception', $vehicle))->assertOk();
        $this->actingAs($user)->get(route('deliveries.damage-report', $reception))->assertOk();
        $this->actingAs($user)->get(route('deliveries.damage-report.pdf', $reception))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($user)->get(route('deliveries.create', $reception))->assertOk();

        // 3. Delivery with photos and signature closes the reception.
        $this->actingAs($user)->post(route('deliveries.store', $reception), $this->formPayload([
            'returned_by_name' => 'John Smith',
            'keys_received_by_name' => 'Recepción',
            'return_date' => now()->toDateString(),
            'return_time' => '17:00',
            'final_mileage' => 1200,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('comparisons.show', $reception));

        $delivery = $reception->fresh()->delivery;
        $this->assertSame(ReceptionStatus::Closed, $reception->fresh()->status);
        $this->assertCount(8, $delivery->photos);
        $delivery->photos->each(fn ($p) => Storage::disk('public')->assertExists($p->path));

        $this->actingAs($user)->get(route('deliveries.show', $delivery))->assertOk();
        $this->actingAs($user)->get(route('deliveries.index'))->assertOk();

        // 4. Comparison page and PDF (images embedded from storage).
        $this->actingAs($user)->get(route('comparisons.show', $reception))->assertOk()->assertSee('200');
        $this->actingAs($user)->get(route('comparisons.pdf', $reception))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        // 5. The closed reception can no longer be delivered again.
        $this->actingAs($user)->get(route('deliveries.create', $reception))->assertStatus(409);
        $this->actingAs($user)->get(route('deliveries.select-reception', $vehicle))
            ->assertRedirect(route('deliveries.select-vehicle'));
    }
}
