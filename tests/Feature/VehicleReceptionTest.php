<?php

namespace Tests\Feature;

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

    private function validPayload(Vehicle $vehicle, array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $vehicle->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:30',
            'initial_mileage' => 1000,
            'fuel_level' => 'full',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'cleanliness' => 'ok',
            'has_anomaly' => '0',
            'documentation' => [
                'registration_card' => '1',
                'vehicle_sticker' => '1',
                'drivers_license' => '1',
            ],
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
        $this->assertCount(1, $reception->photos);
        $this->assertSame('front', $reception->photos->first()->position->value);
        Storage::disk('public')->assertExists($reception->photos->first()->path);
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

    public function test_guest_cannot_create_a_reception(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->post(route('receptions.store'), $this->validPayload($vehicle));

        $response->assertRedirect(route('login'));
    }
}
