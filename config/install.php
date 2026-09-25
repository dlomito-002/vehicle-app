<?php

/*
| Settings read by `php artisan app:install` and Database\Seeders\InitialUsersSeeder.
| None of these are secrets. Login is by email one-time code, so no password
| is ever configured or stored for the initial users.
*/
return [

    // First administrator created on a fresh installation (optional when
    // the project's team users are seeded, since they include an admin).
    'admin_name' => env('INSTALL_ADMIN_NAME', 'Administrador'),
    'admin_email' => env('INSTALL_ADMIN_EMAIL'),

    // Also create the project's regular team users (see InitialUsersSeeder).
    'seed_team' => (bool) env('INSTALL_SEED_TEAM', true),

];
