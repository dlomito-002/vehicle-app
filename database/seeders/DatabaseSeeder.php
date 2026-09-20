<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Agent User',
            'email' => 'agent@example.com',
        ]);

        User::factory()->create([
            'name' => 'Pierre',
            'email' => 'pierre.mazariegos@carrousel.com.gt',
        ]);

        User::factory()->create([
            'name' => 'Luis',
            'email' => 'luis@carrousel.com.gt',
        ]);

        User::factory()->create([
            'name' => 'Diego Velasquez',
            'email' => 'diego2402alejandrov@gmail.com',
        ]);

        User::factory()->create([
            'name' => 'Rocio',
            'email' => 'rocio@carrousel.com.gt',
        ]);
        
        User::factory()->create([
            'name' => 'Ad',
            'email' => 'ad@gmail.com',
            'role' => 'admin',
            'password' => '123',
        ]);

        

        Vehicle::factory()->count(8)->create();
    }
}

// comunicacion.interna@carrousel.com.gt