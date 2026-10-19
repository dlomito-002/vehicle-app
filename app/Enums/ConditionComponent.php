<?php

namespace App\Enums;

/**
 * The "ESTADO GENERAL DEL VEHÍCULO" checklist from the paper bitácora —
 * 9 components, each rated with ConditionStatus, optionally with a
 * supporting photo. (Logos, Polarizado y Batería se eliminaron en
 * septiembre 2026 a solicitud del cliente.)
 */
enum ConditionComponent: string
{
    case Chasis = 'chasis';
    case Carroceria = 'carroceria';
    case Pintura = 'pintura';
    case Vidrios = 'vidrios';
    case Tapiceria = 'tapiceria';
    case Emblemas = 'emblemas';
    case Luces = 'luces';
    case PideVias = 'pide_vias';
    case Llantas = 'llantas';

    /**
     * Components still rated on the forms. The rest are covered by the general
     * condition fields in ConditionStatus::fieldLabels() (body, glass and
     * lights, tires, dashboard), so they are not asked twice.
     *
     * @return self[]
     */
    public static function formItems(): array
    {
        return [self::PideVias, self::Tapiceria];
    }

    public function label(): string
    {
        return match ($this) {
            self::Chasis => 'Chasis',
            self::Carroceria => 'Carrocería',
            self::Pintura => 'Pintura',
            self::Vidrios => 'Vidrios',
            self::Tapiceria => 'Tapicería',
            self::Emblemas => 'Emblemas',
            self::Luces => 'Luces',
            self::PideVias => 'Pide vías',
            self::Llantas => 'Llantas',
        };
    }
}
