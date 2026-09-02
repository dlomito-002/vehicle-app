<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_documentations', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner: VehicleReception or VehicleDelivery.
            // Reception checks 3 document types, delivery checks 2 — the
            // difference is enforced by validation/business logic, not the
            // schema, so new document types can be added later without a
            // migration.
            $table->morphs('documentable');

            $table->string('document_type'); // DocumentType enum
            $table->boolean('is_valid')->default(false);

            $table->timestamps();

            $table->unique(
                ['documentable_type', 'documentable_id', 'document_type'],
                'vehicle_documentations_unique_per_owner'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_documentations');
    }
};
