<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
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
     * @return array{ok: bool, provider: string|null, body: mixed, error: string|null}
     */
    public function send(string $message, string $to, ?string $verificationCode = null): array
    {
        $digits = preg_replace('/\D/', '', $to) ?? '';

        if (! $this->isLive()) {
            return [
                'ok' => true,
                'provider' => null,
                'body' => true,
                'error' => null,
            ];
        }

        try {
            if (str_starts_with($digits, '966')) {
                return $this->sendViaDreams($digits, $message);
            }

            return $this->sendViaTwilio($to, $digits, $verificationCode);
        } catch (Throwable $e) {
            $this->debugLog('SMS send exception', [
                'to' => $digits,
                'error' => $e->getMessage(),
            ]);

            report($e);

            return [
                'ok' => false,
                'provider' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok: bool, provider: string, body: mixed, error: string|null}
     */
    private function sendViaDreams(string $digits, string $message): array
    {
        $user = (string) config('sms.user');
        $secret = (string) config('sms.secret_key');
        $sender = (string) config('sms.sender');

        if ($user === '' || $secret === '') {
            $error = 'Dreams SMS is not configured (DREAMS_SMS_USER / DREAMS_SMS_SECRET_KEY).';
            $this->debugLog($error, ['to' => $digits]);

            return [
                'ok' => false,
                'provider' => 'dreams',
                'body' => null,
                'error' => $error,
            ];
        }

        $url = 'https://www.dreams.sa/index.php/api/sendsms/'
            .'?user='.urlencode($user)
            .'&secret_key='.urlencode($secret)
            .'&to='.urlencode($digits)
            .'&message='.urlencode($message)
            .'&sender='.urlencode($sender);

        // Local WAMP often fails Dreams SSL verification; skip verify outside production.
        $http = Http::timeout(30);
        if (! App::environment('production')) {
            $http = $http->withoutVerifying();
        }

        $response = $http->get($url);
        $body = $response->json() ?? $response->body();
        $bodyString = trim(is_scalar($body) ? (string) $body : (string) json_encode($body));

        $ok = $response->successful() && (
            str_starts_with($bodyString, 'Result')
            || str_contains($bodyString, 'Result:')
        );

        $this->debugLog('Dreams SMS response', [
            'to' => $digits,
            'sender' => $sender,
            'http_status' => $response->status(),
            'ok' => $ok,
            'body' => $bodyString,
        ]);

        return [
            'ok' => $ok,
            'provider' => 'dreams',
            'body' => $body,
            'error' => $ok ? null : $this->dreamsErrorMessage($bodyString),
        ];
    }

    /**
     * @return array{ok: bool, provider: string, body: mixed, error: string|null}
     */
    private function sendViaTwilio(string $to, string $digits, ?string $verificationCode): array
    {
        if ($verificationCode === null || $verificationCode === '') {
            $error = 'Twilio Verify path requires a verification code for non-Saudi numbers.';
            $this->debugLog($error, ['to' => $digits]);

            return [
                'ok' => false,
                'provider' => 'twilio',
                'body' => null,
                'error' => $error,
            ];
        }

        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $verifyServiceSid = (string) config('services.twilio.verify_service_sid');

        if ($sid === '' || $token === '' || $verifyServiceSid === '') {
            $error = 'Twilio Verify is not configured; non-Saudi numbers cannot receive OTP.';
            $this->debugLog($error, ['to' => $digits]);

            return [
                'ok' => false,
                'provider' => 'twilio',
                'body' => null,
                'error' => $error,
            ];
        }

        $e164 = str_starts_with($to, '+') ? $to : '+'.$digits;

        $client = new TwilioClient($sid, $token);
        $result = $client->verify->v2->services($verifyServiceSid)
            ->verifications
            ->create($e164, 'sms', ['customCode' => $verificationCode]);

        $this->debugLog('Twilio Verify sent', [
            'to' => $e164,
            'sid' => $result->sid ?? null,
        ]);

        return [
            'ok' => true,
            'provider' => 'twilio',
            'body' => $result,
            'error' => null,
        ];
    }

    private function dreamsErrorMessage(string $body): string
    {
        $code = trim($body);

        return match (true) {
            str_contains($code, '-110') => 'Dreams SMS: wrong username or secret key (-110).',
            str_contains($code, '-111') => 'Dreams SMS: account not activated (-111).',
            str_contains($code, '-112') => 'Dreams SMS: account blocked (-112).',
            str_contains($code, '-113') => 'Dreams SMS: not enough balance (-113).',
            str_contains($code, '-115'), str_contains($code, '-116') => 'Dreams SMS: sender name not allowed (-115/-116). Check DREAMS_SMS_SENDER.',
            str_contains($code, '-122') => 'Dreams SMS: number not allowed (-122).',
            str_contains($code, '-124') => 'Dreams SMS: server IP not allowed (-124). Whitelist this server IP in Dreams.',
            default => 'Dreams SMS failed: '.$code,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function debugLog(string $message, array $context = []): void
    {
        try {
            Log::warning($message, $context);
        } catch (Throwable) {
            // Logging must never break OTP flow on live.
        }

        try {
            $line = '['.now()->toDateTimeString().'] '.$message.' '.json_encode($context, JSON_UNESCAPED_UNICODE).PHP_EOL;
            @file_put_contents(storage_path('logs/sms-debug.log'), $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable) {
            //
        }
    }

    /**
     * Whether real SMS delivery is currently enabled.
     */
    public function isLive(): bool
    {
        return App::environment('production') || (bool) config('sms.live');
    }
}
