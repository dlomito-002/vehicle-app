<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * A single-use, expiring, hashed one-time code used to complete the
 * email-based login flow (see App\Http\Controllers\Auth\LoginController).
 * Not tied to a `users` row by foreign key since a code can be requested
 * for an email that turns out not to exist without leaking that fact.
 */
class LoginVerificationCode extends Model
{
    /** How long a generated code stays valid. */
    public const TTL_MINUTES = 10;

    /** Invalid attempts allowed against a single code before it's locked out. */
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'email',
        'code_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
            'consumed_at' => 'datetime',
        ];
    }

    /**
     * Generate a new 6-digit code for the given email, invalidating any
     * previously issued, still-active codes for that email so only one
     * code is ever valid at a time. Returns the model together with the
     * plain-text code (needed once, to send the email — never persisted).
     *
     * @return array{0: self, 1: string}
     */
    public static function generateFor(string $email): array
    {
        static::where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $plainCode = (string) random_int(100000, 999999);

        $model = static::create([
            'email' => $email,
            'code_hash' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return [$model, $plainCode];
    }

    /** The active (not consumed, not expired) code for an email, if any. */
    public static function activeFor(string $email): ?self
    {
        return static::where('email', $email)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function isLockedOut(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function matches(string $plainCode): bool
    {
        return Hash::check($plainCode, $this->code_hash);
    }

    public function registerFailedAttempt(): void
    {
        $this->increment('attempts');
    }

    public function markConsumed(): void
    {
        $this->update(['consumed_at' => now()]);
    }

    /** Helper for callers that need a fixed-length numeric string, e.g. for input maxlength. */
    public static function codeLength(): int
    {
        return 6;
    }
}
