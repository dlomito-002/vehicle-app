<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_photos', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner: VehicleReception or VehicleDelivery.
            // Shared table so the comparison report can match photos by
            // position across both stages with a single query.
            $table->morphs('photographable');

            $table->string('position'); // PhotoPosition enum
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_filename');
            $table->unsignedBigInteger('size'); // bytes
            $table->string('mime_type');

            $table->timestamps();

            $table->index(
                ['photographable_type', 'photographable_id', 'position'],
                'vehicle_photos_photographable_position_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_photos');
    }
};
