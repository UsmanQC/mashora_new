<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('appointments', 'is_follow_up')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->boolean('is_follow_up')->default(false)->after('parent_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('appointments', 'is_follow_up')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('is_follow_up');
        });
    }
};
