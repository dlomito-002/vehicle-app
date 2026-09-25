<?php

namespace App\Support;

use App\Mail\VehicleMovementMail;
use App\Models\VehicleDelivery;
use App\Models\VehicleReception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the Fleet Desk recipients (see NotificationRecipients) about a
 * persisted reception or delivery. Best effort: the movement is already
 * saved, so a mail failure is logged and never surfaces to the user.
 */
class VehicleMovementNotifier
{
    public static function notify(VehicleReception|VehicleDelivery $movement): void
    {
        try {
            $recipients = NotificationRecipients::emails();

            if ($recipients === []) {
                return;
            }

            $movement->loadMissing(['vehicle', 'documentation']);

            Mail::to($recipients)->send(new VehicleMovementMail($movement));
        } catch (Throwable $e) {
            Log::error('Vehicle movement email failed.', [
                'type' => $movement::class,
                'id' => $movement->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
