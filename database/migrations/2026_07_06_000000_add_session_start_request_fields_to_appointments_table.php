<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dateTime('session_start_requested_at')->nullable()->after('extend_at')->comment('When the doctor requested the patient to approve session start');
            $table->dateTime('session_start_approved_at')->nullable()->after('session_start_requested_at')->comment('When the patient approved the early session start');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn(['session_start_requested_at', 'session_start_approved_at']);
        });
    }
};
