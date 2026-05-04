<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory(20)->create();
        \App\Models\Appointment::factory(10)->create();
        // \App\Models\Review::factory(100)->create();
        // \App\Models\PatientMood::factory(30)->create();
        // \App\Models\Notification::factory(30)->create();
        // Invoice::factory()
        //     ->count(50)
        //     ->has(InvoiceItem::factory()->count(1))
        //     ->create();
    }
}
