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
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
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

    private function alertSetup(): array
    {
        Mail::fake();
        $admin = User::factory()->admin()->create(['receives_notification_emails' => true]);
        $vehicle = Vehicle::factory()->create();
        $schedule = VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, MaintenanceCategory::Basic);
        $schedule->recordCompletion(9000, now()->toDateString(), $admin->id); // due at 10,000

        return [$admin, $vehicle, $schedule];
    }

    private function check(Vehicle $vehicle, User $admin, int $mileage): void
    {
        $this->recordReception($vehicle, $admin, $mileage);
        VehicleMaintenanceSchedule::checkAllFor($vehicle->fresh());
    }

    public function test_alert_is_resent_only_on_meaningful_mileage_progress(): void
    {
        [$admin, $vehicle] = $this->alertSetup();

        $this->check($vehicle, $admin, 9700); // 300 km away: outside window
        Mail::assertNothingSent();

        $this->check($vehicle, $admin, 9800); // enters window
        Mail::assertSentCount(1);

        $this->check($vehicle, $admin, 9800); // same mileage
        $this->check($vehicle, $admin, 9850); // +50 km: small
        Mail::assertSentCount(1);

        $this->check($vehicle, $admin, 9990); // +190 km: updated alert
        Mail::assertSentCount(2);

        $this->check($vehicle, $admin, 9990); // duplicate
        Mail::assertSentCount(2);

        $this->check($vehicle, $admin, 10005); // escalates to overdue
        Mail::assertSentCount(3);
        Mail::assertSent(MaintenanceAlertMail::class, fn ($m) => $m->schedule->alertStatus() === 'overdue');
    }

    public function test_completion_resets_the_cycle_and_next_interval_alerts_independently(): void
    {
        [$admin, $vehicle, $schedule] = $this->alertSetup();

        $this->check($vehicle, $admin, 9990);
        Mail::assertSentCount(1);

        $this->actingAs($admin)->post(route('maintenance-schedules.complete', [$vehicle, MaintenanceCategory::Basic]), [
            'mileage' => 10000,
            'service_date' => now()->toDateString(),
        ])->assertRedirect();

        $schedule->refresh();
        $this->assertNull($schedule->alert_mileage);
        $this->assertNull($schedule->alert_status);
        $this->assertSame(11000, $schedule->nextDueMileage());

        $this->check($vehicle, $admin, 10500); // far from 11,000
        Mail::assertSentCount(1);

        $this->check($vehicle, $admin, 10800); // new cycle enters window
        Mail::assertSentCount(2);
    }

    public function test_failed_email_is_not_recorded_and_is_retried(): void
    {
        [$admin, $vehicle, $schedule] = $this->alertSetup();

        app()->forgetInstance('mail.manager');
        Mail::clearResolvedInstances(); // real mailer instead of the fake, so a send can actually fail
        config(['mail.default' => 'array']);
        Event::listen(MessageSending::class, fn () => throw new \RuntimeException('smtp down'));
        $this->recordReception($vehicle, $admin, 9900);
        VehicleMaintenanceSchedule::checkAllFor($vehicle->fresh()); // must not throw

        $this->assertNull($schedule->fresh()->alert_mileage);

        Event::forget(MessageSending::class);
        VehicleMaintenanceSchedule::checkAllFor($vehicle->fresh());
        $this->assertSame(9900, $schedule->fresh()->alert_mileage);
    }

    public function test_reception_flow_triggers_the_maintenance_check(): void
    {
        [$admin, $vehicle, $schedule] = $this->alertSetup();
        $this->recordReception($vehicle, $admin, 9800);

        VehicleMaintenanceSchedule::checkAllFor($vehicle);
        VehicleMaintenanceSchedule::checkAllFor($vehicle); // retried request

        Mail::assertSentCount(1);
        $this->assertSame(9800, $schedule->fresh()->alert_mileage);
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
