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
            // Assuming 'total' is the name of the existing column
            $table->decimal('doctor_share', 10, 2)->default(0)->after('total');
            $table->decimal('mashora_share', 10, 2)->default(0)->after('doctor_share');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Assuming 'total_amount' is the name of the existing column
            $table->decimal('doctor_share', 10, 2)->default(0)->after('total_amount');
            $table->decimal('mashora_share', 10, 2)->default(0)->after('doctor_share');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('doctor_share');
            $table->dropColumn('mashora_share');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('doctor_share');
            $table->dropColumn('mashora_share');
        });

        Schema::dropIfExists('invoice_items');
    }
};
