<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

            // MaintenanceCategory enum: basic | major.
            $table->string('category');

            // Km interval for this category. Null means "not yet defined".
            $table->unsignedInteger('interval_km')->nullable();

            // Set when the 200km warning email is sent for the vehicle's
            // current maintenance window, so the same window doesn't
            // generate duplicate emails. Cleared back to null whenever a
            // completion is recorded for this category (see
            // VehicleMaintenanceSchedule::recordCompletion()).
            $table->timestamp('alert_sent_at')->nullable();

            $table->timestamps();

            $table->unique(['vehicle_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_schedules');
    }
};
