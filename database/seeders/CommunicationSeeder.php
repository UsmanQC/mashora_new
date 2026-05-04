<?php

namespace Database\Seeders;

use App\Models\Communication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommunicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Communication::create([
            'communication' => 'chat',
            'title' => 'Chat',
            'title_ar' => 'Chat',
        ]);

        Communication::create([
            'communication' => 'voice_call',
            'title' => 'Voice Call',
            'title_ar' => 'Voice Call',
        ]);

        Communication::create([
            'communication' => 'video_call',
            'title' => 'Video Call',
            'title_ar' => 'Video Call',
        ]);
    }
}
