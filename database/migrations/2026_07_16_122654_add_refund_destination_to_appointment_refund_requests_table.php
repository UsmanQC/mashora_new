<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_refund_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('appointment_refund_requests', 'refund_destination')) {
                $table->string('refund_destination')->default('wallet')->after('resolution_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment_refund_requests', function (Blueprint $table) {
            if (Schema::hasColumn('appointment_refund_requests', 'refund_destination')) {
                $table->dropColumn('refund_destination');
            }
        });
    }
};
