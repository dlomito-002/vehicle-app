<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_receptions', function (Blueprint $table) {
            $table->string('fuel_type')->nullable()->after('fuel_level');
        });

        Schema::table('vehicle_deliveries', function (Blueprint $table) {
            $table->string('fuel_type')->nullable()->after('fuel_level');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_receptions', function (Blueprint $table) {
            $table->dropColumn('fuel_type');
        });

        Schema::table('vehicle_deliveries', function (Blueprint $table) {
            $table->dropColumn('fuel_type');
        });
    }
};
