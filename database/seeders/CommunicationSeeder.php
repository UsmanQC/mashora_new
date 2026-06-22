<?php

namespace Database\Seeders;

use App\Models\Communication;
use Illuminate\Database\Seeder;

class CommunicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $communications = [
            [
                'communication' => 'chat',
                'title' => 'Chat',
                'title_ar' => 'Chat',
            ],
            [
                'communication' => 'voice_call',
                'title' => 'Voice Call',
                'title_ar' => 'Voice Call',
            ],
            [
                'communication' => 'video_call',
                'title' => 'Video Call',
                'title_ar' => 'Video Call',
            ],
        ];

        foreach ($communications as $communication) {
            Communication::query()->updateOrCreate(
                ['communication' => $communication['communication']],
                [
                    'title' => $communication['title'],
                    'title_ar' => $communication['title_ar'],
                ],
            );
        }
    }
}
