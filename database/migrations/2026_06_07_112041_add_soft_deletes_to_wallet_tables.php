<?php

use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSoftDeletesIfMissing((new Wallet)->getTable());
        $this->addSoftDeletesIfMissing((new Transaction)->getTable());
        $this->addSoftDeletesIfMissing((new Transfer)->getTable());
    }

    public function down(): void
    {
        $this->dropSoftDeletesIfPresent((new Transfer)->getTable());
        $this->dropSoftDeletesIfPresent((new Transaction)->getTable());
        $this->dropSoftDeletesIfPresent((new Wallet)->getTable());
    }

    private function addSoftDeletesIfMissing(string $tableName): void
    {
        if (Schema::hasColumn($tableName, 'deleted_at')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    private function dropSoftDeletesIfPresent(string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'deleted_at')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
