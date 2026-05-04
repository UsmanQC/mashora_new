<?php

namespace Database\Seeders;

use App\Models\Appointment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppointmentCheckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appointmentsWithoutTemporary = Appointment::doesntHave('temporaryAppointment')->get()->pluck('id')->implode(','); 
        \Log::info("appointmentsWithoutTemporary: ".$appointmentsWithoutTemporary);
    }
}
