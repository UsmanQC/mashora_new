<?php

namespace App\Services;

use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * MyFatoorah Embedded Payment v3 (POST /v3/sessions + sessions/v1/session.js).
 *
 * @see https://docs.myfatoorah.com/docs/embedded-payment-v3
 */
class MyFatoorahEmbeddedV3Service
{
    /**
     * @return array{
     *     ok: bool,
     *     session_id?: string,
     *     encryption_key?: string,
     *     session_js_url?: string,
     *     message?: string
     * }
     */
    public function createCompletePaymentSession(TemporaryAppointment $temporaryAppointment, float $amountDue, User $customer): array
    {
        if ($amountDue <= 0) {
            return ['ok' => false, 'message' => __('patient_booking.wallet_covers_full')];
        }

        if (empty(config('myfatoorah.api_key'))) {
            return ['ok' => false, 'message' => __('patient_booking.payment_api_missing')];
        }

        try {
            $customerPayload = [
                'Name' => (string) ($temporaryAppointment->patient_name ?: $customer->name ?: 'Patient'),
                'Reference' => (string) $temporaryAppointment->id,
            ];

            $email = (string) ($temporaryAppointment->patient_email ?: $customer->email ?: '');
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $customerPayload['Email'] = $email;
            }

            $apiKey = trim((string) config('myfatoorah.api_key'));

            // WAMP/local PHP often lacks a CA bundle (cURL 60). Match HyperpayCheckoutService.
            $http = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(45);

            if (app()->isLocal()) {
                $http = $http->withoutVerifying();
            }

            $order = [
                'Amount' => round($amountDue, 2),
                'ExternalIdentifier' => (string) $temporaryAppointment->id,
            ];

            // Sandbox portal currency is KWD; live uses the account country currency.
            if (! (bool) config('myfatoorah.is_test')) {
                $order['Currency'] = match (strtoupper((string) config('myfatoorah.vc_code', 'SAU'))) {
                    'ARE' => 'AED',
                    'QAT' => 'QAR',
                    'EGY' => 'EGP',
                    'BHR' => 'BHD',
                    'OMN' => 'OMR',
                    'JOD' => 'JOD',
                    'KWT' => 'KWD',
                    default => 'SAR',
                };
            }

            // Omit SupportedPaymentMethods so account-enabled wallets appear (Apple Pay, GPay, etc.).
            $response = $http->post($this->apiBaseUrl().'/v3/sessions', [
                'PaymentMode' => 'COMPLETE_PAYMENT',
                'Order' => $order,
                'Customer' => $customerPayload,
                'Language' => app()->getLocale() === 'ar' ? 'AR' : 'EN',
            ]);

            /** @var array{IsSuccess?: bool, Message?: string, ValidationErrors?: mixed, Data?: array{SessionId?: string, EncryptionKey?: string}} $json */
            $json = $response->json() ?? [];

            if (! $response->successful() || ! ($json['IsSuccess'] ?? false)) {
                $message = $this->humanMessageFromResponse($response->status(), $json, $response->body());

                Log::warning('MyFatoorah v3 session failed', [
                    'status' => $response->status(),
                    'api' => $this->apiBaseUrl(),
                    'body' => $response->body(),
                ]);

                return ['ok' => false, 'message' => $message];
            }

            $sessionId = (string) ($json['Data']['SessionId'] ?? '');
            $encryptionKey = (string) ($json['Data']['EncryptionKey'] ?? '');

            if ($sessionId === '' || $encryptionKey === '') {
                return [
                    'ok' => false,
                    'message' => __('patient_booking.payment_start_failed'),
                ];
            }

            $this->storeEncryptionKey($temporaryAppointment, $encryptionKey);

            return [
                'ok' => true,
                'session_id' => $sessionId,
                'encryption_key' => $encryptionKey,
                'session_js_url' => $this->sessionJsUrl(),
            ];
        } catch (Throwable $e) {
            report($e);

            $raw = strtolower($e->getMessage());

            if (str_contains($raw, 'ssl') || str_contains($raw, 'certificate') || str_contains($raw, 'curl error 60')) {
                return ['ok' => false, 'message' => __('patient_booking.payment_ssl_local')];
            }

            $message = __('patient_booking.payment_start_failed');

            if (app()->isLocal() || app()->hasDebugModeEnabled()) {
                $message .= ' ('.$e->getMessage().')';
            }

            return ['ok' => false, 'message' => $message];
        }
    }

    public function storeEncryptionKey(TemporaryAppointment $temporaryAppointment, string $encryptionKey): void
    {
        Cache::put($this->encryptionCacheKey($temporaryAppointment), $encryptionKey, now()->addDay());
    }

    public function encryptionKeyFor(TemporaryAppointment $temporaryAppointment): ?string
    {
        $key = Cache::get($this->encryptionCacheKey($temporaryAppointment));

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * Decrypt COMPLETE_PAYMENT callback paymentData (AES-128-CBC, key/IV from EncryptionKey UTF-8 bytes).
     *
     * @return array<string, mixed>|null
     */
    public function decryptPaymentData(string $encryptedText, string $encryptionKey): ?array
    {
        try {
            $encryptedBytes = base64_decode($encryptedText, true);

            if ($encryptedBytes === false) {
                return null;
            }

            $passBytes = $encryptionKey;
            $keyIv = str_repeat("\0", 16);
            $len = min(strlen($passBytes), 16);

            for ($i = 0; $i < $len; $i++) {
                $keyIv[$i] = $passBytes[$i];
            }

            $decrypted = openssl_decrypt($encryptedBytes, 'AES-128-CBC', $keyIv, OPENSSL_RAW_DATA, $keyIv);

            if (! is_string($decrypted) || $decrypted === '') {
                return null;
            }

            /** @var array<string, mixed>|null $payload */
            $payload = json_decode($decrypted, true);

            return is_array($payload) ? $payload : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function isPaidPayload(array $payload): bool
    {
        $invoiceStatus = strtoupper((string) data_get($payload, 'Invoice.Status', ''));
        $transactionStatus = strtoupper((string) data_get($payload, 'Transaction.Status', ''));

        return $invoiceStatus === 'PAID' || $transactionStatus === 'SUCCESS';
    }

    public function sessionJsUrl(): string
    {
        if ((bool) config('myfatoorah.is_test')) {
            return 'https://demo.myfatoorah.com/sessions/v1/session.js';
        }

        return match (strtoupper((string) config('myfatoorah.vc_code', 'SAU'))) {
            'ARE' => 'https://ae.myfatoorah.com/sessions/v1/session.js',
            'QAT' => 'https://qa.myfatoorah.com/sessions/v1/session.js',
            'EGY' => 'https://eg.myfatoorah.com/sessions/v1/session.js',
            'SAU' => 'https://sa.myfatoorah.com/sessions/v1/session.js',
            default => 'https://portal.myfatoorah.com/sessions/v1/session.js',
        };
    }

    public function apiBaseUrl(): string
    {
        if ((bool) config('myfatoorah.is_test')) {
            return 'https://apitest.myfatoorah.com';
        }

        return match (strtoupper((string) config('myfatoorah.vc_code', 'SAU'))) {
            'ARE' => 'https://api-ae.myfatoorah.com',
            'QAT' => 'https://api-qa.myfatoorah.com',
            'EGY' => 'https://api-eg.myfatoorah.com',
            'SAU' => 'https://api-sa.myfatoorah.com',
            default => 'https://api.myfatoorah.com',
        };
    }

    /**
     * @param  array{Message?: string, ValidationErrors?: mixed}  $json
     */
    private function humanMessageFromResponse(int $status, array $json, string $rawBody): string
    {
        $apiMessage = trim((string) ($json['Message'] ?? ''));

        if (is_array($json['ValidationErrors'] ?? null) && $json['ValidationErrors'] !== []) {
            $parts = [];
            foreach ($json['ValidationErrors'] as $error) {
                if (is_array($error)) {
                    $parts[] = trim((string) ($error['Error'] ?? $error['Name'] ?? json_encode($error)));
                } elseif (is_string($error)) {
                    $parts[] = $error;
                }
            }

            $joined = implode(' · ', array_filter($parts));
            if ($joined !== '') {
                return $joined;
            }
        }

        if ($apiMessage !== '') {
            return $apiMessage;
        }

        if ($status === 401 || $status === 403) {
            return __('patient_booking.payment_gateway_misconfigured');
        }

        if (app()->isLocal() || app()->hasDebugModeEnabled()) {
            return __('patient_booking.payment_start_failed').' (HTTP '.$status.')';
        }

        return __('patient_booking.payment_start_failed');
    }

    private function encryptionCacheKey(TemporaryAppointment $temporaryAppointment): string
    {
        return 'mf.v3.enc.'.$temporaryAppointment->id;
    }
}
