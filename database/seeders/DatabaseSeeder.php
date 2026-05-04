<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        // \App\Models\NotificationFactory::factory(20)->create();
        $this->call(AdminSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(SpecialitySeeder::class);
        $this->call(DegreeSeeder::class);

        //$this->call(DoseUnitSeeder::class);
        //$this->call(RouteSeeder::class);
        $this->call(FrequencySeeder::class);
        //$this->call(PrimaryDiagnosisSeeder::class);
       // $this->call(CountrySeeder::class);
        $this->call(DurationSeeder::class);
        $this->call(InstantAppointmentPricesSeeder::class);
        $this->call(CommunicationSeeder::class);
        $this->call(ExamSeeder::class);
        $this->call(ThoughtsSeeder::class);
        $this->call(InstantAppointmentPricesSeeder::class);
        // $this->call(DoctorSeeder::class);
    }
}
