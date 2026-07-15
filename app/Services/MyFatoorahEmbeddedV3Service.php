<?php

namespace App\Services;

use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * MyFatoorah Embedded Payment v3 (POST /v3/sessions + sessions/v1/session.js).
 *
 * @see https://docs.myfatoorah.com/docs/embedded-payment-v3
 */
class MyFatoorahEmbeddedV3Service
{
    /**
     * @return array{session_id: string, encryption_key: string, session_js_url: string}|null
     */
    public function createCompletePaymentSession(TemporaryAppointment $temporaryAppointment, float $amountDue, User $customer): ?array
    {
        if ($amountDue <= 0 || empty(config('myfatoorah.api_key'))) {
            return null;
        }

        try {
            $response = Http::withToken((string) config('myfatoorah.api_key'))
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->apiBaseUrl().'/v3/sessions', [
                    'PaymentMode' => 'COMPLETE_PAYMENT',
                    'Order' => [
                        'Amount' => $amountDue,
                        'Currency' => 'SAR',
                        'ExternalIdentifier' => (string) $temporaryAppointment->id,
                    ],
                    'Customer' => [
                        'Name' => (string) ($temporaryAppointment->patient_name ?: $customer->name ?: 'Patient'),
                        'Reference' => (string) $temporaryAppointment->id,
                        'Email' => (string) ($temporaryAppointment->patient_email ?: $customer->email ?: ''),
                    ],
                    'IntegrationUrls' => [
                        'Redirection' => route('patient.payment.success', ['temporaryAppointment' => $temporaryAppointment->id]),
                    ],
                    'Language' => app()->getLocale() === 'ar' ? 'AR' : 'EN',
                    'SupportedPaymentMethods' => ['card', 'applepay', 'googlepay', 'stcpay'],
                    'SupportedNetworks' => ['visa', 'masterCard', 'mada', 'amex'],
                ]);

            if (! $response->successful()) {
                report(new RuntimeException('MyFatoorah v3 session failed: '.$response->body()));

                return null;
            }

            /** @var array{IsSuccess?: bool, Data?: array{SessionId?: string, EncryptionKey?: string}} $json */
            $json = $response->json();

            if (! ($json['IsSuccess'] ?? false)) {
                report(new RuntimeException('MyFatoorah v3 session rejected: '.$response->body()));

                return null;
            }

            $sessionId = (string) ($json['Data']['SessionId'] ?? '');
            $encryptionKey = (string) ($json['Data']['EncryptionKey'] ?? '');

            if ($sessionId === '' || $encryptionKey === '') {
                return null;
            }

            $this->storeEncryptionKey($temporaryAppointment, $encryptionKey);

            return [
                'session_id' => $sessionId,
                'encryption_key' => $encryptionKey,
                'session_js_url' => $this->sessionJsUrl(),
            ];
        } catch (Throwable $e) {
            report($e);

            return null;
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

    private function encryptionCacheKey(TemporaryAppointment $temporaryAppointment): string
    {
        return 'mf.v3.enc.'.$temporaryAppointment->id;
    }
}
