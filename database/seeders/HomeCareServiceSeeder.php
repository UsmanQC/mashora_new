<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\HomeCareService;

class HomeCareServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	HomeCareService::truncate();
        $services = ["Nurse Visit" , "Physiotherapy", "Laboratory"];
        foreach ($services as $service) {
			HomeCareService::create(['title' => $service, 'title_ar' => $service, 'status' => 1, 'manually_complete' => 1, 'parent_id' => NULL, 'price' => 100]);        	
        }
    }
}
