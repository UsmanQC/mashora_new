<?php

namespace Database\Seeders;

use App\Models\InstantAppointmentPrice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstantAppointmentPricesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'duration' => 15,
                'price' => 120
            ],
            [
                'duration' => 30,
                'price' => 160
            ],
            [
                'duration' => 45,
                'price' => 185
            ],
            [
                'duration' => 60,
                'price' => 199
            ],
        ];

        foreach ($data as $price) {
            InstantAppointmentPrice::updateOrCreate(['duration' => $price['duration']], $price);
        }
    }
}
