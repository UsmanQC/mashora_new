<?php

namespace Database\Seeders;

use App\Models\Drug;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DrugStrengthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $drugs = Drug::where('flag', 0)->orderBy('id', 'ASC')->get();

        foreach($drugs as $drug){
            $strength = trim($drug->strength);
            $strengthUnit = trim($drug->strength_unit);
            if (strpos($strength, '/') === false && strpos($strength, ',') === false && strpos($strengthUnit, '/') === false && strpos($strengthUnit, ',') === false) {
                $drug->strengths()->create(['dosage' => $strength.' '.$strengthUnit]);
                $drug->flag = 1;
                $drug->save();
                // echo "Single."; die;
            } elseif(strpos($strength, '/') === false && strpos($strength, ',') === false && strpos($strengthUnit, '/') !== false) {
                $units = explode("/", $strengthUnit);
                foreach($units as $unit){
                    $drug->strengths()->create(['dosage' => $strength.' '.trim($unit)]);
                }
                $drug->flag = 1;
                $drug->save();
            } elseif(strpos($strength, '/') === false && strpos($strength, ',') === false && strpos($strengthUnit, '/') !== false) {
                $units = explode("/", $strengthUnit);
                foreach($units as $unit){
                    $drug->strengths()->create(['dosage' => $strength.' '.trim($unit)]);
                }
                $drug->flag = 1;
                $drug->save();
            } else{
                $strengths = explode(",", $strength);
                $units = explode(",", $strengthUnit);
                foreach($strengths as $i => $item){
                    if(isset($units[$i]) && $units[$i] != ''){
                        $drug->strengths()->create(['dosage' => $item.' '.trim($units[$i])]);
                    }
                }
                $drug->flag = 1;
                $drug->save();
            }
        }
    }
}
