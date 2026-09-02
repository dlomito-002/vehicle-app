<?php

namespace App\Enums;

enum FuelLevel: string
{
    case Full = 'full';
    case ThreeQuarters = 'three_quarters';
    case Half = 'half';
    case Quarter = 'quarter';
    case LessThanQuarter = 'less_than_quarter';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Tanque lleno',
            self::ThreeQuarters => '3/4',
            self::Half => '1/2',
            self::Quarter => '1/4',
            self::LessThanQuarter => 'Menos de 1/4',
        };
    }

    /**
     * Ordinal weight used to compare fuel levels (higher = more fuel).
     */
    public function weight(): int
    {
        return match ($this) {
            self::Full => 4,
            self::ThreeQuarters => 3,
            self::Half => 2,
            self::Quarter => 1,
            self::LessThanQuarter => 0,
        };
    }
}
