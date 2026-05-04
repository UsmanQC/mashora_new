<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Degree;
class DegreeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Degree::truncate();

        Degree::create([
            'title' 	=> 'Doctor (Specialist)',
            'title_ar'  => 'طبيب (أخصائي نفسي)',
            'status'  => 1,
        ]);

        Degree::create([
            'title' 	=> 'Doctor (Consultant)',
            'title_ar'  => 'طبيب (استشاري نفسي)',
            'status'  => 1,
        ]);

        Degree::create([
            'title' 	=> 'Non-Doctor (Therapist)',
            'title_ar'  => 'غير طبيب (أخصائي علم نفس)',
            'status'  => 1,
        ]);
    }
}
