<?php

namespace Database\Seeders;

use App\Models\Speciality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Speciality::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $specialities = [
            [
                'title'    => 'Obsessive',
                'title_ar' => 'الوسواس',
            ],
            [
                'title'    => 'Substance Abuse',
                'title_ar' => 'علاج الإدمان',
            ],
            [
                'title'    => 'Relationships problems',
                'title_ar' => 'مشاكل العلاقات',
            ],
            [
                'title'    => 'Lack of appreciation and care',
                'title_ar' => 'عدم الاهتمام وتقدير الذات',
            ],
            [
                'title'    => 'Doubt and jealousy',
                'title_ar' => 'الشك والغيرة',
            ],
            [
                'title'    => 'Emotional vacuum',
                'title_ar' => 'الفراغ العاطفي',
            ],
            [
                'title'    => 'Social phobia',
                'title_ar' => 'الرهاب الاجتماعي',
            ],
            [
                'title'    => 'Panic',
                'title_ar' => 'الهلع',
            ],
            [
                'title'    => 'Schizophrenia Disorder',
                'title_ar' => 'الفصام',
            ],
            [
                'title'    => 'Mood disorder',
                'title_ar' => 'اضطراب المزاج',
            ],
            [
                'title'    => 'Pelage',
                'title_ar' => 'الوسواس',
            ],
            [
                'title'    => 'Fear of death',
                'title_ar' => 'الخوف من الموت',
            ],
            [
                'title'    => 'Disorder',
                'title_ar' => 'اضطراب الهوية الجنسية',
            ],
            [
                'title'    => 'Delusion of disease',
                'title_ar' => 'توهم المرض',
            ],
            [
                'title'    => 'Suicidal thoughts',
                'title_ar' => 'التفكير بالانتحار',
            ],
            [
                'title'    => 'Depression disorder',
                'title_ar' => 'الاكتئاب',
            ],
            [
                'title'    => 'Dealing with stress and anxiety',
                'title_ar' => 'القلق والتوتر',
            ],
            [
                'title'    => 'Bipolar disorder',
                'title_ar' => 'اضطراب ثنائي القطب',
            ],
            [
                'title'    => 'Emotional intelligence',
                'title_ar' => 'الذكاء العاطفي',
            ],
            [
                'title'    => 'Isolation and introversion',
                'title_ar' => 'العزلة والانطواء',
            ],
            [
                'title'    => 'Dealing with self blame',
                'title_ar' => 'التعامل مع لوم الذات',
            ],
            [
                'title'    => 'Sleep disorders',
                'title_ar' => 'اضطرابات النوم',
            ],
            [
                'title'    => 'Psychological stress',
                'title_ar' => 'الضغوط النفسية',
            ],
            [
                'title'    => 'Low self esteem',
                'title_ar' => 'ضعف تقدير الذات',
            ],
            [
                'title'    => 'Post-traumatic stress disorder',
                'title_ar' => 'اضطراب مابعد الصدمة',
            ],
            [
                'title'    => 'Anorexia',
                'title_ar' => 'فقدان الشهية',
            ],
        ];

        foreach($specialities as $item) {
            Speciality::create($item);
        }

    }
}
