<?php

namespace Tests\Feature;

use App\Enums\MaintenanceCategory;
use App\Enums\ReceptionStatus;
use App\Mail\MaintenanceAlertMail;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceSchedule;
use App\Models\VehicleReception;
use App\Support\NotificationRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function recordReception(Vehicle $vehicle, User $user, int $mileage): void
    {
        VehicleReception::create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $user->id,
            'received_by_name' => 'Jane Doe',
            'trip_reason' => 'Client visit',
            'location' => 'Oficina central',
            'reception_date' => now()->toDateString(),
            'reception_time' => '09:00',
            'initial_mileage' => $mileage,
            'washed' => false,
            'fuel_level' => 'full',
            'fuel_type' => 'gasoline_super',
            'general_condition' => 'ok',
            'windows_mirrors_lights' => 'ok',
            'tires_condition' => 'ok',
            'dashboard_indicators' => 'ok',
            'has_anomaly' => false,
            'status' => ReceptionStatus::Open,
        ]);
    }

    public function test_index_shows_current_mileage_next_due_and_remaining_km(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, MaintenanceCategory::Basic)
            ->recordCompletion(10000, now()->toDateString(), $admin->id);
        VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, MaintenanceCategory::Major)
            ->recordCompletion(10000, now()->toDateString(), $admin->id);
        $this->recordReception($vehicle, $admin, 10850);

        $this->actingAs($admin)->get(route('maintenance-schedules.index'))
            ->assertOk()
            ->assertSee('Kilometraje actual')
            ->assertSee('10,850 km')
            ->assertSee('11,000 km')
            ->assertSee('Faltan 150 km')
            ->assertSee('14,000 km')
            ->assertSee('Faltan 3,150 km');
    }

    public function test_index_shows_exceeded_km_when_overdue(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, MaintenanceCategory::Basic)
            ->recordCompletion(10000, now()->toDateString(), $admin->id);
        $this->recordReception($vehicle, $admin, 11250);

        $this->actingAs($admin)->get(route('maintenance-schedules.index'))
            ->assertOk()
            ->assertSee('Excedido por 250 km');
    }

    public function test_index_handles_vehicle_without_mileage_or_service_history(): void
    {
        $user = User::factory()->create();
        Vehicle::factory()->create();

        $this->actingAs($user)->get(route('maintenance-schedules.index'))
            ->assertOk()
            ->assertSee('Sin registro')
            ->assertSee('Registra un servicio para iniciar el conteo.')
            ->assertDontSee('Faltan');
    }

    public function test_maintenance_alert_is_sent_once_to_the_selected_users(): void
    {
        Mail::fake();
        config(['vehicle.manager_email' => 'gerente@example.com']);

        $admin = User::factory()->admin()->create(['email' => 'admin@example.com', 'receives_notification_emails' => true]);
        User::factory()->create(['email' => 'flota@example.com', 'receives_notification_emails' => true]);
        $vehicle = Vehicle::factory()->create();

        VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, MaintenanceCategory::Basic)
            ->recordCompletion(10000, now()->toDateString(), $admin->id);
        $this->recordReception($vehicle, $admin, 10900);

        $this->actingAs($admin)->get(route('maintenance-schedules.index'))->assertOk();
        $this->actingAs($admin)->get(route('maintenance-schedules.index'))->assertOk();

        Mail::assertSentCount(1);
        Mail::assertSent(MaintenanceAlertMail::class, fn (MaintenanceAlertMail $mail) => count($mail->to) === 2
            && $mail->hasTo('admin@example.com')
            && $mail->hasTo('flota@example.com'));
    }

    public function test_maintenance_alert_is_skipped_safely_without_recipients(): void
    {
        Mail::fake();
        config(['vehicle.manager_email' => null]);

        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();

        $schedule = VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, MaintenanceCategory::Basic);
        $schedule->recordCompletion(10000, now()->toDateString(), $admin->id);
        $this->recordReception($vehicle, $admin, 10900);

        $this->actingAs($admin)->get(route('maintenance-schedules.index'))->assertOk();

        Mail::assertNothingSent();
        // Not marked as sent, so the alert still fires once recipients exist.
        $this->assertNull($schedule->fresh()->alert_sent_at);
    }

    public function test_notification_recipients_are_unique_and_ignore_unselected_users(): void
    {
        config(['vehicle.manager_email' => 'gerente@example.com']);

        User::factory()->create(['email' => 'uno@example.com', 'receives_notification_emails' => true]);
        User::factory()->create(['email' => 'UNO@example.com', 'receives_notification_emails' => true]);
        User::factory()->create(['email' => 'otro@example.com']);

        $this->assertSame(['uno@example.com'], NotificationRecipients::emails());
    }
}
