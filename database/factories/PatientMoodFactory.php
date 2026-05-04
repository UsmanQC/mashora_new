<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientMood>
 */
class PatientMoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'mood' => $this->faker->randomElement(['satisfied',
                            'neutral',
                            'disappointed',
                            'anxiety',
                            'happy',
                            'sad',
                            'tired',
                            'angry']),
            'comments' => $this->faker->sentence,
            'date' => $this->faker->date('Y-m-d'),
        ];
    }
}
