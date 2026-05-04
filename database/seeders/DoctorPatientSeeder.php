<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorPatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i=1; $i < 20; $i++) {
            DB::table('doctor_patient')->insert([
                'doctor_id' => 51,
                'user_id' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
