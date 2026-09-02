<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'make' => fake()->randomElement(['Hyundai', 'Toyota', 'Kia', 'Chevrolet', 'Nissan']),
            'model' => fake()->randomElement(['Accent', 'Corolla', 'Rio', 'Spark', 'Sentra']),
            'license_plate' => strtoupper(fake()->bothify('C###???')),
        ];
    }
}
