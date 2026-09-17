<?php

namespace App\Mail;

use App\Models\VehicleMaintenanceSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VehicleMaintenanceSchedule $schedule,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fleet Desk — Alerta de mantenimiento: '.$this->schedule->vehicle->displayName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-alert',
        );
    }
}
