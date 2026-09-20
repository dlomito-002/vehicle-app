<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use Closure;

/**
 * Shared email handling for the user create/update Form Requests.
 *
 * Login looks a user up by `mb_strtolower(trim($email))` (see
 * App\Http\Controllers\Auth\LoginController), so an address stored with any
 * uppercase character belongs to an account nobody can ever log into — on
 * SQLite a TEXT comparison is case-sensitive. Normalizing here keeps what
 * user management writes and what login reads in the same shape.
 */
trait ValidatesUserEmail
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => mb_strtolower(trim($email))]);
        }
    }

    /**
     * Case-insensitive uniqueness. Rule::unique() compares with `=`, which
     * on SQLite would let "Agente@example.com" through next to an existing
     * "agente@example.com" and create two accounts for one address.
     */
    protected function uniqueEmailRule(?int $ignoreUserId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreUserId): void {
            if (! is_string($value)) {
                return;
            }

            $taken = User::query()
                ->whereRaw('lower(email) = ?', [mb_strtolower($value)])
                ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
                ->exists();

            if ($taken) {
                $fail('Ya existe un usuario registrado con ese correo electrónico.');
            }
        };
    }
}
