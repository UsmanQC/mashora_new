<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_name' => $this->faker->unique()->name,
            'rating' => $this->faker->randomElement([1, 2, 3, 4, 5]),
            'comments' => $this->faker->sentence,
            'doctor_id' => 53,
            'user_id' => rand(1,20),
            'appointment_id' => rand(2,50),
        ];
    }
}
