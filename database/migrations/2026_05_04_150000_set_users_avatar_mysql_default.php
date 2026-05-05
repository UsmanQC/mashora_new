<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy MySQL DBs may have `users.avatar` NOT NULL without a default. Match Chatify-style default.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'avatar')) {
            return;
        }

        $default = (string) config('chatify.user_avatar.default', 'avatar.png');
        $escaped = DB::connection()->getPdo()->quote($default);

        DB::table('users')->where(function ($query): void {
            $query->whereNull('avatar')->orWhere('avatar', '');
        })->update(['avatar' => $default]);

        DB::statement('ALTER TABLE `users` MODIFY `avatar` VARCHAR(255) NOT NULL DEFAULT '.$escaped);
    }

    /**
     * Revert is not safe without knowing the prior column definition; no-op.
     */
    public function down(): void {}
};
