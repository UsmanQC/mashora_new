<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class DeviceTokenService
{
    public function register(Model $notifiable, string $token, ?string $sessionId = null): DeviceToken
    {
        $sessionId ??= Session::getId();

        DeviceToken::query()
            ->where('device_token', $token)
            ->where(function ($query) use ($notifiable): void {
                $query->where('userable_type', '!=', $notifiable::class)
                    ->orWhere('userable_id', '!=', $notifiable->getKey());
            })
            ->delete();

        return DeviceToken::query()->updateOrCreate(
            [
                'userable_type' => $notifiable::class,
                'userable_id' => $notifiable->getKey(),
                'device_token' => $token,
            ],
            [
                'session_id' => $sessionId,
            ],
        );
    }

    public function unregister(Model $notifiable, string $token): void
    {
        DeviceToken::query()
            ->where('userable_type', $notifiable::class)
            ->where('userable_id', $notifiable->getKey())
            ->where('device_token', $token)
            ->delete();
    }
}
