<?php

use App\Models\Notification;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Notification::query()
            ->where('type', 'appointment_missed')
            ->lazyById()
            ->each(fn (Notification $notification) => $notification->repairStoredCopy());
    }

    public function down(): void
    {
        //
    }
};
