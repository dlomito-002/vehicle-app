<?php

namespace App\Enums;

/**
 * The "ESTADO GENERAL DEL VEHÍCULO" checklist from the paper bitácora —
 * 12 components, each rated with ConditionStatus, optionally with a
 * supporting photo.
 */
enum ConditionComponent: string
{
    case Chasis = 'chasis';
    case Carroceria = 'carroceria';
    case Pintura = 'pintura';
    case Vidrios = 'vidrios';
    case Tapiceria = 'tapiceria';
    case Logos = 'logos';
    case Emblemas = 'emblemas';
    case Luces = 'luces';
    case PideVias = 'pide_vias';
    case Polarizado = 'polarizado';
    case Llantas = 'llantas';
    case Bateria = 'bateria';

    public function label(): string
    {
        return match ($this) {
            self::Chasis => 'Chasis',
            self::Carroceria => 'Carrocería',
            self::Pintura => 'Pintura',
            self::Vidrios => 'Vidrios',
            self::Tapiceria => 'Tapicería',
            self::Logos => 'Logos',
            self::Emblemas => 'Emblemas',
            self::Luces => 'Luces',
            self::PideVias => 'Pide vías',
            self::Polarizado => 'Polarizado',
            self::Llantas => 'Llantas',
            self::Bateria => 'Batería',
        };
    }
}
