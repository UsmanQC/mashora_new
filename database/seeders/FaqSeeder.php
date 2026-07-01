<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How do I book a session?',
                'question_ar' => 'كيف أحجز جلسة؟',
                'answer' => 'Sign in with your mobile number, choose a specialist, pick a time slot, and complete payment.',
                'answer_ar' => 'سجّل دخولك برقم الجوال، اختر مختصاً، حدّد الموعد، ثم أكمل الدفع.',
                'category' => 'booking',
                'sort_order' => 1,
            ],
            [
                'question' => 'Are sessions confidential?',
                'question_ar' => 'هل الجلسات سرية؟',
                'answer' => 'Yes. Sessions are private and handled according to our privacy policy.',
                'answer_ar' => 'نعم. الجلسات خاصة وتُدار وفق سياسة الخصوصية.',
                'category' => 'privacy',
                'sort_order' => 2,
            ],
            [
                'question' => 'Can I choose video or chat?',
                'question_ar' => 'هل يمكنني اختيار فيديو أو محادثة؟',
                'answer' => 'Available channels depend on the specialist. Many support chat and video calls.',
                'answer_ar' => 'تعتمد القنوات المتاحة على المختص. كثير منهم يدعم المحادثة والمكالمات المرئية.',
                'category' => 'services',
                'sort_order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                $faq,
            );
        }
    }
}
