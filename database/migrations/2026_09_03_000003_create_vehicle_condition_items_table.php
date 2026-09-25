<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_condition_items', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner: VehicleReception or VehicleDelivery.
            $table->morphs('conditionable', 'vehicle_condition_items_conditionable_idx');

            $table->string('item'); // ConditionComponent enum
            $table->string('status'); // ConditionStatus enum (ok/issue)

            // Optional supporting photo, one per checklist item.
            $table->string('photo_disk')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_original_filename')->nullable();
            $table->unsignedBigInteger('photo_size')->nullable();
            $table->string('photo_mime_type')->nullable();

            $table->timestamps();

            $table->unique(
                ['conditionable_type', 'conditionable_id', 'item'],
                'vehicle_condition_items_unique_per_owner'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_condition_items');
    }
};
