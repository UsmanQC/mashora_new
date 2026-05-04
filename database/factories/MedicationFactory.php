<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Medicine>
 */
class MedicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => 3,
            'name' => $this->faker->name,
            'dosage' => '0.5ml',
            'usage' => '1',
            'frequency' => '3',
            'duration_measurement' => 'days',
            'instructions' => $this->faker->sentence,
        ];
    }
}
