<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_equipment_checks', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner: VehicleReception or VehicleDelivery.
            $table->morphs('checkable');

            $table->string('item'); // EquipmentItem enum
            $table->boolean('is_present')->default(false);

            // Optional supporting photo, one per checklist item.
            $table->string('photo_disk')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_original_filename')->nullable();
            $table->unsignedBigInteger('photo_size')->nullable();
            $table->string('photo_mime_type')->nullable();

            $table->timestamps();

            $table->unique(
                ['checkable_type', 'checkable_id', 'item'],
                'vehicle_equipment_checks_unique_per_owner'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_equipment_checks');
    }
};
