<?php

use App\Models\VehicleMaintenanceSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenance_schedules', function (Blueprint $table) {
            // Mileage and status at the last alert sent in the current
            // cycle; both null until the first alert. Cleared on completion.
            $table->unsignedInteger('alert_mileage')->nullable()->after('alert_sent_at');
            $table->string('alert_status')->nullable()->after('alert_mileage');
        });

        // Schedules already alerted under the old once-per-window rule: seed
        // the baseline from the current mileage so they aren't re-sent.
        VehicleMaintenanceSchedule::with('vehicle')->whereNotNull('alert_sent_at')->each(function ($schedule) {
            $schedule->forceFill([
                'alert_mileage' => $schedule->vehicle?->currentMileage(),
                'alert_status' => $schedule->alertStatus(),
            ])->save();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_maintenance_schedules', function (Blueprint $table) {
            $table->dropColumn(['alert_mileage', 'alert_status']);
        });
    }
};
