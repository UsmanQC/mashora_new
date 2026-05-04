<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->after('duration', function (Blueprint $table) {
                $table->dateTime('actual_start_at')->nullable()->comment('Actual Start Session Date Time');
                $table->dateTime('actual_end_at')->nullable()->comment('Actual Completed Session Date Time');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['actual_start_at', 'actual_end_at']);
        });
    }
};
