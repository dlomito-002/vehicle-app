<?php

namespace App\Mail;

use App\Models\VehicleDelivery;
use App\Models\VehicleReception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies vehicle managers that a vehicle was received (reception) or
 * returned (delivery). One mailable for both movements; the view branches
 * on the record type.
 */
class VehicleMovementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VehicleReception|VehicleDelivery $movement,
    ) {}

    public function isReception(): bool
    {
        return $this->movement instanceof VehicleReception;
    }

    public function envelope(): Envelope
    {
        $plate = $this->movement->vehicle->license_plate;

        return new Envelope(
            subject: $this->isReception()
                ? "Control de Vehículos — Recepción de vehículo: {$plate}"
                : "Control de Vehículos — Devolución de vehículo: {$plate}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vehicle-movement',
        );
    }
}
