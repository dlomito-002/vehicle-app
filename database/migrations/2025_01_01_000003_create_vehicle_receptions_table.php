<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Person receiving the vehicle
            $table->string('received_by_name');

            // Trip information
            $table->string('trip_reason');

            // Reception information
            $table->date('reception_date');
            $table->string('reception_time', 5); // HH:MM
            $table->unsignedInteger('initial_mileage');

            // Fuel level
            $table->string('fuel_level');

            // Vehicle inspection (shared ConditionStatus enum per field)
            $table->string('general_condition');
            $table->string('windows_mirrors_lights');
            $table->string('tires_condition');
            $table->string('dashboard_indicators');
            $table->string('cleanliness');

            // Additional damage / anomaly
            $table->boolean('has_anomaly')->default(false);
            $table->text('anomaly_description')->nullable();

            // Lifecycle status: open until a delivery closes it
            $table->string('status')->default('open');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_receptions');
    }
};
