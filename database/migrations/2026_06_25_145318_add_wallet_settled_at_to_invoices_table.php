<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('wallet_settled_at')->nullable()->after('paid_at');
            $table->unique(['doctor_id', 'from_date', 'to_date'], 'invoices_doctor_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_doctor_period_unique');
            $table->dropColumn('wallet_settled_at');
        });
    }
};
