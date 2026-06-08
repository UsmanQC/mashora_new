<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FcmPushService
{
    /**
     * @param  array<string, string>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $this->sendToNotifiable($user, $title, $body, $data);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToNotifiable(Model $notifiable, string $title, string $body, array $data = []): void
    {
        $serverKey = (string) config('push.firebase_server_key');

        if ($serverKey === '') {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('userable_type', $notifiable::class)
            ->where('userable_id', $notifiable->getKey())
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->filter(fn (mixed $token): bool => is_string($token) && $token !== '')
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens->chunk(500) as $chunk) {
            $this->sendChunk($chunk, $title, $body, $data, $serverKey);
        }
    }

    /**
     * @param  Collection<int, string>  $tokens
     * @param  array<string, string>  $data
     */
    private function sendChunk(Collection $tokens, string $title, string $body, array $data, string $serverKey): void
    {
        $payload = [
            'registration_ids' => $tokens->all(),
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => array_merge(['click_action' => 'FLUTTER_NOTIFICATION_CLICK'], $data),
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key '.$serverKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://fcm.googleapis.com/fcm/send', $payload);

            if (! $response->successful()) {
                Log::warning('FCM push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
