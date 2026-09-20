<?php

namespace Tests\Feature;

use App\Enums\ConditionComponent;
use App\Enums\EquipmentItem;
use App\Enums\ReceptionStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDelivery;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparisonReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparison_report_shows_404_when_no_delivery_exists_yet(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $reception = VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:00',
            'initial_mileage' => 1000,
            'fuel_level' => 'full',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => false,
            'status' => ReceptionStatus::Open,
        ]);

        $response = $this->actingAs($user)->get(route('comparisons.show', $reception));

        $response->assertStatus(404);
    }

    public function test_comparison_report_computes_mileage_and_fuel_diffs(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $reception = VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:00',
            'initial_mileage' => 1000,
            'fuel_level' => 'full',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => false,
            'status' => ReceptionStatus::Closed,
        ]);

        $delivery = VehicleDelivery::create([
            'vehicle_reception_id' => $reception->id,
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'returned_by_name' => 'John Smith',
            'keys_received_by_name' => 'Front Desk',
            'location' => 'Oficina central',
            'return_date' => now()->toDateString(),
            'return_time' => '17:00',
            'final_mileage' => 1250,
            'fuel_level' => 'quarter',
            'washed' => false,
            'general_condition' => 'issue',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => true,
            'anomaly_description' => 'New scratch on door',
        ]);

        $response = $this->actingAs($user)->get(route('comparisons.show', $reception));

        $response->assertOk();
        $response->assertViewHas('comparison', function ($comparison) {
            return $comparison['mileage']['delta'] === 250
                && $comparison['fuel_level']['decreased'] === true
                && $comparison['conditions']['general_condition']['worsened'] === true
                && $comparison['new_anomaly'] === true;
        });
    }

    public function test_comparison_report_diffs_every_equipment_and_condition_item(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $reception = VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:00',
            'initial_mileage' => 1000,
            'fuel_level' => 'full',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => false,
            'status' => ReceptionStatus::Closed,
        ]);

        $reception->equipmentChecks()->create(['item' => 'extinguidor', 'is_present' => true]);
        $reception->conditionItems()->create(['item' => 'llantas', 'status' => 'ok']);

        $delivery = VehicleDelivery::create([
            'vehicle_reception_id' => $reception->id,
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'returned_by_name' => 'John Smith',
            'keys_received_by_name' => 'Front Desk',
            'location' => 'Oficina central',
            'return_date' => now()->toDateString(),
            'return_time' => '17:00',
            'final_mileage' => 1250,
            'fuel_level' => 'quarter',
            'washed' => false,
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => false,
        ]);

        $delivery->equipmentChecks()->create(['item' => 'extinguidor', 'is_present' => false]);
        $delivery->conditionItems()->create(['item' => 'llantas', 'status' => 'issue']);

        $response = $this->actingAs($user)->get(route('comparisons.show', $reception));

        $response->assertOk();
        $response->assertViewHas('comparison', function ($comparison) {
            // All equipment items and all condition components must be
            // present in the diff, not just the items that were recorded.
            $extinguidor = collect($comparison['equipment'])->firstWhere('item', 'extinguidor');
            $llantas = collect($comparison['condition_items'])->firstWhere('item', 'llantas');

            return count($comparison['equipment']) === count(EquipmentItem::cases())
                && count($comparison['condition_items']) === count(ConditionComponent::cases())
                && $extinguidor['worsened'] === true
                && $llantas['worsened'] === true;
        });
    }
}
