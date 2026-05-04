<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Appointment::factory(10)->create();
        // $appointments = Appointment::get();
        // foreach ($appointments as $appointment) {
        // 	$starts_date = date('Y-m-d', strtotime($appointment->appointment_date)).' '.date('H:i:s', strtotime($appointment->time_start));
        // 	$appointment->starts_date = $starts_date;
        // 	$appointment->save();
        // }
    }
}
