<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cleanliness is tracked only by the carwash ("washed") field; the
     * separate ok/issue "cleanliness" rating duplicated it.
     */
    public function up(): void
    {
        Schema::table('vehicle_receptions', function (Blueprint $table) {
            $table->dropColumn('cleanliness');
        });

        Schema::table('vehicle_deliveries', function (Blueprint $table) {
            $table->dropColumn('cleanliness');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_receptions', function (Blueprint $table) {
            $table->string('cleanliness')->default('ok');
        });

        Schema::table('vehicle_deliveries', function (Blueprint $table) {
            $table->string('cleanliness')->default('ok');
        });
    }
};
