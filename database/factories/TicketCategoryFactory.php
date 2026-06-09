<?php

namespace Database\Factories;

use App\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketCategory>
 */
class TicketCategoryFactory extends Factory
{
    protected $model = TicketCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'name_ar' => fake()->words(3, true),
            'audience' => 'patient',
            'is_active' => true,
            'sort_order' => 10,
        ];
    }

    public function doctor(): static
    {
        return $this->state(fn (): array => ['audience' => 'doctor']);
    }
}
