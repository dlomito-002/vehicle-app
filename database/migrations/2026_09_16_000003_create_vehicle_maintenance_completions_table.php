<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_maintenance_schedule_id')
                ->constrained(
                    table: 'vehicle_maintenance_schedules',
                    indexName: 'vehicle_maint_completions_schedule_id_fk'
                )
                ->cascadeOnDelete();

            // The odometer reading at which this category's service was
            // completed — the baseline the next scheduled service is
            // calculated from (last completed service + interval_km).
            $table->unsignedInteger('mileage');
            $table->date('service_date');

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('vehicle_maintenance_schedule_id', 'vehicle_maint_completions_schedule_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_completions');
    }
};
