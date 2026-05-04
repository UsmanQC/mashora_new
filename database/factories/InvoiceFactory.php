<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => '53-'.date('Y') . '/' . $this->faker->randomElement(['01', '05', '06', '07', '08', '09', '10', '11']),
            'doctor_id' => 53,
            'issue_date' => $this->faker->date('Y-m-d'),
            'from_date' => $this->faker->date('Y-m-d'),
            'to_date' => $this->faker->time('Y-m-d'),
            'total_amount' => $this->faker->randomElement([1000, 2000, 3000, 5500]),
            'paid_at' => $this->faker->date('Y-m-d H:i:s'),
            'payment_status' => $this->faker->randomElement(['paid', 'unpaid']),
        ];
    }
}
