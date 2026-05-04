<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrimaryDiagnosis;
class PrimaryDiagnosisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = ['Puff', 'Tablete', 'Capsule', 'Sachet', 'Suppository', 'Syringe', 'Dispersible', 'Drop'];
        $i = 0;
        foreach ($datas as $data) {$i++;
        	if(PrimaryDiagnosis::where('title', $data)->doesntExist()){
        		PrimaryDiagnosis::create(['title' => $data, 'title_ar' => $data, 'sort_order' => $i]);
        	}        	
        }
    }
}
