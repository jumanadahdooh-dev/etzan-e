<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DoctorProfile>
 */
class DoctorProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'workplace' => fake()->company(),
            'license_number' => fake()->unique()->numerify('LIC-#####'),
            'years_experience' => fake()->numberBetween(1, 30),
            'bio' => fake()->paragraph(),
            'photo_path' => null,
            'is_available' => true,
        ];
    }
}
