<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('patient_confirmed_at')->nullable()->after('parent_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('new', 'in_process', 'rescheduled', 'cancelled', 'completed', 'not_attended', 'pending_follow_up') NOT NULL DEFAULT 'new'");
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('patient_confirmed_at');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('new', 'in_process', 'rescheduled', 'cancelled', 'completed', 'not_attended') NOT NULL DEFAULT 'new'");
        }
    }
};
