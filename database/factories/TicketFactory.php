<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-'.now()->format('Ymd').'-0001',
            'creator_type' => User::class,
            'creator_id' => User::factory(),
            'ticket_category_id' => TicketCategory::factory(),
            'subject' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'status' => Ticket::STATUS_OPEN,
        ];
    }
}
