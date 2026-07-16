<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

final class SmsService
{
    /**
     * Send SMS OTP. Mirrors Mashorapwa-prod: Dreams.sa for Saudi (+966 / 966…), Twilio Verify for others.
     *
     * Real delivery happens when the app is in production OR config('sms.live')
     * is enabled (SMS_LIVE=true). Otherwise the OTP is shown on screen via the
     * verify-phone dev banner instead.
     *
     * @return mixed Response payload from provider, or true when live sending is disabled.
     */
    public function send(string $message, string $to, ?string $verificationCode = null): mixed
    {
        $digits = preg_replace('/\D/', '', $to) ?? '';

        if (! $this->isLive()) {
            return true;
        }

        if (str_starts_with($digits, '966')) {
            $user = (string) config('sms.user');
            $secret = (string) config('sms.secret_key');
            $sender = (string) config('sms.sender');

            if ($user === '' || $secret === '') {
                Log::warning('Dreams SMS is not configured (DREAMS_SMS_USER / DREAMS_SMS_SECRET_KEY); skipping send.');

                return false;
            }

            $url = 'https://www.dreams.sa/index.php/api/sendsms/'
                .'?user='.urlencode($user)
                .'&secret_key='.urlencode($secret)
                .'&to='.urlencode($digits)
                .'&message='.urlencode($message)
                .'&sender='.urlencode($sender);

            try {
                $http = Http::timeout(30);

                if (app()->isLocal()) {
                    $http = $http->withoutVerifying();
                }

                $response = $http->get($url);
            } catch (\Throwable $e) {
                Log::error('Dreams SMS request failed', [
                    'to' => $digits,
                    'sender' => $sender,
                    'message' => $e->getMessage(),
                ]);

                return false;
            }

            $body = $response->json() ?? $response->body();
            $bodyString = is_scalar($body) ? (string) $body : json_encode($body);

            Log::info('Dreams SMS send attempted', [
                'to' => $digits,
                'sender' => $sender,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $body,
            ]);

            // Dreams returns Result:SMS_ID:mobile on success, or negative codes like -116.
            if (
                ! $response->successful()
                || str_contains($bodyString, '-')
                && ! str_starts_with(ltrim($bodyString), 'Result')
            ) {
                Log::warning('Dreams SMS rejected or failed', [
                    'to' => $digits,
                    'sender' => $sender,
                    'body' => $body,
                ]);
            }

            return $body;
        }

        if ($verificationCode === null || $verificationCode === '') {
            Log::warning('Twilio Verify path requires a verification code for non-Saudi numbers.');

            return false;
        }

        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $verifyServiceSid = (string) config('services.twilio.verify_service_sid');

        if ($sid === '' || $token === '' || $verifyServiceSid === '') {
            Log::warning('Twilio Verify is not configured; skipping send.');

            return false;
        }

        $e164 = str_starts_with($to, '+') ? $to : '+'.$digits;

        $client = new TwilioClient($sid, $token);

        return $client->verify->v2->services($verifyServiceSid)
            ->verifications
            ->create($e164, 'sms', ['customCode' => $verificationCode]);
    }

    /**
     * Whether real SMS delivery is currently enabled.
     */
    public function isLive(): bool
    {
        return App::environment('production') || (bool) config('sms.live');
    }
}
