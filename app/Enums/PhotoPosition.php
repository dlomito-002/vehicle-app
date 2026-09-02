<?php

namespace App\Enums;

enum PhotoPosition: string
{
    case Front = 'front';
    case Rear = 'rear';
    case Right = 'right';
    case Left = 'left';
    case Dashboard = 'dashboard';
    case Interior = 'interior';
    case Anomaly = 'anomaly';

    public function label(): string
    {
        return match ($this) {
            self::Front => 'Frente',
            self::Rear => 'Parte trasera',
            self::Right => 'Lado derecho',
            self::Left => 'Lado izquierdo',
            self::Dashboard => 'Tablero',
            self::Interior => 'Interior',
            self::Anomaly => 'Evidencia de anomalía',
        };
    }

    /**
     * The fixed single-photo positions every form requests.
     * 'anomaly' is handled separately since it allows multiple files.
     */
    public static function standardPositions(): array
    {
        return [self::Front, self::Rear, self::Right, self::Left, self::Dashboard, self::Interior];
    }
}
