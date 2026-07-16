<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FcmPushService
{
    public function __construct(
        private readonly FirebaseAccessTokenResolver $tokens,
    ) {}

    /**
     * @param  array<string, string>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], bool $silent = false): void
    {
        $this->sendToNotifiable($user, $title, $body, $data, $silent);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToNotifiable(Model $notifiable, string $title, string $body, array $data = [], bool $silent = false): void
    {
        $accessToken = $this->tokens->accessToken();
        $projectId = $this->tokens->projectId();

        if ($accessToken === null || $projectId === null || $projectId === '') {
            return;
        }

        $deviceTokens = DeviceToken::query()
            ->where('userable_type', $notifiable::class)
            ->where('userable_id', $notifiable->getKey())
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->filter(fn (mixed $token): bool => is_string($token) && $token !== '')
            ->unique()
            ->values();

        if ($deviceTokens->isEmpty()) {
            Log::info('FCM skipped: no device tokens for recipient', [
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->getKey(),
            ]);

            return;
        }

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($deviceTokens as $deviceToken) {
            $this->sendToToken($endpoint, $accessToken, $deviceToken, $title, $body, $data, $silent);
        }
    }

    /**
     * @param  array<string, string>  $data
     */
    private function sendToToken(
        string $endpoint,
        string $accessToken,
        string $deviceToken,
        string $title,
        string $body,
        array $data,
        bool $silent,
    ): void {
        $stringData = [];
        foreach (array_merge(['click_action' => 'FLUTTER_NOTIFICATION_CLICK'], $data) as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        $message = [
            'token' => $deviceToken,
            'data' => $stringData,
            'android' => [
                'priority' => 'high',
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => $silent
                        ? ['content-available' => 1]
                        : ['sound' => 'default'],
                ],
            ],
            'webpush' => [
                'headers' => [
                    'Urgency' => 'high',
                    'TTL' => '86400',
                ],
            ],
        ];

        if (! $silent) {
            $message['notification'] = [
                'title' => $title,
                'body' => $body,
            ];

            $message['webpush']['notification'] = [
                'title' => $title,
                'body' => $body,
                'icon' => asset('images/pwa/icon-192-v3.png'),
                'badge' => asset('images/pwa/icon-192-v3.png'),
            ];
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($endpoint, [
                    'message' => $message,
                ]);

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
