<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_appointments', function (Blueprint $table): void {
            $table->decimal('wallet_amount', 10, 2)->default(0)->after('total');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->decimal('wallet_amount', 10, 2)->default(0)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_appointments', function (Blueprint $table): void {
            $table->dropColumn('wallet_amount');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('wallet_amount');
        });
    }
};
