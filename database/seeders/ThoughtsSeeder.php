<?php

namespace Database\Seeders;

use App\Models\Thought;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThoughtsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Thought::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            [
                'description_en'    => 'Living in the present is the key to happiness. Enjoy your moments and focus on the positives around you 🌟🧘‍♀️',
                'description_ar'    => 'العيش في الحاضر هو مفتاح السعادة، تمتع بلحظاتك وركّز على الإيجابيات حولك #سعادة_داخلية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Relaxing the mind is the key to inner happiness! Be sure to meditate and relax to enhance your psychological well-being',
                'description_ar'    => 'استرخاء العقل هو مفتاح السعادة الداخلية! احرص على التأمل والاستجمام لتعزيز رفاهيتك النفسية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'practicing physical activity can be beneficial for improving mood and psychological state, as exercise relieves stress',
                'description_ar'    => 'ممارسة النشاط البدني يمكن أن تكون مفيدة لتحسين المزاج والحالة النفسية، فالرياضة تفرز هرمونات السعادة وتخفف من التوتر',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Psychiatry focuses on the relationship between the mind and body and how they affect each other. Therefore, improving mental health can contribute to improving physical health.',
                'description_ar'    => 'الطب النفسي يركز على العلاقة بين العقل والجسم وكيفية تأثيرهما على بعضهما البعض. لذلك، فإن تحسين الصحة النفسية يمكن أن يساهم في تحسين الصحة الجسدية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,

            ],
            [
                'description_en'    =>  'Psychiatry is not limited to treating psychological disorders only, but it can be an effective tool for improving overall health and dealing with daily challenges',
                'description_ar'    => 'العيش في الحاضر هو مفتاح السعادة، تمتع بلحظاتك وركّز على الإيجابيات حولك #سعادة_داخلية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Hard work and constant stress can negatively affect mental health. It is important to take care of the balance between work and personal life to maintain good mental health.',
                'description_ar'    => 'العمل الشاق والتوتر المستمر يمكن أن يؤثران بشكل سلبي على الصحة النفسية. من المهم الاهتمام بالتوازن بين العمل والحياة الشخصية للحفاظ على صحة نفسية جيدة.',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Take care of your mental health as well as your physical health. Focus on relaxation and psychological nourishment to achieve balance',
                'description_ar'    => 'اهتم بصحتك النفسية كما تهتم بصحتك الجسدية. ركّز على الاسترخاء والتغذية النفسية لتحقيق التوازن',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Positive thinking is the key to success and happiness. Replace negative thoughts with optimism and find joy in every day #inner_happiness',
                'description_ar'    => 'التفكير الإيجابي هو مفتاح النجاح والسعادة. استبدل الأفكار السلبية بالتفاؤل وابحث عن البهجة في كل يوم #سعادة_داخلية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Ensure a balance between work and personal life. Make time for hobbies and joy to improve your psychological well-being',
                'description_ar'    => 'احرص على التوازن بين العمل والحياة الشخصية. امنح وقتًا للهوايات وأوقات الفرح لتحسين رفاهيتك النفسية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Learn how to face psychological challenges with confidence and positivity. Patience and optimism will help you get through it #Self_confidence',
                'description_ar'    => 'تعلم كيف تواجه التحديات النفسية بثقة وإيجابية. إن الصبر والتفاؤل سيساعدانك على تجاوزها #ثقة_بالنفس',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Remember that relaxation is not a luxury, but a necessity for your mental and physical health. Enjoy times of rest and relaxation',
                'description_ar'    => 'تذكّر أن الاسترخاء ليس ترفًا، بل ضرورة لصحتك النفسية والجسدية. استمتع بأوقات الراحة والاستجمام',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Strength is not in clinging to the past, but in being free from it! Get rid of the burden and prepare for a bright future #inner_strength',
                'description_ar'    => 'القوة ليست في التمسك بالماضي، بل في التحرر منه! تخلص من العبء واستعد لمستقبل مشرق #قوة_داخلية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Stay positive and enjoy the present moment! Remember that optimism promotes mental and physical health',
                'description_ar'    => 'كن إيجابيًا واستمتع باللحظة الحالية! تذكر أن التفاؤل يعزز الصحة النفسية والجسدية #تفاؤل',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Did you know? Psychiatry helps overcome psychological and emotional difficulties to improve the quality of life',
                'description_ar'    => 'هل تعلم؟ الطب النفسي يساعد في التغلب على الصعوبات النفسية والعاطفية لتحسين الجودة الحياة',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Social communication is important for mental health. Make sure to connect with friends and family to build positive connections',
                'description_ar'    => 'التواصل الاجتماعي مهم للصحة النفسية. احرص على التواصل مع الأصدقاء والعائلة لبناء روابط إيجابية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'A smile reflects inner happiness and positively affects the psychological state. Smile at life and yourself! 😃🌼 #happiness #smile',
                'description_ar'    => 'الابتسامة تعكس السعادة الداخلية وتؤثر إيجابًا على الحالة النفسية. ابتسم للحياة ولنفسك! 😃🌼 #سعادة #ابتسامة',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Remember that you deserve psychological comfort. Set aside time to relax and enjoy your favorite activities to improve your mental and emotional well-being',
                'description_ar'    => 'تذكّر أنك تستحق الراحة النفسية. حدد وقتًا للاسترخاء والاستمتاع بأنشطتك المفضلة لتحسين حالتك النفسية والعاطفية',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
            [
                'description_en'    => 'Know that you are not alone. Psychiatry supports individuals suffering from depression and anxiety and helps them regain happiness and inner peace',
                'description_ar'    => 'اعرف أنك لست وحدك, الطب النفسي يدعم الأفراد الذين يعانون من الاكتئاب والقلق ويساعدهم على استعادة السعادة والسلام الداخلي',
                'auth_en'           => 'Mashora',
                'auth_ar'           => 'مشورة',
                'status'            => 1,
            ],
        ];

        // Thought::insert($data);
        foreach($data as $item) {
            Thought::create($item);
        }
    }
}
