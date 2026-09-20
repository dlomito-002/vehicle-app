<?php

namespace Tests\Feature;

use App\Enums\ReceptionStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_marks_a_vehicle_with_an_open_reception_as_busy_today(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:00',
            'initial_mileage' => 1000,
            'fuel_level' => 'full',
            'fuel_type' => 'gasoline_super',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => false,
            'status' => ReceptionStatus::Open,
        ]);

        $response = $this->actingAs($user)->get(route('calendar.index'));

        $response->assertOk();
        $response->assertViewHas('occupancy', function ($occupancy) use ($vehicle) {
            return array_key_exists(now()->toDateString(), $occupancy[$vehicle->id] ?? []);
        });
    }

    public function test_calendar_does_not_mark_an_available_vehicle_as_busy(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->get(route('calendar.index'));

        $response->assertOk();
        $response->assertViewHas('occupancy', function ($occupancy) use ($vehicle) {
            return empty($occupancy[$vehicle->id] ?? []);
        });
        $this->assertTrue($vehicle->fresh()->isAvailable());
    }
}
