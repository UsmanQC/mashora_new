<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SessionDay;
use Illuminate\Support\Str;

class SessionDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    { 
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $i = 0;
        foreach ($days as $day) {$i++;
        	if(SessionDay::where('day', $day)->doesntExist()){
        		SessionDay::create(['day' => $day, 'day_ar' => $day, 'slug' => Str::slug($day), 'sort_order' => $i]);
        	}        	
        }
    }
}
