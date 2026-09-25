<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Users needed to log in to a brand-new installation. Safe to run any number
 * of times: users are matched by email and existing ones are never modified.
 *
 * Login is by email one-time code, so no password is configured anywhere;
 * the required `password` column receives a random, unknown value.
 */
class InitialUsersSeeder extends Seeder
{
    /** Project team users (email => [name, role]). */
    private const TEAM = [
        'diego2402alejandrov@gmail.com' => ['Diego Velasquez', UserRole::Admin],
        'pierre.mazariegos@carrousel.com.gt' => ['Pierre', UserRole::Agent],
        'luis@carrousel.com.gt' => ['Luis', UserRole::Agent],
        'rocio@carrousel.com.gt' => ['Rocio', UserRole::Agent],
        'marlon@carrousel.com.gt' => ['Marlon', UserRole::Agent],
    ];

    public function run(): void
    {
        if (config('install.seed_team')) {
            foreach (self::TEAM as $email => [$name, $role]) {
                $this->ensureUser($email, $name, $role);
            }
        }

        if ($adminEmail = config('install.admin_email')) {
            $this->ensureUser($adminEmail, config('install.admin_name') ?: 'Administrador', UserRole::Admin);
        }
    }

    private function ensureUser(string $email, string $name, UserRole $role): void
    {
        $email = mb_strtolower(trim($email));

        if (User::where('email', $email)->exists()) {
            return;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => Str::random(40), 'role' => $role]);
        $user->email_verified_at = now();
        $user->save();
    }
}
