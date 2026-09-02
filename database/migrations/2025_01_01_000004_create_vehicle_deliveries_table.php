<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_deliveries', function (Blueprint $table) {
            $table->id();

            // Each delivery closes exactly one specific reception, explicitly
            // chosen by the user (a vehicle may have several open receptions
            // at once, so this is never inferred automatically).
            $table->foreignId('vehicle_reception_id')
                ->unique()
                ->constrained('vehicle_receptions')
                ->cascadeOnDelete();

            // Denormalized for direct vehicle-scoped queries/reports.
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Person returning the vehicle / person receiving the keys
            $table->string('returned_by_name');
            $table->string('keys_received_by_name');

            // Return information
            $table->date('return_date');
            $table->string('return_time', 5); // HH:MM
            $table->unsignedInteger('final_mileage');

            // Fuel level
            $table->string('fuel_level');

            // Return condition
            $table->boolean('washed')->default(false);
            $table->string('general_condition');
            $table->string('windows_mirrors_lights');
            $table->string('tires_condition');
            $table->string('dashboard_indicators');
            $table->string('cleanliness');

            $table->boolean('has_anomaly')->default(false);
            $table->text('anomaly_description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_deliveries');
    }
};
