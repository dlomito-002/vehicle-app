<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_receptions', function (Blueprint $table) {
            // Where the vehicle was received (office, client site, airport, etc.).
            $table->string('location')->after('trip_reason');

            // Carwash tracking, mirroring the existing "washed" field on deliveries.
            $table->boolean('washed')->default(false)->after('initial_mileage');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_receptions', function (Blueprint $table) {
            $table->dropColumn(['location', 'washed']);
        });
    }
};
