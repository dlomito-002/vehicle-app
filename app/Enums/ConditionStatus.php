<?php

namespace App\Enums;

/**
 * Shared two-state enum reused across every binary inspection field
 * (general body condition, windows/mirrors/lights, tires, dashboard
 * indicators, cleanliness). The concrete wording differs per field, so
 * label() takes the field name and returns the copy defined in the
 * requirements for that specific field.
 */
enum ConditionStatus: string
{
    case Ok = 'ok';
    case Issue = 'issue';

    public function label(string $field): string
    {
        return match ($field) {
            'general_condition' => $this === self::Ok ? 'Sin daños visibles' : 'Tiene daños',
            'windows_mirrors_lights' => $this === self::Ok ? 'En buen estado' : 'Tiene una anomalía',
            'tires_condition' => $this === self::Ok ? 'En buen estado' : 'Tienen una anomalía',
            'dashboard_indicators' => $this === self::Ok ? 'Sin alertas' : 'Tiene una alerta',
            'cleanliness' => $this === self::Ok ? 'Limpio' : 'Requiere limpieza',
            default => $this === self::Ok ? 'Correcto' : 'Incidencia',
        };
    }

    public static function fieldLabels(): array
    {
        return [
            'general_condition' => 'Estado general de la carrocería',
            'windows_mirrors_lights' => 'Ventanas, espejos y luces',
            'tires_condition' => 'Neumáticos',
            'dashboard_indicators' => 'Tablero / indicadores',
            'cleanliness' => 'Limpieza del vehículo',
        ];
    }
}
