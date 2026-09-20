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
