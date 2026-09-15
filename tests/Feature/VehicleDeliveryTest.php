<?php

namespace Tests\Feature;

use App\Enums\ConditionComponent;
use App\Enums\EquipmentItem;
use App\Enums\ReceptionStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_SIGNATURE_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function createOpenReception(Vehicle $vehicle, User $user, array $overrides = []): VehicleReception
    {
        return VehicleReception::create(array_merge([
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:00',
            'initial_mileage' => 1000,
            'washed' => false,
            'fuel_level' => 'full',
            'fuel_type' => 'gasoline',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => false,
            'status' => ReceptionStatus::Open,
        ], $overrides));
    }

    private function deliveryPayload(array $overrides = []): array
    {
        return array_merge([
            'returned_by_name' => 'John Smith',
            'keys_received_by_name' => 'Front Desk',
            'return_date' => now()->toDateString(),
            'return_time' => '17:00',
            'final_mileage' => 1200,
            'fuel_level' => 'half',
            'fuel_type' => 'gasoline',
            'washed' => '0',
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
                'insurance_papers' => '1',
            ],
            'equipment_checks' => collect(EquipmentItem::cases())
                ->mapWithKeys(fn ($item) => [$item->value => '1'])
                ->all(),
            'condition_items' => collect(ConditionComponent::cases())
                ->mapWithKeys(fn ($item) => [$item->value => 'ok'])
                ->all(),
        ], $overrides);
    }

    public function test_vehicle_can_have_multiple_open_receptions(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->createOpenReception($vehicle, $user);
        $this->createOpenReception($vehicle, $user);

        $this->assertCount(2, $vehicle->openReceptions()->get());
    }

    public function test_select_reception_step_lists_only_open_receptions_for_the_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $open = $this->createOpenReception($vehicle, $user);
        $closed = $this->createOpenReception($vehicle, $user, ['status' => ReceptionStatus::Closed]);

        $response = $this->actingAs($user)->get(route('deliveries.select-reception', $vehicle));

        $response->assertOk();
        $response->assertSee($open->received_by_name);
        $response->assertViewHas('openReceptions', function ($receptions) use ($open, $closed) {
            return $receptions->contains('id', $open->id) && ! $receptions->contains('id', $closed->id);
        });
    }

    public function test_delivery_closes_the_specific_reception_selected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $receptionA = $this->createOpenReception($vehicle, $user);
        $receptionB = $this->createOpenReception($vehicle, $user);

        $response = $this->actingAs($user)->post(
            route('deliveries.store', $receptionB),
            $this->deliveryPayload()
        );

        $response->assertRedirect(route('comparisons.show', $receptionB));

        $this->assertDatabaseHas('vehicle_deliveries', [
            'vehicle_reception_id' => $receptionB->id,
        ]);

        $this->assertSame(ReceptionStatus::Closed, $receptionB->fresh()->status);
        $this->assertSame(ReceptionStatus::Open, $receptionA->fresh()->status);
    }

    public function test_delivery_cannot_reuse_an_already_closed_reception(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $reception = $this->createOpenReception($vehicle, $user, ['status' => ReceptionStatus::Closed]);

        $response = $this->actingAs($user)->get(route('deliveries.create', $reception));

        $response->assertStatus(409);
    }

    public function test_final_mileage_cannot_be_lower_than_initial_mileage(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $reception = $this->createOpenReception($vehicle, $user, ['initial_mileage' => 5000]);

        $response = $this->actingAs($user)->post(
            route('deliveries.store', $reception),
            $this->deliveryPayload(['final_mileage' => 4000])
        );

        $response->assertSessionHasErrors('final_mileage');
    }

    public function test_delivery_documentation_excludes_drivers_license(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $reception = $this->createOpenReception($vehicle, $user);

        $this->actingAs($user)->post(route('deliveries.store', $reception), $this->deliveryPayload());

        $delivery = $reception->fresh()->delivery;
        $types = $delivery->documentation->pluck('document_type')->map->value->all();

        $this->assertEqualsCanonicalizing(['registration_card', 'vehicle_sticker', 'insurance_papers'], $types);
    }

    public function test_delivery_stores_equipment_and_condition_checklists(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $reception = $this->createOpenReception($vehicle, $user);

        $this->actingAs($user)->post(route('deliveries.store', $reception), $this->deliveryPayload());

        $delivery = $reception->fresh()->delivery;

        $this->assertCount(count(EquipmentItem::cases()), $delivery->equipmentChecks);
        $this->assertCount(count(ConditionComponent::cases()), $delivery->conditionItems);
    }
}
