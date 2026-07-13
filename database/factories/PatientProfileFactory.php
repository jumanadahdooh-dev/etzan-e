<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientProfile>
 */
class PatientProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->patient(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'city' => fake()->city(),
            'height' => fake()->randomFloat(2, 150, 195),
            'weight' => fake()->randomFloat(2, 50, 120),
            'notes' => null,
            'profile_completed' => true,
            'health_goal' => fake()->randomElement(['lose_weight', 'gain_weight', 'maintain_weight']),
            'activity_level' => fake()->randomElement(['low', 'moderate', 'high']),
            'meals_per_day' => fake()->numberBetween(2, 5),
            'sleep_hours' => fake()->randomFloat(1, 5, 9),
            'water_cups' => fake()->numberBetween(4, 12),
            'preferred_doctor_gender' => 'any',
            'preferred_consultation_type' => 'any',
        ];
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_completed' => false,
            'health_goal' => null,
            'activity_level' => null,
        ]);
    }
}
