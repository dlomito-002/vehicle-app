<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_service_record(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($admin)->post(route('services.store'), [
            'vehicle_id' => $vehicle->id,
            'service_type' => 'oil_change',
            'service_date' => now()->toDateString(),
            'mileage_at_service' => 10000,
            'next_service_mileage' => 15000,
        ]);

        $response->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('vehicle_services', [
            'vehicle_id' => $vehicle->id,
            'service_type' => 'oil_change',
        ]);
    }

    public function test_agent_cannot_create_a_service_record(): void
    {
        $agent = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($agent)->post(route('services.store'), [
            'vehicle_id' => $vehicle->id,
            'service_type' => 'oil_change',
            'service_date' => now()->toDateString(),
            'next_service_mileage' => 15000,
        ]);

        $response->assertForbidden();
    }

    public function test_service_requires_a_next_due_date_or_mileage(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($admin)->post(route('services.store'), [
            'vehicle_id' => $vehicle->id,
            'service_type' => 'oil_change',
            'service_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('next_service_date');
    }

    public function test_service_is_overdue_once_current_mileage_passes_the_threshold(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        $service = VehicleService::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $admin->id,
            'service_type' => 'oil_change',
            'service_date' => now()->subMonths(2),
            'mileage_at_service' => 10000,
            'next_service_mileage' => 15000,
        ]);

        $this->assertSame('overdue', $service->alertStatus(15200));
        $this->assertSame('due_soon', $service->alertStatus(14700));
        $this->assertSame('ok', $service->alertStatus(12000));
    }

    public function test_agent_can_view_the_services_index(): void
    {
        $agent = User::factory()->create();

        $response = $this->actingAs($agent)->get(route('services.index'));

        $response->assertOk();
    }
}
