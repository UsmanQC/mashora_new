<?php

namespace Database\Seeders;

use App\Models\{Exam, Option, Question};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Option::truncate();
        Question::truncate();
        Exam::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            [
                'title'         => 'Overall Mental<br/>Wellness',
                'title_ar'      => 'اختبار شامل<br/>للصحة النفسية',
                'estimate_time' => 4,
                'metric_result_type' => 'overall_mental_wellness',
                'order'         => 1,
                'questions'     => [
                    [
                        'title'     => 'I generally feel optimistic about the future',
                        'title_ar'  => 'أشعر عمومًا بالتفاؤل بشأن المستقبل.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I am able to cope with stress and life\'s challenges effectively.',
                        'title_ar' => 'أستطيع التعامل مع الضغوط وتحديات الحياة بفعالية.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I have a strong sense of purpose and meaning in my life.',
                        'title_ar' => 'لدي شعور قوي بالغاية بالغرض والمعنى في حياتي.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have a supportive social network and feel connected to others.',
                        'title_ar'  => 'لدي شبكة اجتماعية داعمة وأشعر بالاتصال بالآخرين.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I am satisfied with my relationships and feel emotionally connected to those around me.',
                        'title_ar'  => 'أنا راضٍ عن علاقاتي وأشعر بالارتباط العاطفي مع من حولي.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I engage in activities that bring me joy and fulfillment regularly.',
                        'title_ar'  => 'أنا أشارك في الأنشطة التي تجلب لي الفرح والإشباع بانتظام.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I am able to express my emotions in a healthy way.',
                        'title_ar'  => 'أستطيع التعبير عن مشاعري بطريقة صحية.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a sense of accomplishment in my personal and professional life.',
                        'title_ar'  => 'أشعر بالإنجاز في حياتي الشخصية والمهنية.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I prioritize self-care and make time for activities that promote my well-being.',
                        'title_ar'  => 'أولوية الرعاية الذاتية وأجد الوقت للأنشطة التي تعزز صحتي.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I am open to seeking help and support when needed.',
                        'title_ar'  => 'أنا مفتوح لطلب المساعدة والدعم عند الحاجة.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I am open to seeking help and support when needed.',
                        'title_ar'  => 'أنا عمومًا أملك نظرة إيجابية تجاه الحياة.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I am able to adapt to changes and challenges in a healthy way.',
                        'title_ar'  => 'أستطيع التكيف مع التغييرات والتحديات بطريقة صحية.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => 'نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    // Set 2:
                    [
                        'title'     => 'I am content with my life overall.',
                        'title_ar'  => 'أنا راضٍ عن حياتي بشكل عام.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a sense of balance and harmony in my daily activities.',
                        'title_ar'  => 'أشعر بالتوازن والانسجام في أنشطتي اليومية.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I am able to manage my time and responsibilities effectively.',
                        'title_ar'  => 'أستطيع إدارة وقتي ومسؤولياتي بفعالية.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have a positive and nurturing inner dialogue with myself.',
                        'title_ar'  => 'لدي حوار داخلي إيجابي ومغذي مع نفسي.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a sense of belonging and connection in my community.',
                        'title_ar'  => 'أشعر بالانتماء والاتصال في مجتمعي.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I am able to set and achieve meaningful goals in my life.',
                        'title_ar'  => 'أنا قادر على تحديد وتحقيق أهداف معنوية في حياتي.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I practice mindfulness or relaxation techniques regularly.',
                        'title_ar'  => 'أمارس تقنيات الاسترخاء أو الانتباه بانتظام.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have a positive and supportive network of friends and family.',
                        'title_ar'  => 'لدي شبكة أصدقاء وعائلة إيجابية وداعمة.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a sense of accomplishment in both my personal and professional life.',
                        'title_ar'  => 'أشعر بالإنجاز في حياتي الشخصية والمهنية.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I engage in activities that bring me a sense of purpose and joy.',
                        'title_ar'  => 'أشارك في الأنشطة التي تجلب لي الشعور بالغرض والفرح.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have effective coping strategies to deal with challenges.',
                        'title_ar'  => 'أمتلك استراتيجيات فعالة للتعامل مع التحديات.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a sense of fulfillment in my personal and professional relationships.',
                        'title_ar'  => 'أشعر بالإشباع في علاقاتي الشخصية والمهنية.',
                        'options'   => [
                            [
                                'title'       => 'Not at all',
                                'title_ar'    => 'على الإطلاق',
                                'value'  => 0,
                            ],
                            [
                                'title'      => 'Slightly',
                                'title_ar'   => 'قليلاً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Moderately',
                                'title_ar'   => 'بشكل معتدل',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Very much',
                                'title_ar'   => 'كثيرًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title'         => 'Depression<br/>Metric',
                'title_ar'      => 'مقياس نسبة<br/>الاحباط',
                'estimate_time' => 5,
                'metric_result_type' => 'depression_metric',
                'order'         => 2,
                'questions'     => [
                    [
                        'title'     => 'I have little interest or pleasure in doing things.',
                        'title_ar'  => 'لدي قليل من الاهتمام أو المتعة في القيام بالأشياء.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I feel down, depressed, or hopeless.',
                        'title_ar' => ' أشعر بالحزن أو الاكتئاب أو اليأس.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I have trouble falling or staying asleep, or I sleep too much.',
                        'title_ar' => 'لدي صعوبة في النوم أو البقاء في النوم أو أن أنام كثيرًا.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I feel tired or have little energy.',
                        'title_ar' => 'أشعر بالتعب أو لدي طاقة قليلة.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I have poor appetite or overeat.',
                        'title_ar' => 'لدي شهية سيئة أو أنا أكل بكميات كبيرة.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I feel like a failure or have let myself or my family down.',
                        'title_ar' => ' أشعر بأنني فشلت أو أنني خذلت نفسي أو عائلتي.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I have trouble concentrating on things, such as reading the newspaper or watching television.',
                        'title_ar' => 'لدي صعوبة في التركيز على الأشياء، مثل قراءة الصحيفة أو مشاهدة التلفاز.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I move or speak so slowly that other people have noticed, or the opposite—I am so fidgety or restless that I have been moving more than usual.',
                        'title_ar' => ' أتحرك أو أتكلم ببطء بحيث لاحظه الآخرون، أو العكس - أنا متهيج جدًا أو متوتر لدرجة أنني أتحرك أكثر من المعتاد.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I have thoughts that I would be better off dead or of hurting myself in some way.',
                        'title_ar' => ' لدي أفكار بأنني سأكون أفضل بعيدًا عن الحياة أو بالإضافة إلى ذلك أني أفكر في إيذاء نفسي بطريقة ما.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I have aches or pains that have no clear physical cause.',
                        'title_ar' => ' لدي آلام أو أوجاع ليس لها سبب واضح فيزيائي.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I feel lonely or isolated from others.',
                        'title_ar' => 'أشعر بالوحدة أو الانعزال عن الآخرين.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I have lost or gained weight without trying.',
                        'title_ar' => 'لقد فقدت أو زاد وزني دون محاولة.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'عدة أيام',
                                'value' => 3,
                            ],
                        ],
                    ],
                    // Set 2 :
                    [
                        'title'    => 'I find it difficult to experience joy or pleasure in activities I used to enjoy.',
                        'title_ar' => 'أجد صعوبة في تجربة الفرح أو المتعة في الأنشطة التي كنت أستمتع بها.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'I feel like a burden to others or that I\'m not contributing anything meaningful.',
                        'title_ar' => 'أشعر بأنني عبء على الآخرين أو أنني لا أسهم بأي شيء ذي قيمة.',
                        'options'  => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have difficulty making decisions or concentrating on tasks.',
                        'title_ar'  => ' لدي صعوبة في اتخاذ القرارات أو التركيز ع',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience changes in appetite or weight that are unrelated to intentional dieting.',
                        'title_ar'  => ' أشعر بالتغيرات في الشهية أو الوزن التي لا علاقة لها باتباع نظام غذائي مقصود.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a sense of hopelessness about the future.',
                        'title_ar'  => 'أشعر باليأس تجاه المستقبل.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have trouble falling asleep, staying asleep, or sleeping too much.',
                        'title_ar'  => 'لدي صعوبة في النوم، البقاء في النوم، أو النوم بكثرة.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel fatigued or have a lack of energy.',
                        'title_ar'  => 'أشعر بالتعب أو نقص الطاقة.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have persistent feelings of sadness, tearfulness, or emptiness.',
                        'title_ar'  => 'لدي مشاعر مستمرة من الحزن أو البكاء أو الفراغ.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience aches or pains without a clear physical cause.',
                        'title_ar'  => 'أشعر بالآلام أو الوجع دون وجود سبب فيزيائي واضح.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have thoughts of death or suicide.',
                        'title_ar'  => 'لدي أفكار حول الموت أو الانتحار.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I withdraw from social activities and feel a sense of isolation.',
                        'title_ar'  => 'أنسحب عن الأنشطة الاجتماعية وأشعر بالعزلة.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I struggle to find meaning or purpose in my life.',
                        'title_ar'  => 'أجد صعوبة في العثور على معنى أو هدف في حياتي.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادراً',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                ]
            ],
            [
                'title'         => 'Anxiety<br/>Metric',
                'title_ar'      => 'مقياس نسبة<br/>القلق',
                'estimate_time' => 6,
                'metric_result_type' => 'anxiety_metric',
                'order'         => 3,
                'questions'     => [
                    [
                        'title'     => 'I feel restless, keyed up, or on edge.',
                        'title_ar'  => 'أشعر بالقلق أو الاضطراب.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have uncontrollable worries.',
                        'title_ar'  => 'لدي قلق لا يمكن السيطرة عليه.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience muscle tension or soreness due to stress.',
                        'title_ar'  => 'أشعر بتوتر أو ألم عضلي بسبب الضغط.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I find it difficult to concentrate on tasks due to anxious thoughts.',
                        'title_ar'  => 'لدي صعوبة في التركيز على المهام بسبب أفكار القلق.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel easily fatigued, even when I haven\'t engaged in physical activity.',
                        'title_ar'  => 'أشعر بالتعب بسرعة، حتى عندما لا أمارس النشاط البدني.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have a sense of impending doom or danger.',
                        'title_ar'  => 'لدي شعور بالكوارث المحتملة أو الخطر.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience irritability or edginess.',
                        'title_ar'  => 'أشعر بالغضب أو الاضطراب.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have trouble falling asleep, staying asleep, or have restless sleep due to anxious thoughts.',
                        'title_ar'  => 'لدي صعوبة في النوم، البقاء في النوم، أو النوم بكثرة بسبب أفكار القلق.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a rapid heart rate or palpitations without physical exertion.',
                        'title_ar'  => 'أشعر بارتفاع معدل نبضات القلب أو بالخفقان دون مجهود جسدي.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience trembling or shaking due to nervousness.',
                        'title_ar'  => 'أشعر بالرجفة أو الهز بسبب العصبية.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I avoid situations or activities because they make me anxious.',
                        'title_ar'  => 'أتجنب المواقف أو الأنشطة لأنها تثير قلقي.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have difficulty relaxing or feeling at ease.',
                        'title_ar'  => 'لدي صعوبة في الاسترخاء أو الشعور بالراحة.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a sense of impending doom or danger.',
                        'title_ar'  => 'أشعر بالكوارث المحتملة أو الخطر.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Several days',
                                'title_ar'   => 'عدة أيام',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'More than half the days',
                                'title_ar'   => 'أكثر من نصف الأيام',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Nearly every day',
                                'title_ar'   => 'كل يوم تقريبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    // set 2 :
                    [
                        'title'     => 'I worry excessively about various aspects of my life.',
                        'title_ar'  => 'أشعر بالقلق بشكل مفرط حول جوانب مختلفة من حياتي.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience physical symptoms such as sweating, trembling, or a racing heart without apparent cause.',
                        'title_ar'  => 'أعاني من أعراض جسدية مثل التعرق، الرجفان، أو زيادة في نبضات القلب بدون سبب ظاهر.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have difficulty controlling my worries or nervous thoughts.',
                        'title_ar'  => 'لدي صعوبة في السيطرة على أفكاري القلقة أو العصبية.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I avoid situations that make me anxious.',
                        'title_ar'  => 'أتجنب المواقف التي تثير قلقي.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel tense or on edge.',
                        'title_ar'  => 'أشعر بالتوتر أو الاضطراب.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I have trouble falling asleep, staying asleep, or have restless sleep due to anxious thoughts.',
                        'title_ar'  => 'لدي صعوبة في النوم، البقاء في النوم، أو النوم بكثرة بسبب أفكار القلق.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I find it hard to relax or unwind.',
                        'title_ar'  => 'أجد صعوبة في الاسترخاء أو الترويح.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience sudden and intense feelings of fear or panic.',
                        'title_ar'  => 'أشعر بالخوف أو الهلع الحاد والمفاجئ.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I feel a constant sense of dread or unease.',
                        'title_ar'  => 'أشعر بالقلق المستمر أو القلق.',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I struggle to focus or concentrate due to anxious thoughts.',
                        'title_ar'  => '',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'I experience physical symptoms like muscle tension or headaches related to anxiety.',
                        'title_ar'  => '',
                        'options'   => [
                            [
                                'title'      => 'Not at all',
                                'title_ar'   => 'على الإطلاق',
                                'value' => 0,
                            ],
                            [
                                'title'      => 'Rarely',
                                'title_ar'   => ' نادرًا',
                                'value' => 1,
                            ],
                            [
                                'title'      => 'Sometimes',
                                'title_ar'   => 'أحيانًا',
                                'value' => 2,
                            ],
                            [
                                'title'      => 'Often',
                                'title_ar'   => 'غالبًا',
                                'value' => 3,
                            ],
                        ],
                    ],
                ]
            ],
            [
                'title' => 'Ocupational<br/>Burnout<br/>Metric',
                'title_ar' => 'مقياس<br/>الاحتراق<br/>الوظيفي',
                'estimate_time' => 5,
                'metric_result_type' => 'ocupational_burnout_metric',
                'order' => 4,
                'questions' => [
                    [
                        'title' => 'I feel emotionally drained from my work.',
                        'title_ar' => 'أشعر بالتعب العاطفي من عملي.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I feel exhausted even before starting work.',
                        'title_ar' => 'أشعر بالإرهاق حتى قبل بدء العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I find it challenging to concentrate on tasks at work.',
                        'title_ar' => 'أجد صعوبة في التركيز على المهام في العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I feel disillusioned about my job.',
                        'title_ar' => 'أشعر بالإحباط تجاه عملي.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I am easily irritable or short-tempered at work.',
                        'title_ar' => ' أنا سريع الغضب أو ذو تصرفات انفعالية سريعة في العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I find it difficult to motivate myself to perform work-related tasks.',
                        'title_ar' => 'أجد صعوبة في تحفيز نفسي لأداء المهام المتعلقة بالعمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I feel like I am not making a meaningful contribution at work.',
                        'title_ar' => '. أشعر بأنني لا أقدم إسهامًا ذا معنى في العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I experience physical symptoms such as headaches or stomachaches related to work stress.',
                        'title_ar' => 'أعاني من أعراض جسدية مثل الصداع أو آلام البطن بسبب ضغوط العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I have trouble sleeping due to work-related stress.',
                        'title_ar' => 'أواجه صعوبة في النوم بسبب ضغوط العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I feel overwhelmed by the demands of my job.',
                        'title_ar' => 'أشعر أني مثقل بسبب متطلبات العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I have become less satisfied with my achievements at work.',
                        'title_ar' => 'أصبحت أقل رضاً عن إنجازاتي في العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I feel like my work negatively impacts my personal life.',
                        'title_ar' => 'أشعر بأن عملي يؤثر سلبًا على حياتي الشخصية.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    // Set 2:
                    [
                        'title' => 'I feel a lack of energy to tackle my daily tasks at work.',
                        'title_ar' => 'أشعر بنقص الطاقة لمواجهة مهامي اليومية في العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I often feel emotionally drained after working.',
                        'title_ar' => 'غالبًا ما أشعر بالتعب النفسي بعد العمل',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I find it hard to concentrate on my work tasks.',
                        'title_ar' => ' أجد صعوبة في التركيز على مهامي في العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I feel like I am emotionally detached from my work.',
                        'title_ar' => 'أشعر بأنني منعزل عاطفيًا عن عملي.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I frequently experience mood swings related to work stress.',
                        'title_ar' => 'غالبًا ما أعاني من تقلبات المزاج المرتبطة بضغوط العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I struggle to find motivation to complete my work assignments.',
                        'title_ar' => 'أجد صعوبة في العثور على الدافع لإكمال مهامي في العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I question the significance of my work.',
                        'title_ar' => 'أشكك في أهمية عملي.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I frequently experience physical symptoms due to work-related stress.',
                        'title_ar' => 'غالبًا ما أعاني من أعراض جسدية بسبب ضغوط العمل.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title'    => 'Work-related stress affects my ability to sleep.',
                        'title_ar' => 'يؤثر ضغط العمل على قدرتي على النوم.',
                        'options'  => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I often feel overwhelmed by my workload.',
                        'title_ar' => 'غالبًا ما أشعر بالغموض بسبب حجم العمل المطلوب.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I feel less satisfied with my job compared to before.',
                        'title_ar' => 'أشعر بأنني أقل راضٍ عن عملي مقارنة بالسابق.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'I find it challenging to balance work and personal life.',
                        'title_ar' => 'أجد صعوبة في التوازن بين العمل والحياة الشخصية.',
                        'options' => [
                            [
                                'title' => 'Not at all',
                                'title_ar' => '',
                                'value' => 0,
                            ],
                            [
                                'title' => 'Several days',
                                'title_ar' => '',
                                'value' => 1,
                            ],
                            [
                                'title' => 'More than half the days',
                                'title_ar' => '',
                                'value' => 2,
                            ],
                            [
                                'title' => 'Nearly every day',
                                'title_ar' => '',
                                'value' => 3,
                            ],
                        ],
                    ],
                ]
            ],
            [
                'title'         => 'ADHD Metric<br/>(Kids)',
                'title_ar'      => 'اضطراب فرط الحركة<br/>(أطفال)',
                'estimate_time' => 6,
                'metric_result_type' => 'adhd_metric_kids',
                'order'         => 5,
                'questions'     => [
                    [
                        'title' => 'How often does your child have trouble organizing tasks and activities?',
                        'title_ar' => 'كم مرة يواجه طفلك صعوبة في تنظيم المهام والأنشطة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child avoid or dislike tasks that require sustained mental effort?',
                        'title_ar' => 'هل يتجنب طفلك أو يكره المهام التي تتطلب جهدًا عقليًا مستمرًا؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child lose things necessary for tasks or activities?',
                        'title_ar' => ' كم مرة يفقد طفلك الأشياء الضرورية للمهام أو الأنشطة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child seem forgetful in daily activities?',
                        'title_ar' => 'هل يبدو طفلك نسيانًا في الأنشطة اليومية؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child fidget with or tap hands or feet, or squirm in their seat?',
                        'title_ar' => 'كم مرة يلعب طفلك بأصابعه أو يرنو أو يتلاعب بقدميه، أو يتلوى في مقعده؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child often leave their seat in situations where remaining seated is expected?',
                        'title_ar' => 'هل يترك طفلك مقعده في العديد من المواقف التي يُتوقع البقاء جالسًا فيها؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child run about or climb excessively in situations where it is inappropriate?',
                        'title_ar' => 'كم مرة يجري طفلك بشكل مفرط أو يتسلق بشكل مبالغ فيه في المواقف التي لا تناسب ذلك؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child have difficulty playing or engaging in activities quietly?',
                        'title_ar' => 'هل يواجه طفلك صعوبة في اللعب أو المشاركة في الأنشطة بصمت؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child talk excessively?',
                        'title_ar' => 'كم مرة يتحدث طفلك بشكل مفرط؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child interrupt or intrude on others\' conversations or activities?',
                        'title_ar' => 'هل يقاطع طفلك أو يتدخل في محادثات أو أنشطة الآخرين؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child have difficulty waiting their turn?',
                        'title_ar' => 'كم مرة يواجه طفلك صعوبة في انتظار دوره؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child often blurts out answers before questions have been completed?',
                        'title_ar' => 'هل يفصل طفلك بشكل متكرر الإجابات قبل انتهاء الأسئلة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child have difficulty following through on instructions or completing tasks?',
                        'title_ar' => 'كم مرة يواجه طفلك صعوبة في متابعة التعليمات أو إكمال المهام؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child have trouble organizing tasks and activities?',
                        'title_ar' => 'هل يعاني طفلك من صعوبة في تنظيم المهام والأنشطة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child avoid or dislike tasks requiring sustained mental effort?',
                        'title_ar' => 'كم مرة يتجنب طفلك أو يكره المهام التي تتطلب جهدًا عقليًا مستمرًا؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    // set 2:
                    [
                        'title' => 'How often does your child have difficulty paying attention to details or make careless mistakes?',
                        'title_ar' => 'كم مرة يواجه طفلك صعوبة في التركيز على التفاصيل أو يرتكب أخطاء غير محسوبة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child often have trouble sustaining attention in tasks or play activities?',
                        'title_ar' => 'هل يواجه طفلك بشكل متكرر صعوبة في الانتباه أثناء المهام أو الأنشطة الترفيهية؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child not seem to listen when spoken to directly?',
                        'title_ar' => 'كم مرة لا يبدو طفلك يستمع عندما يُحدث إليه مباشرة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child fail to follow through on instructions or fails to finish tasks (not due to oppositional behavior or failure to understand instructions)?',
                        'title_ar' => 'هل يتخلف طفلك عن اتباع التعليمات أو عدم إكمال المهام (ليس بسبب سلوك مقاوم أو عدم فهم التعليمات)؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child have difficulty organizing tasks and activities?',
                        'title_ar' => 'كم مرة يواجه طفلك صعوبة في تنظيم المهام والأنشطة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child avoid or dislike tasks that require sustained mental effort?',
                        'title_ar' => 'هل يتجنب طفلك أو يكره المهام التي تتطلب جهدًا عقليًا مستمرًا؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child lose things necessary for tasks or activities?',
                        'title_ar' => 'كم مرة يفقد طفلك الأشياء الضرورية للمهام أو الأنشطة؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child seem forgetful in daily activities?',
                        'title_ar' => 'هل يبدو طفلك نسيانًا في الأنشطة اليومية؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child fidget with or tap hands or feet, or squirm in their seat?',
                        'title_ar' => 'كم مرة يلعب طفلك بأصابعه أو يرنو أو يتلاعب بقدميه، أو يتلوى في مقعده؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child often leave their seat in situations where remaining seated is expected?',
                        'title_ar' => 'هل يترك طفلك مقعده في العديد من المواقف التي يُتوقع البقاء جالسًا فيها؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child run about or climb excessively in situations where it is inappropriate?',
                        'title_ar' => 'كم مرة يجري طفلك بشكل مفرط أو يتسلق بشكل مبالغ فيه في المواقف التي لا تناسب ذلك؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child have difficulty playing or engaging in activities quietly?',
                        'title_ar' => 'هل يواجه طفلك صعوبة في اللعب أو المشاركة في الأنشطة بصمت؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child talk excessively?',
                        'title_ar' => 'كم مرة يتحدث طفلك بشكل مفرط؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Does your child interrupt or intrude on others\' conversations or activities?',
                        'title_ar' => 'هل يقاطع طفلك أو يتدخل في محادثات أو أنشطة الآخرين؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title' => 'How often does your child have difficulty waiting their turn?',
                        'title_ar' => 'كم مرة يواجه طفلك صعوبة في انتظار دوره؟',
                        'options' => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => ' بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                ]
            ],
            [
                'title'         => 'ADHD Metric<br/>(Adults)',
                'title_ar'      => 'اضطراب فرط الحركة<br/>(كبار)',
                'estimate_time' => 5,
                'metric_result_type' => 'adhd_metric_adults',
                'order'         => 6,
                'questions'     => [
                    [
                        'title'     => 'How often do you have difficulty organizing tasks and activities?',
                        'title_ar'  => 'كم مرة تواجه صعوبة في تنظيم المهام والأنشطة؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'Do you avoid or dislike tasks that require sustained mental effort?',
                        'title_ar'  => 'هل تتجنب أو تكره المهام التي تتطلب جهدًا عقليًا مستمرًا؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'How often do you lose things necessary for tasks or activities?',
                        'title_ar'  => 'كم مرة تفقد الأشياء الضرورية للمهام أو الأنشطة؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'Do you often forget appointments or commitments?',
                        'title_ar'  => 'هل تنسى مواعيد الاجتماعات أو التزاماتك بشكل متكرر؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'How often do you have difficulty sustaining attention in tasks or leisure activities?',
                        'title_ar'  => 'كم مرة تواجه صعوبة في الانتباه المستمر في المهام أو الأنشطة الترفيهية؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'Do you often feel restless or have trouble sitting still?',
                        'title_ar'  => 'هل تشعر بالقلق المستمر أو الارتياح الصعب في الجلوس؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'How often do you find yourself fidgeting with your hands or tapping your feet when you\'re seated?',
                        'title_ar'  => 'كم مرة تجد نفسك تحريك يديك أو تقليب قدميك عندما تكون جالسًا؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'Do you often interrupt or intrude on others\' conversations or activities?',
                        'title_ar'  => 'هل تقاطع أو تدخل في محادثات أو أنشطة الآخرين بشكل متكرر؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'How often do you have difficulty waiting your turn?',
                        'title_ar'  => 'كم مرة تواجه صعوبة في انتظار دورك؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'Do you often feel impatient or have difficulty waiting for things?',
                        'title_ar'  => 'هل تشعر بالانفعال المستمر أو تجد صعوبة في الانتظار للأمور؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'How often do you have trouble following through on instructions or finishing tasks?',
                        'title_ar'  => 'كم مرة تواجه صعوبة في اتباع التعليمات أو إكمال المهام؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'Do you often start tasks but quickly lose focus and fail to complete them?',
                        'title_ar'  => ' هل تبدأ المهام ولكن تفقد سرعان ما تركيزك و',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'How often do you find yourself daydreaming or easily distracted?',
                        'title_ar'  => 'كم مرة تجد نفسك تحلم متيقظًا أو تشعر بالانحراف بسهولة؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'Do you often feel overwhelmed by tasks or responsibilities?',
                        'title_ar'  => 'هل تشعر بالإرهاق المستمر من المهام أو المسؤوليات؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    [
                        'title'     => 'How often do you have difficulty managing your time effectively?',
                        'title_ar'  => 'كم مرة تواجه صعوبة في إدارة وقتك بشكل فعّال؟',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                    // Set 2 :
                    [
                        'title'     => 'How often do you have difficulty managing your time effectively?',
                        'title_ar'  => '',
                        'options'   => [
                            [
                                'title'       => 'Never',
                                'title_ar'    => ' أبدًا',
                                'value'  => 0,
                            ],
                            [
                                'title'       => 'Rarely',
                                'title_ar'    => 'نادرًا ما',
                                'value'  => 1,
                            ],
                            [
                                'title'       => 'Sometimes',
                                'title_ar'    => 'بعض الأحيان',
                                'value'  => 2,
                            ],
                            [
                                'title'       => 'Often',
                                'title_ar'    => 'غالبًا',
                                'value'  => 3,
                            ],
                        ],
                    ],
                ]
            ],
        ];

        foreach ($data as $edata) {
            $exam = Exam::updateOrCreate(['title' => $edata], [
                'title' => $edata['title'],
                'title_ar' => $edata['title_ar'],
                'estimate_time' => $edata['estimate_time'],
                'order' => $edata['order'],
                'metric_result_type' => $edata['metric_result_type']
            ]);

            if (isset($edata['questions']) && count($edata['questions']) > 0) {
                $questionOrder = 1;
                $questions = $edata['questions'];
                foreach ($questions as $questionData) {
                    $question = $exam->questions()->create([
                        'title' => $questionData['title'],
                        'title_ar' => $questionData['title_ar'],
                        'order' => $questionOrder
                    ]);
                    if (isset($questionData['options']) && count($questionData['options']) > 0) {
                        $optionOrder = 1;
                        foreach ($questionData['options'] as $optionData) {
                            $question->options()->create($optionData + ['order' => $optionOrder]);

                            $optionOrder++;
                        }
                    }

                    $questionOrder++;
                }
            }
        }
    }
}
