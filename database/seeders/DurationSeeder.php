<?php

namespace Database\Seeders;

use App\Models\Duration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //DB::table('durations')->truncate();

        Duration::create([
            'duration' => 15,
            'title' => '15 minutes',
            'title_ar' => '15 دقيقة',
        ]);
        Duration::create([
            'duration' => 30,
            'title' => '30 minutes',
            'title_ar' => '30 دقيقة',
        ]);
        Duration::create([
            'duration' => 45,
            'title' => '45 minutes',
            'title_ar' => '45 دقيقة',
        ]);
        Duration::create([
            'duration' => 60,
            'title' => '60 minutes',
            'title_ar' => '60 دقيقة',
        ]);
    }
}
