<?php

namespace App\Enums;

enum ServiceType: string
{
    case OilChange = 'oil_change';
    case Brakes = 'brakes';
    case Tires = 'tires';
    case Battery = 'battery';
    case AirFilter = 'air_filter';
    case GeneralMaintenance = 'general_maintenance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OilChange => 'Cambio de aceite',
            self::Brakes => 'Frenos',
            self::Tires => 'Llantas',
            self::Battery => 'Batería',
            self::AirFilter => 'Filtro de aire',
            self::GeneralMaintenance => 'Mantenimiento general',
            self::Other => 'Otro',
        };
    }
}
