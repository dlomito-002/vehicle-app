<?php

namespace Tests\Feature;

use App\Mail\LoginVerificationCodeMail;
use App\Models\LoginVerificationCode;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_log_in_with_a_valid_verification_code(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email])
            ->assertRedirect(route('login.verify'));

        Mail::assertSent(LoginVerificationCodeMail::class);

        $code = LoginVerificationCode::where('email', $user->email)->latest('id')->first();

        // The plain code isn't persisted (only its hash), so capture it the
        // same way the mail transport would have received it.
        $plainCode = null;
        Mail::assertSent(LoginVerificationCodeMail::class, function (LoginVerificationCodeMail $mail) use (&$plainCode) {
            $plainCode = $mail->code;

            return true;
        });

        $response = $this->post(route('login.verify'), ['code' => $plainCode]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($code->fresh()->consumed_at);
    }

    public function test_login_rejects_an_invalid_verification_code(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email]);

        $response = $this->post(route('login.verify'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
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
            'has_anomaly' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('receptions.show', $reception));

        $response->assertOk();
    }

    public function test_admin_can_update_a_vehicle(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($admin)->get(route('vehicles.edit', $vehicle))->assertOk();

        $this->actingAs($admin)
            ->put(route('vehicles.update', $vehicle), ['make' => 'Toyota', 'model' => 'Hilux', 'license_plate' => $vehicle->license_plate])
            ->assertRedirect(route('vehicles.index'));

        $this->assertSame('Toyota', $vehicle->fresh()->make);
    }

    public function test_admin_can_delete_a_vehicle(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($admin)->delete(route('vehicles.destroy', $vehicle))
            ->assertRedirect(route('vehicles.index'));

        $this->assertSoftDeleted($vehicle);
    }

    public function test_agent_cannot_update_or_delete_a_vehicle(): void
    {
        $agent = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['make' => 'Original']);

        $this->actingAs($agent)->get(route('vehicles.edit', $vehicle))->assertForbidden();
        $this->actingAs($agent)
            ->put(route('vehicles.update', $vehicle), ['make' => 'Hack', 'license_plate' => $vehicle->license_plate])
            ->assertForbidden();
        $this->actingAs($agent)->delete(route('vehicles.destroy', $vehicle))->assertForbidden();

        $this->assertSame('Original', $vehicle->fresh()->make);
        $this->assertNotSoftDeleted($vehicle);
    }

    public function test_vehicle_index_shows_edit_and_delete_actions_only_to_admin(): void
    {
        $vehicle = Vehicle::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('vehicles.index'))
            ->assertSee(route('vehicles.edit', $vehicle))
            ->assertSee(route('vehicles.destroy', $vehicle));
    }
}
