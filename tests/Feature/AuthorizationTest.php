<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_agent_cannot_access_vehicle_management(): void
    {
        $agent = User::factory()->create();

        $response = $this->actingAs($agent)->get(route('vehicles.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_vehicle_management(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('vehicles.index'));

        $response->assertOk();
    }

    public function test_agent_cannot_view_another_agents_reception(): void
    {
        $owner = User::factory()->create();
        $otherAgent = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $reception = VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $owner->id,
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
        ]);

        $response = $this->actingAs($otherAgent)->get(route('receptions.show', $reception));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_agents_reception(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        $reception = VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $owner->id,
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
        ]);

        $response = $this->actingAs($admin)->get(route('receptions.show', $reception));

        $response->assertOk();
    }
}
