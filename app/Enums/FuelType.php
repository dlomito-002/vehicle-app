<?php

namespace App\Enums;

enum FuelType: string
{
    case Diesel = 'diesel';
    case Gasoline = 'gasoline';

    public function label(): string
    {
        return match ($this) {
            self::Diesel => 'Diesel',
            self::Gasoline => 'Gasolina',
        };
    }
}
