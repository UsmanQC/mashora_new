<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // $scheduledAt = $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d H:i:s');
        $scheduledAt = $this->faker->dateTimeBetween('now', 'now')->format('Y-m-d H:i:s');
        //$scheduledAt = $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d H:i:s');
        return [
            'appointment_number' => rand(1000,2000),
            'doctor_id' => 1,
            'user_id' => 1,
            'scheduled_at' => $scheduledAt,
            'appointment_date' => date('Y-m-d', strtotime($scheduledAt)),
            'start_time' => $this->faker->date('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'duration' => $this->faker->randomElement([15, 30, 45, 60]),
            //'note' => $this->faker->text(5),
            'patient_name' => $this->faker->name,
            // 'patient_birth_date' => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            // 'patient_gender' => $this->faker->randomElement(['male', 'female']),
            'patient_phone' => $this->faker->phoneNumber,
            'amount'=>$this->faker->randomFloat( 2, 100,  1000),
            'total'=>$this->faker->randomFloat( 2, 100,  1000),
            'status' => $this->faker->randomElement(['new', 'new', 'in_process', 'completed', 'completed', 'cancelled']),
        ];
    }
}
