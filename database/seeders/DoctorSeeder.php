<?php

namespace Database\Seeders;

use App\Models\Communication;
use App\Models\Degree;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\Speciality;
use App\Models\WorkingDay;
use App\Models\WorkingHour;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public const SEEDED_DOCTOR_COUNT = 10;

    public const TEST_DOCTOR_PHONE = '966511122233';

    public const TEST_DOCTOR_PASSWORD = 'password';

    public const TEST_DOCTOR_PRIMARY_SPECIALITY_ID = 1;

    public const SEEDED_DOCTOR_SPECIALITY_COUNT = 26;

    /**
     * @return list<string>
     */
    public static function profileDoctorPhones(): array
    {
        return [
            '966511122233',
            '966511122234',
            '966511122235',
            '966511122236',
            '966511122237',
            '966511122238',
            '966511122239',
            '966511122240',
            '966511122241',
            '966511122242',
        ];
    }

    /**
     * @return list<int>
     */
    public static function allSpecialityIds(): array
    {
        return range(1, self::SEEDED_DOCTOR_SPECIALITY_COUNT);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $degrees = Degree::query()->where('status', true)->get()->keyBy('title');
        $durationPrices = $this->durationPrices();
        $communicationKeys = Communication::query()
            ->whereIn('communication', ['chat', 'video_call'])
            ->pluck('communication')
            ->all();

        $seededDoctors = [];

        foreach ($this->doctorProfiles() as $profile) {
            $degree = $degrees->get($profile['degree_title']) ?? $degrees->first();

            $doctor = Doctor::query()->updateOrCreate(
                ['phone' => $profile['phone']],
                [
                    'name' => $profile['name'],
                    'name_ar' => $profile['name_ar'],
                    'email' => $profile['email'],
                    'password' => self::TEST_DOCTOR_PASSWORD,
                    'gender' => $profile['gender'],
                    'spoken_languages' => $profile['spoken_languages'],
                    'status' => 'approved',
                    'profile_completed' => true,
                    'accept_instant_appointment' => true,
                    'degree_id' => $degree?->id,
                    'experience' => $profile['experience'],
                    'about' => $profile['about'],
                    'about_ar' => $profile['about_ar'],
                ],
            );

            foreach ($durationPrices as $minutes => $price) {
                $duration = Duration::query()->where('duration', $minutes)->first();

                if ($duration === null) {
                    continue;
                }

                $doctor->durations()->syncWithoutDetaching([
                    $duration->duration => ['price' => $price],
                ]);
            }

            if ($communicationKeys !== []) {
                $doctor->communications()->sync($communicationKeys);
            }

            $this->seedWorkingDays($doctor);

            $seededDoctors[] = $doctor;
        }

        $this->seedAllSpecialitiesForDoctors($seededDoctors);
    }

    /**
     * @param  list<Doctor>  $doctors
     */
    private function seedAllSpecialitiesForDoctors(array $doctors): void
    {
        $availableSpecialityIds = Speciality::query()
            ->whereBetween('id', [1, self::SEEDED_DOCTOR_SPECIALITY_COUNT])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($availableSpecialityIds === []) {
            return;
        }

        foreach ($doctors as $doctor) {
            $doctor->specialities()->sync($availableSpecialityIds);
            $doctor->updateQuietly(['speciality_id' => $availableSpecialityIds[0]]);
        }
    }

    /**
     * @return list<array{
     *     phone: string,
     *     name: string,
     *     name_ar: string,
     *     email: string,
     *     gender: string,
     *     spoken_languages: string,
     *     degree_title: string,
     *     experience: int,
     *     about: string,
     *     about_ar: string
     * }>
     */
    private function doctorProfiles(): array
    {
        return [
            [
                'phone' => '966511122233',
                'name' => 'Dr. Test Doctor',
                'name_ar' => 'د. اختبار',
                'email' => 'doctor@test.awaan.io',
                'gender' => 'male',
                'spoken_languages' => 'ar_en',
                'degree_title' => 'Doctor (Specialist)',
                'experience' => 8,
                'about' => 'Seeded test doctor for local development and QA.',
                'about_ar' => 'طبيب تجريبي للاختبار المحلي.',
            ],
            [
                'phone' => '966511122234',
                'name' => 'Dr. Nada Alghamdi',
                'name_ar' => 'د. ندى الغامدي',
                'email' => 'nada.alghamdi@test.awaan.io',
                'gender' => 'female',
                'spoken_languages' => 'ar_en',
                'degree_title' => 'Doctor (Specialist)',
                'experience' => 10,
                'about' => 'Supports patients with mood and relationship challenges.',
                'about_ar' => 'تدعم المرضى في تحديات المزاج والعلاقات.',
            ],
            [
                'phone' => '966511122235',
                'name' => 'Dr. Khalid Mohammed',
                'name_ar' => 'د. خالد محمد',
                'email' => 'khalid.mohammed@test.awaan.io',
                'gender' => 'male',
                'spoken_languages' => 'ar',
                'degree_title' => 'Doctor (Consultant)',
                'experience' => 14,
                'about' => 'Consultant focused on anxiety and panic disorders.',
                'about_ar' => 'استشاري متخصص في القلق واضطرابات الهلع.',
            ],
            [
                'phone' => '966511122236',
                'name' => 'Dr. Fatima Noor',
                'name_ar' => 'د. فاطمة نور',
                'email' => 'fatima.noor@test.awaan.io',
                'gender' => 'female',
                'spoken_languages' => 'ar',
                'degree_title' => 'Doctor (Specialist)',
                'experience' => 7,
                'about' => 'Helps patients manage obsessive and intrusive thoughts.',
                'about_ar' => 'تساعد المرضى على إدارة الأفكار الوسواسية.',
            ],
            [
                'phone' => '966511122237',
                'name' => 'Dr. Omar Hassan',
                'name_ar' => 'د. عمر حسن',
                'email' => 'omar.hassan@test.awaan.io',
                'gender' => 'male',
                'spoken_languages' => 'ar_en',
                'degree_title' => 'Doctor (Consultant)',
                'experience' => 12,
                'about' => 'Works with trauma recovery and sleep-related concerns.',
                'about_ar' => 'يعمل على التعافي من الصدمات ومشاكل النوم.',
            ],
            [
                'phone' => '966511122238',
                'name' => 'Dr. Layla Rahman',
                'name_ar' => 'د. ليلى رحمن',
                'email' => 'layla.rahman@test.awaan.io',
                'gender' => 'female',
                'spoken_languages' => 'en',
                'degree_title' => 'Non-Doctor (Therapist)',
                'experience' => 6,
                'about' => 'Therapist supporting emotional resilience and self-esteem.',
                'about_ar' => 'أخصائية تدعم المرونة العاطفية وتقدير الذات.',
            ],
            [
                'phone' => '966511122239',
                'name' => 'Dr. Yusuf Kareem',
                'name_ar' => 'د. يوسف كريم',
                'email' => 'yusuf.kareem@test.awaan.io',
                'gender' => 'male',
                'spoken_languages' => 'ar_en',
                'degree_title' => 'Doctor (Specialist)',
                'experience' => 9,
                'about' => 'Specialist in bipolar and mood-related conditions.',
                'about_ar' => 'أخصائي في اضطرابات المزاج وثنائي القطب.',
            ],
            [
                'phone' => '966511122240',
                'name' => 'Dr. Hana Saleh',
                'name_ar' => 'د. حنا صالح',
                'email' => 'hana.saleh@test.awaan.io',
                'gender' => 'female',
                'spoken_languages' => 'ar_en',
                'degree_title' => 'Doctor (Specialist)',
                'experience' => 11,
                'about' => 'Supports patients facing substance use and dependency.',
                'about_ar' => 'تدعم المرضى الذين يواجهون مشاكل الإدمان.',
            ],
            [
                'phone' => '966511122241',
                'name' => 'Dr. Rami Farouk',
                'name_ar' => 'د. رامي فاروق',
                'email' => 'rami.farouk@test.awaan.io',
                'gender' => 'male',
                'spoken_languages' => 'ar',
                'degree_title' => 'Doctor (Consultant)',
                'experience' => 15,
                'about' => 'Consultant experienced with severe mood and psychotic symptoms.',
                'about_ar' => 'استشاري متمرس في اضطرابات المزاج والأعراض الذهانية.',
            ],
            [
                'phone' => '966511122242',
                'name' => 'Dr. Amira Zayed',
                'name_ar' => 'د. أميرة زايد',
                'email' => 'amira.zayed@test.awaan.io',
                'gender' => 'female',
                'spoken_languages' => 'ar_en',
                'degree_title' => 'Doctor (Specialist)',
                'experience' => 5,
                'about' => 'Helps adolescents and adults with identity and relationship stress.',
                'about_ar' => 'تساعد المراهقين والبالغين في ضغوط الهوية والعلاقات.',
            ],
        ];
    }

    /**
     * @return array<int, float>
     */
    private function durationPrices(): array
    {
        return [
            15 => 150.0,
            30 => 250.0,
        ];
    }

    private function seedWorkingDays(Doctor $doctor): void
    {
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayOfWeek) {
            $workingDay = WorkingDay::query()->updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $dayOfWeek,
                    'override_date' => null,
                ],
                [
                    'is_working' => true,
                ],
            );

            WorkingHour::query()->updateOrCreate(
                [
                    'working_day_id' => $workingDay->id,
                    'start_time' => '09:00:00',
                ],
                [
                    'end_time' => '17:00:00',
                ],
            );
        }
    }
}
