<?php

namespace App\Enums;

/**
 * Fixed, interval-driven maintenance categories — distinct from the
 * free-text ServiceType log. Each vehicle tracks one schedule per category
 * (see VehicleMaintenanceSchedule), independently of the manual service
 * history recorded via VehicleService.
 */
enum MaintenanceCategory: string
{
    case Basic = 'basic';
    case Major = 'major';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Servicio básico',
            self::Major => 'Servicio mayor',
        };
    }

    /** Default interval in kilometers for a newly created schedule. */
    public function defaultIntervalKm(): int
    {
        return match ($this) {
            self::Basic => 1000,
            self::Major => 4000,
        };
    }
}
