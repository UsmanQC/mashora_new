<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('user_id')
                ->constrained('appointments')
                ->nullOnDelete();
        });

        Schema::table('temporary_appointments', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('appointment_id')
                ->constrained('appointments')
                ->nullOnDelete();
            $table->boolean('is_follow_up')->default(false)->after('parent_id');
            $table->timestamp('patient_confirmed_at')->nullable()->after('is_follow_up');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['is_follow_up', 'patient_confirmed_at']);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
