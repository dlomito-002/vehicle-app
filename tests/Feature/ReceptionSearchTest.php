<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Buscador de la barra superior: filtra recepciones por placa, vehículo o responsable (?q=). */
class ReceptionSearchTest extends TestCase
{
    use RefreshDatabase;

    private function reception(User $owner, Vehicle $vehicle, string $receivedBy): VehicleReception
    {
        return VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $owner->id,
            'received_by_name' => $receivedBy,
            'trip_reason' => 'Visita a parque',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:00',
            'initial_mileage' => 1000,
            'fuel_level' => 'full',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'has_anomaly' => false,
        ]);
    }

    public function test_topbar_search_filters_receptions(): void
    {
        $user = User::factory()->create();
        $hyundai = Vehicle::factory()->create(['make' => 'Hyundai', 'license_plate' => 'P111AAA']);
        $toyota = Vehicle::factory()->create(['make' => 'Toyota', 'license_plate' => 'P222BBB']);
        $this->reception($user, $hyundai, 'Ana Pérez');
        $this->reception($user, $toyota, 'Carlos Gómez');

        $this->actingAs($user)->get(route('receptions.index', ['q' => 'P111']))
            ->assertOk()->assertSee('P111AAA')->assertDontSee('P222BBB')
            ->assertSee('class="topbar-search"', false)->assertSee('value="P111"', false);

        $this->actingAs($user)->get(route('receptions.index', ['q' => 'Carlos']))
            ->assertOk()->assertSee('P222BBB')->assertDontSee('P111AAA');

        $this->actingAs($user)->get(route('receptions.index'))
            ->assertOk()->assertSee('P111AAA')->assertSee('P222BBB');
    }
}
