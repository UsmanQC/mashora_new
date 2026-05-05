<?php

namespace Database\Factories;

use App\Models\PatientMood;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientMood>
 */
class PatientMoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mood' => fake()->randomElement(['satisfied', 'neutral', 'happy', 'sad']),
            'comments' => fake()->boolean(40) ? fake()->sentence() : null,
            'date' => now()->timezone(config('app.timezone'))->toDateString(),
            'is_shared' => fake()->boolean(25),
        ];
    }
}
