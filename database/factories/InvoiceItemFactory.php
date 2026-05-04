<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => 'Appointments',
            'quantity' => 1,
            'total' => $this->faker->randomElement([1000, 2000, 3000, 5500]),
            'invoice_id' => Invoice::factory(),
        ];
    }
}
