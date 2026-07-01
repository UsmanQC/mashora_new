<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => fake()->sentence().'?',
            'question_ar' => 'سؤال '.fake()->word().'؟',
            'answer' => fake()->paragraph(),
            'answer_ar' => 'إجابة تجريبية.',
            'category' => fake()->randomElement(['booking', 'privacy', 'services']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
