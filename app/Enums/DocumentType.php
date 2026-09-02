<?php

namespace App\Enums;

enum DocumentType: string
{
    case RegistrationCard = 'registration_card';
    case VehicleSticker = 'vehicle_sticker';
    case DriversLicense = 'drivers_license';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationCard => 'Tarjeta de circulación',
            self::VehicleSticker => 'Calcomanía vehicular vigente',
            self::DriversLicense => 'Licencia de conducir vigente',
        };
    }

    /** Documentation checked during vehicle reception. */
    public static function forReception(): array
    {
        return [self::RegistrationCard, self::VehicleSticker, self::DriversLicense];
    }

    /** Documentation checked during vehicle delivery/return (no driver's license). */
    public static function forDelivery(): array
    {
        return [self::RegistrationCard, self::VehicleSticker];
    }
}
