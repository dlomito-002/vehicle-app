<?php

namespace App\Console\Commands;

use App\Enums\MaintenanceCategory;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceSchedule;
use Illuminate\Console\Command;

/**
 * Runs the same due-soon/overdue check that VehicleMaintenanceScheduleController::index()
 * triggers on page view, so alerts still go out even if nobody opens that
 * screen. Safe to run as often as needed — checkAndNotify() only re-sends
 * after meaningful mileage progress (see VehicleMaintenanceSchedule::checkAndNotify()).
 */
class CheckMaintenanceAlerts extends Command
{
    protected $signature = 'maintenance:check-alerts';

    protected $description = 'Send maintenance alert emails for vehicles that just entered their warning window';

    public function handle(): int
    {
        $vehicles = Vehicle::all();
        $sent = 0;

        foreach ($vehicles as $vehicle) {
            foreach (MaintenanceCategory::cases() as $category) {
                $schedule = VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, $category);
                $schedule->setRelation('vehicle', $vehicle);

                $wasSent = $schedule->alert_sent_at?->timestamp;
                $schedule->checkAndNotify();

                if ($schedule->fresh()->alert_sent_at?->timestamp !== $wasSent) {
                    $sent++;
                }
            }
        }

        $this->info("Revisión completa. Alertas enviadas: {$sent}.");

        return self::SUCCESS;
    }
}
