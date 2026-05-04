<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Doctor::factory(50)->create();
        // $clinics = Doctor::get();
        // $sessionDays = SessionDay::orderBy('sort_order', 'ASC')->get()->pluck('id')->toArray();
        // foreach ($clinics as $clinic) {
        // 	if($clinic->doctors()->count() == 0){
        // 		echo $clinic_id = $clinic->id;
	    //     	\App\Models\Doctor::factory(5)->create();
	    //     	$clinic_doctors = Doctor::orderBy('id', 'DESC')->limit(5)->get();
	    //     	foreach ($clinic_doctors as $clinic_doctor) {
	    //     		$clinic_doctor->clinic_id = $clinic_id;
	    //     		$clinic_doctor->save();
	    //     	}
        // 	}
        // }
        // $doctors = Doctor::get();
        // foreach ($doctors as $doctor) {
        // 	if(empty($doctor->other_specialities)){
        // 		$specialities = Speciality::inRandomOrder()->limit(rand(2,5))->get()->pluck('id')->toArray();
        // 		$doctor->specialities()->sync($specialities);

        //         //Assign session days to doctor
        //         if(!empty($sessionDays)){
        //             $doctor->session_days()->sync($sessionDays);
        //             foreach ($doctor->session_days as $session_day) {
        //                 $start = strtotime('09:30');
        //                 $end = strtotime('12:00');
        //                 $minutes = ($end - $start) / 60;
        //                 $slots = floor($minutes)/25;
        //                 $time_start = '09:30';

        //                 $session_doctor_day = SessionDoctorDay::find($session_day->pivot->id);

        //                 for ($i=1; $i <= $slots; $i++) {
        //                     $end_at = date("H:i:s", strtotime('+25 minutes', strtotime($time_start)));
        //                     $session_doctor_day->slots()->create(['time_start' => $time_start, 'time_end' => $end_at, 'session_day_id' => $session_day->id, 'doctor_id' => $doctor->id]);
        //                     $time_start = $end_at;
        //                 }
        //             }
        //         }
        // 	}
        // }
    }
}
