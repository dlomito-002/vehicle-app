<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_deliveries', function (Blueprint $table) {
            // Where the vehicle was returned (office, client site, airport, etc.).
            // Stored independently from the reception's location since a
            // vehicle may be picked up in one place and returned in another.
            //
            // Nullable: deliveries created before this migration have no
            // location on record (we don't know where they happened) and
            // SQLite can't add a NOT NULL column with no default to a
            // table that already has rows. New deliveries still require
            // it — that's enforced by StoreVehicleDeliveryRequest.
            $table->string('location')->nullable()->after('keys_received_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_deliveries', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
