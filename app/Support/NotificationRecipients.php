<?php

namespace App\Support;

use App\Models\User;

/**
 * Resolves who receives Fleet Desk notification emails (Help requests and
 * maintenance alerts).
 *
 * Recipients are the users an admin marked with "Recibir correos de Fleet
 * Desk" in User Management. VEHICLE_MANAGER_EMAIL (config
 * 'vehicle.manager_email') is only a deployment fallback: it is used when
 * no user is selected, so existing installs keep receiving emails until an
 * admin configures recipients. It is never merged with the selected users.
 */
class NotificationRecipients
{
    /**
     * @return list<string> Unique, case-insensitively de-duplicated addresses;
     *                      empty when nothing is configured.
     */
    public static function emails(): array
    {
        $emails = User::query()
            ->where('receives_notification_emails', true)
            ->orderBy('id')
            ->pluck('email')
            ->all();

        if ($emails === []) {
            $fallback = config('vehicle.manager_email');
            $emails = $fallback ? [$fallback] : [];
        }

        return collect($emails)
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->unique(fn (string $email) => mb_strtolower($email))
            ->values()
            ->all();
    }
}
