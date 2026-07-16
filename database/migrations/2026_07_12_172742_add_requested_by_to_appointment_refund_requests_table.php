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
        Schema::table('appointment_refund_requests', function (Blueprint $table) {
            $table->string('requested_by')->default('patient')->after('doctor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_refund_requests', function (Blueprint $table) {
            $table->dropColumn('requested_by');
        });
    }
};
