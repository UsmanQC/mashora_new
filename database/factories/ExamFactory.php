<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->text,
            'title_ar' => $this->faker->text,
            'estimate_time' => rand(5, 6),
        ];
    }

        /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Exam $exam) {
            // ...
        })->afterCreating(function (Exam $exam) {
            for ($i=0; $i < 10; $i++) {
                $question = $exam->questions()->create([
                                'title' => $this->faker->text(40),
                                'title_ar' => $this->faker->text(40),
                            ]);
                $rand = rand(0, 3);
                $question->options()->create([
                        'title' => 'Not at all',
                        'title_ar' => 'Not at all',
                        'value' => $rand == 0 ? 1 : 0,
                    ]);
                $question->options()->create([
                        'title' => 'Several days',
                        'title_ar' => 'Several days',
                        'value' => $rand == 1 ? 1 : 0,
                    ]);
                $question->options()->create([
                        'title' => 'More than half the days',
                        'title_ar' => 'More than half the days',
                        'value' => $rand == 2 ? 1 : 0,
                    ]);
                $question->options()->create([
                        'title' => 'Nearly every day',
                        'title_ar' => 'Nearly every day',
                        'value' => $rand == 2 ? 1 : 0,
                    ]);
            }
        });
    }
}
