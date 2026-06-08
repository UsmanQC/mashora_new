<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_appointments', function (Blueprint $table) {
            $table->text('payment_invoice_url')->nullable()->change();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->text('payment_invoice_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('temporary_appointments', function (Blueprint $table) {
            $table->string('payment_invoice_url')->nullable()->change();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_invoice_url')->nullable()->change();
        });
    }
};
