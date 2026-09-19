<?php

namespace App\Enums;

enum FuelType: string
{
    case GasolineSuper = 'gasoline_super';
    case GasolineRegular = 'gasoline_regular';
    case Diesel = 'diesel';

    public function label(): string
    {
        return match ($this) {
            self::GasolineSuper => 'Gasolina Superior',
            self::GasolineRegular => 'Gasolina Regular',
            self::Diesel => 'Diésel',
        };
    }
}
