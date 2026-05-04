<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Frequency;
class FrequencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = ['Every 4 hours', 'Daily', 'Every hour', 'Three times a day before meals', 'As needed', 'Once per day', 'Before dinner', 'Every day', 'Every other hour'];
        $i = 0;
        foreach ($datas as $data) {$i++;
        	if(Frequency::where('title', $data)->doesntExist()){
        		Frequency::create(['title' => $data, 'title_ar' => $data, 'sort_order' => $i]);
        	}        	
        }
    }
}
