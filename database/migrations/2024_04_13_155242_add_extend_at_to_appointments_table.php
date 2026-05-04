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
        Schema::table('temporary_appointments', function (Blueprint $table) {
            $table->dateTime('extend_at')->nullable()->comment('Extend Date Time')->after('duration');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->dateTime('extend_at')->nullable()->comment('Extend Date Time')->after('duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temporary_appointments', function (Blueprint $table) {
            $table->dropColumn('extend_at');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('extend_at');
        });
    }
};
