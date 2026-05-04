<?php

namespace Database\Factories;

use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DoctorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Doctor::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'name_ar' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'phone' => str_replace(' ', '', $this->faker->unique()->phoneNumber),
            'password' => bcrypt(12345678), // password
            'remember_token' => Str::random(10),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'degree_id' => rand(1, 3),
            'speciality_id' => rand(1, 15),
            'experience' => rand(1, 20),
            'about' => $this->faker->text,
            'about_ar' => $this->faker->text,
            'spoken_languages' => $this->faker->randomElement(['ar', 'en', 'ar_en']),
            'profile_photo_path' => null,
            'status' => 'approved',
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Doctor $doctor) {
            // ...
        })->afterCreating(function (Doctor $doctor) {
            // Initialize an empty array to store the times
            $startTimesArray = [];

            // Start time (12 am)
            $startFromTime = Carbon::createFromTime(0, 0, 0);

            // End time (1 pm)
            $endFromTime = Carbon::createFromTime(13, 0, 0);

            // Loop through each hour from start time to end time
            while ($startFromTime < $endFromTime) {
                // Add the current time to the array
                $startTimesArray[] = $startFromTime->format('H:i:s');

                // Increment the time by one hour
                $startFromTime->addHour();
            }

            $endTimesArray = [];

            // Start time (2 pm)
            $startToTime = Carbon::createFromTime(14, 0, 0);

            // End time (2 pm)
            $endToTime = Carbon::createFromTime(20, 0, 0);
            while ($startToTime < $endToTime) {
                // Add the current time to the array
                $endTimesArray[] = $startToTime->format('H:i:s');

                // Increment the time by one hour
                $startToTime->addHour();
            }

            $workingDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            if (!empty($workingDays)) {
                foreach ($workingDays as $dayOfWeek) {
                    $workingDay = $doctor->workingDays()->updateOrCreate(
                        ['doctor_id' => $doctor->id, 'day_of_week' => $dayOfWeek],
                        [
                            'day_of_week' => $dayOfWeek,
                            'is_working' => 1
                        ]
                    );

                    $workingDay->workingHours()->create([
                        'start_time' => $this->faker->randomElement($startTimesArray),
                        'end_time' => $this->faker->randomElement($endTimesArray),
                    ]);
                }
            }

            $durationArr = [15, 30, 45, 60];
            $durationPrices = [15 => 100, 30 => 200, 45 => 300, 60 => 400];

            $durations = Arr::map(array_flip($durationArr), function (string $value, string $key) use ($durationPrices) {
                return ['price' => $durationPrices[$key]];
            });

            if (!empty($durations)) {
                $doctor->durations()->sync($durations);
            }

            $doctorCommunications = ['chat', 'video_call', 'voice_call'];

            //Save Communication
            if (!empty($doctorCommunications)) {
                $doctor->communications()->sync($doctorCommunications);
            }

            $doctor->profile_completed = 1;
            $doctor->save();
        });
    }
}
