<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Account & login', 'name_ar' => 'الحساب وتسجيل الدخول', 'audience' => 'patient', 'sort_order' => 10],
            ['name' => 'Booking & appointments', 'name_ar' => 'الحجز والمواعيد', 'audience' => 'patient', 'sort_order' => 20],
            ['name' => 'Payments & refunds', 'name_ar' => 'المدفوعات والاسترداد', 'audience' => 'patient', 'sort_order' => 30],
            ['name' => 'Technical issues', 'name_ar' => 'مشاكل تقنية', 'audience' => 'patient', 'sort_order' => 40],
            ['name' => 'Session quality', 'name_ar' => 'جودة الجلسة', 'audience' => 'patient', 'sort_order' => 50],
            ['name' => 'Other', 'name_ar' => 'أخرى', 'audience' => 'patient', 'sort_order' => 99],
            ['name' => 'Account & verification', 'name_ar' => 'الحساب والتحقق', 'audience' => 'doctor', 'sort_order' => 10],
            ['name' => 'Appointments & schedule', 'name_ar' => 'المواعيد والجدول', 'audience' => 'doctor', 'sort_order' => 20],
            ['name' => 'Payments & invoices', 'name_ar' => 'المدفوعات والفواتير', 'audience' => 'doctor', 'sort_order' => 30],
            ['name' => 'Technical issues', 'name_ar' => 'مشاكل تقنية', 'audience' => 'doctor', 'sort_order' => 40],
            ['name' => 'Platform policies', 'name_ar' => 'سياسات المنصة', 'audience' => 'doctor', 'sort_order' => 50],
            ['name' => 'Other', 'name_ar' => 'أخرى', 'audience' => 'doctor', 'sort_order' => 99],
        ];

        foreach ($categories as $category) {
            TicketCategory::query()->updateOrCreate(
                [
                    'name' => $category['name'],
                    'audience' => $category['audience'],
                ],
                [
                    'name_ar' => $category['name_ar'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ],
            );
        }
    }
}
