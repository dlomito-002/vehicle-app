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
    case Transmission = 'transmission';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Servicio básico',
            self::Major => 'Servicio mayor',
            self::Transmission => 'Servicio de transmisión',
        };
    }

    /**
     * Default interval in kilometers for a newly created schedule.
     * Transmission has no company-defined interval yet — it starts
     * unconfigured (null) and must be set explicitly before alerts can
     * fire for it.
     */
    public function defaultIntervalKm(): ?int
    {
        return match ($this) {
            self::Basic => 1000,
            self::Major => 4000,
            self::Transmission => null,
        };
    }
}
