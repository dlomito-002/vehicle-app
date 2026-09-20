<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Clean up rows that reference options that no longer exist in their
     * backing enums, so casting them can't throw:
     *  - transmission maintenance schedules (and their completions)
     *  - the "Póliza de seguro vigente" documentation answer
     *  - the old generic 'gasoline' fuel type, which can't be mapped to
     *    Superior/Regular without guessing, so it is cleared instead.
     */
    public function up(): void
    {
        $scheduleIds = DB::table('vehicle_maintenance_schedules')
            ->where('category', 'transmission')
            ->pluck('id');

        DB::table('vehicle_maintenance_completions')
            ->whereIn('vehicle_maintenance_schedule_id', $scheduleIds)
            ->delete();

        DB::table('vehicle_maintenance_schedules')
            ->whereIn('id', $scheduleIds)
            ->delete();

        DB::table('vehicle_documentations')
            ->where('document_type', 'insurance_papers')
            ->delete();

        foreach (['vehicle_receptions', 'vehicle_deliveries'] as $table) {
            DB::table($table)->where('fuel_type', 'gasoline')->update(['fuel_type' => null]);
        }
    }

    public function down(): void
    {
        // Deleted rows can't be restored.
    }
};
