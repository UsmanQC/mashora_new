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
     * @return mixed Response payload from provider, or true when not in production (dev/testing).
     */
    public function send(string $message, string $to, ?string $verificationCode = null): mixed
    {
        $digits = preg_replace('/\D/', '', $to) ?? '';

        if (! App::environment('production')) {
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

            $response = Http::timeout(30)->get($url);

            return $response->json() ?? $response->body();
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
}
