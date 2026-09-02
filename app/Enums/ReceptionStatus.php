<?php

namespace App\Enums;

enum ReceptionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta (pendiente de devolución)',
            self::Closed => 'Cerrada (devuelta)',
        };
    }
}
