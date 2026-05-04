<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = ['Buccal', 'Enteral', 'Inhalable', 'Infused', 'Intramuscular', 'Intramthecal', 'Intravenous'];
        $i = 0;
        foreach ($datas as $data) {$i++;
        	if(Route::where('title', $data)->doesntExist()){
        		Route::create(['title' => $data, 'title_ar' => $data, 'sort_order' => $i]);
        	}        	
        }
    }
}
