<?php

namespace App\Enums;

/**
 * The "CHEQUEO GENERAL" equipment checklist from the paper bitácora —
 * 25 items, each answered Sí/No, optionally with a supporting photo.
 * (Aire acondicionado se eliminó en septiembre 2026 a solicitud del cliente.)
 */
enum EquipmentItem: string
{
    case Herramientas = 'herramientas';
    case Triangulos = 'triangulos';
    case Alfombras = 'alfombras';
    case Remolcador = 'remolcador';
    case Parrilla = 'parrilla';
    case Sunroof = 'sunroof';
    case CopiaTarjetaCirculacion = 'copia_tarjeta_circulacion';
    case LlantaRepuesto = 'llanta_repuesto';
    case Bocinas = 'bocinas';
    case RetrovisorIzquierdo = 'retrovisor_izquierdo';
    case TaponCombustible = 'tapon_combustible';
    case Radio = 'radio';
    case CopiaPapelesSeguro = 'copia_papeles_seguro';
    case RetrovisorInterior = 'retrovisor_interior';
    case LlaveChuchos = 'llave_chuchos';
    case LlaveArranque = 'llave_arranque';
    case RetrovisorDerecho = 'retrovisor_derecho';
    case LlavePortezuela = 'llave_portezuela';
    case Defensas = 'defensas';
    case Alarma = 'alarma';
    case Tricket = 'tricket';
    case Platos = 'platos';
    case Aros = 'aros';
    case Antena = 'antena';
    case Extinguidor = 'extinguidor';
    // Removed: AireAcondicionado (septiembre 2026, solicitud del cliente).

    public function label(): string
    {
        return match ($this) {
            self::Herramientas => 'Herramientas',
            self::Triangulos => 'Triángulos',
            self::Alfombras => 'Alfombras',
            self::Remolcador => 'Remolcador',
            self::Parrilla => 'Parrilla',
            self::Sunroof => 'Sunroof',
            self::CopiaTarjetaCirculacion => 'Copia tarjeta de circulación',
            self::LlantaRepuesto => 'Llanta de repuesto',
            self::Bocinas => 'Bocinas',
            self::RetrovisorIzquierdo => 'Retrovisor izquierdo',
            self::TaponCombustible => 'Tapón de combustible',
            self::Radio => 'Radio',
            self::CopiaPapelesSeguro => 'Copia papeles del seguro',
            self::RetrovisorInterior => 'Retrovisor interior',
            self::LlaveChuchos => 'Llave de chuchos',
            self::LlaveArranque => 'Llave de arranque',
            self::RetrovisorDerecho => 'Retrovisor derecho',
            self::LlavePortezuela => 'Llave de portezuela',
            self::Defensas => 'Defensas',
            self::Alarma => 'Alarma',
            self::Tricket => 'Tricket (gato)',
            self::Platos => 'Platos',
            self::Aros => 'Aros',
            self::Antena => 'Antena',
            self::Extinguidor => 'Extinguidor',
        };
    }
}
