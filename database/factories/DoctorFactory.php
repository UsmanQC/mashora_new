<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'name_ar' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('9665########'),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'spoken_languages' => fake()->randomElement(['ar', 'en', 'ar_en']),
            'profile_photo_path' => null,
            'status' => 'approved',
            'profile_completed' => true,
            'active_status' => false,
        ];
    }

    public function pendingOnboarding(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_completed' => false,
            'status' => 'pending',
        ]);
    }
}
