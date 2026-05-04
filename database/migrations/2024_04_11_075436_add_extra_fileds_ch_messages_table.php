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
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id')->nullable()->after('to_id');
            $table->enum('send_by', ['doctor', 'patient'])->default('doctor')->after('appointment_id');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropColumn('appointment_id');
            $table->dropColumn('send_by');
            $table->dropSoftDeletes();
        });
    }
};
