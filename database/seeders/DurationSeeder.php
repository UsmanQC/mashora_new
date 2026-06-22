<?php

namespace Database\Seeders;

use App\Models\Duration;
use Illuminate\Database\Seeder;

class DurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $durations = [
            [
                'duration' => 15,
                'title' => '15 minutes',
                'title_ar' => '15 دقيقة',
            ],
            [
                'duration' => 30,
                'title' => '30 minutes',
                'title_ar' => '30 دقيقة',
            ],
            [
                'duration' => 45,
                'title' => '45 minutes',
                'title_ar' => '45 دقيقة',
            ],
            [
                'duration' => 60,
                'title' => '60 minutes',
                'title_ar' => '60 دقيقة',
            ],
        ];

        foreach ($durations as $duration) {
            Duration::query()->updateOrCreate(
                ['duration' => $duration['duration']],
                [
                    'title' => $duration['title'],
                    'title_ar' => $duration['title_ar'],
                ],
            );
        }
    }
}
