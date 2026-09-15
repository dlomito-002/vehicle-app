<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('service_type'); // ServiceType enum
            $table->string('other_description')->nullable(); // free text when service_type = other

            $table->date('service_date');
            $table->unsignedInteger('mileage_at_service')->nullable();

            // Next-due thresholds used to compute alerts. Either or both may be set.
            $table->date('next_service_date')->nullable();
            $table->unsignedInteger('next_service_mileage')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_services');
    }
};
