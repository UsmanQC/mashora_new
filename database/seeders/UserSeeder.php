<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory(10)->create();
        /*\App\Models\User::factory(24)->create();
        $users = User::orderBy('id', 'DESC')->limit(24)->get();
        foreach ($users as $user) {
        	//Create own patient
        	$user->patients()->create(['name' => $user->name, 'birth_date' => $user->birth_date, 'relation' => 'Self', 'gender' => $user->gender]);
        	$patient = $user->patients()->first();
        	if(!empty($patient)){
        		$requestData = ['doctor_id' => 17, 'patient_id' => $patient->id, 'appointment_date' => '2023/02/25', 'time_start' => '09:30:00', 'time_end' => '09:55:00', 'health_issue' => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book."];
        		$appointment = $user->createAppointment($requestData);
        		for ($x = 0; $x <= 3; $x++) {
	        		$appointment->reports()->create(['report_path' => 'reports/F4LTrinHQr4DGyb3D74I26qW4.pdf']);
	        	}
        	}
        }*/
        //$user       = User::find(3);
        // $doctors    = Doctor::limit(1)->get();
        // foreach ($doctors as $doctor) {
        //     if($doctor->profile_photo_path != "") {
        //         $doctor->createThumbnailImage();
        //     }
        //     $doctor->createProfileImage();
        // }
    }
}
