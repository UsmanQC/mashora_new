<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TemporaryAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * HyperPay OPPWA COPYandPAY: checkout session init and payment result verification.
 */
class HyperpayCheckoutService
{
    protected string $env;

    protected string $entityId;

    protected string $authorization;

    protected string $apiBaseUrl;

    protected string $checkoutUrl;

    protected string $widgetHost;

    public function __construct()
    {
        $this->env = (string) config('hyperpay.env', 'test');
        $envKey = in_array($this->env, ['test', 'dev'], true) ? 'test' : 'live';

        $this->entityId = self::configuredEntityId();
        $this->authorization = 'Bearer '.((string) config('hyperpay.token', ''));
        $this->apiBaseUrl = (string) config("hyperpay.{$envKey}.api_base_url", 'https://eu-test.oppwa.com/');
        $this->checkoutUrl = (string) config("hyperpay.{$envKey}.checkout_url", '');
        $this->widgetHost = (string) config("hyperpay.{$envKey}.widget_host", 'oppwa.com');
    }

    public function isConfigured(): bool
    {
        return filled(config('hyperpay.token')) && filled(self::configuredEntityId());
    }

    public static function configuredEntityId(): string
    {
        $mode = strtolower((string) config('hyperpay.entity_mode', 'b2b'));

        if ($mode === 'b2b') {
            return (string) config('hyperpay.entity_id_b2b', '');
        }

        return (string) config('hyperpay.entity_id_b2c', '');
    }

    public function getEnvironment(): string
    {
        return $this->env;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public static function requestHasReturnParameters(Request $request): bool
    {
        return $request->filled('resourcePath')
            || $request->filled('checkoutId')
            || $request->filled('id');
    }

    /**
     * @return array{checkout_id: string, integrity: ?string, entity_id: string, env: string, merchant_transaction_id: string, callback_url: string}
     */
    public function initBookingCheckout(TemporaryAppointment $temporaryAppointment, float $amountDue): array
    {
        $merchantTransactionId = $this->generateMerchantTransactionId('BOOK', (string) $temporaryAppointment->id);

        $callbackUrl = route('patient.payment.success', [
            'temporaryAppointment' => $temporaryAppointment->id,
        ]);

        $checkout = $this->createCheckout(
            amount: $amountDue,
            merchantTransactionId: $merchantTransactionId,
            customerName: (string) ($temporaryAppointment->patient_name ?: 'Patient'),
            customerEmail: (string) ($temporaryAppointment->patient_email ?: $this->fallbackEmail($temporaryAppointment->user_id)),
        );

        $temporaryAppointment->forceFill([
            'payment_session_id' => $checkout['checkout_id'],
            'payment_invoice_id' => $merchantTransactionId,
        ])->save();

        return [
            ...$checkout,
            'merchant_transaction_id' => $merchantTransactionId,
            'callback_url' => $callbackUrl,
        ];
    }

    /**
     * @return array{checkout_id: string, integrity: ?string, entity_id: string, env: string, merchant_transaction_id: string, callback_url: string}
     */
    public function initFollowUpCheckout(Appointment $appointment, float $amountDue): array
    {
        $merchantTransactionId = $this->generateMerchantTransactionId('FOLLOWUP', (string) $appointment->id);

        $callbackUrl = route('patient.follow-up.payment.success', $appointment);

        $checkout = $this->createCheckout(
            amount: $amountDue,
            merchantTransactionId: $merchantTransactionId,
            customerName: (string) ($appointment->patient_name ?: 'Patient'),
            customerEmail: (string) ($appointment->patient_email ?: $this->fallbackEmail($appointment->user_id)),
        );

        $appointment->forceFill([
            'payment_session_id' => $checkout['checkout_id'],
            'payment_invoice_id' => $merchantTransactionId,
        ])->save();

        return [
            ...$checkout,
            'merchant_transaction_id' => $merchantTransactionId,
            'callback_url' => $callbackUrl,
        ];
    }

    public function widgetScriptUrl(string $checkoutId): string
    {
        return "https://{$this->widgetHost}/v1/paymentWidgets.js?checkoutId={$checkoutId}";
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPaymentResult(?string $checkoutId = null, ?string $entityId = null, ?string $resourcePath = null): array
    {
        $entityId = $entityId ?? $this->entityId;
        $url = $this->paymentStatusUrl($checkoutId, $entityId, $resourcePath);

        $this->logApiRequest('payment_status', 'GET', $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: '.$this->authorization,
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, ! app()->isLocal());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $curlError = curl_errno($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('Failed to connect to HyperPay: '.curl_strerror($curlError));
        }

        $responseData = json_decode((string) $response, true);

        if (! is_array($responseData)) {
            $this->logApiResponse('payment_status', 'GET', $url, null, $httpStatus, (string) $response);

            throw new RuntimeException('Invalid response from HyperPay');
        }

        $this->logApiResponse('payment_status', 'GET', $url, $responseData, $httpStatus);

        return $responseData;
    }

    public function paymentStatusUrl(?string $checkoutId, string $entityId, ?string $resourcePath = null): string
    {
        if (filled($resourcePath)) {
            $path = str_starts_with($resourcePath, '/') ? $resourcePath : '/'.$resourcePath;

            return rtrim($this->apiBaseUrl, '/').$path.'?entityId='.urlencode($entityId);
        }

        if (! filled($checkoutId)) {
            throw new RuntimeException('HyperPay checkout id or resource path is required.');
        }

        return rtrim($this->apiBaseUrl, '/').'/v1/checkouts/'.urlencode($checkoutId).'/payment?entityId='.urlencode($entityId);
    }

    public function isPaymentSuccessful(?string $statusCode): bool
    {
        return $this->getPaymentStatus($statusCode) === 'success';
    }

    /**
     * @return 'success'|'processing'|'pending'|'failed'
     */
    public function getPaymentStatus(?string $code): string
    {
        if (! filled($code)) {
            return 'failed';
        }

        if (preg_match('/^(000\.200)/', $code)) {
            return 'processing';
        }

        if (
            preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $code) ||
            preg_match('/^(000\.400\.0|000\.400\.100)/', $code)
        ) {
            return 'success';
        }

        if (preg_match('/^(800\.400\.5|100\.400\.500)/', $code)) {
            return 'pending';
        }

        return 'failed';
    }

    public function paymentReferenceId(array $responseData): string
    {
        $paymentId = data_get($responseData, 'id');

        if (is_string($paymentId) && $paymentId !== '') {
            return $paymentId;
        }

        $merchantTransactionId = data_get($responseData, 'merchantTransactionId');

        if (is_string($merchantTransactionId) && $merchantTransactionId !== '') {
            return $merchantTransactionId;
        }

        return '';
    }

    public function responseBelongsToBooking(array $responseData, TemporaryAppointment $temporaryAppointment): bool
    {
        $merchantTransactionId = (string) data_get($responseData, 'merchantTransactionId', '');
        $stored = (string) ($temporaryAppointment->payment_invoice_id ?? '');

        if ($stored !== '' && $merchantTransactionId !== '' && $stored === $merchantTransactionId) {
            return true;
        }

        return str_contains($merchantTransactionId, 'BOOK_'.$temporaryAppointment->id);
    }

    public function responseBelongsToFollowUp(array $responseData, Appointment $appointment): bool
    {
        $merchantTransactionId = (string) data_get($responseData, 'merchantTransactionId', '');
        $stored = (string) ($appointment->payment_invoice_id ?? '');

        if ($stored !== '' && $merchantTransactionId !== '' && $stored === $merchantTransactionId) {
            return true;
        }

        return str_contains($merchantTransactionId, 'FOLLOWUP_'.$appointment->id);
    }

    /**
     * @return array{checkout_id: string, integrity: ?string, entity_id: string, env: string}
     */
    private function createCheckout(
        float $amount,
        string $merchantTransactionId,
        string $customerName,
        string $customerEmail,
    ): array {
        $nameParts = explode(' ', trim($customerName), 2);
        $firstName = $nameParts[0] !== '' ? $nameParts[0] : 'Patient';
        $lastName = $nameParts[1] ?? 'Patient';

        $payload = [
            'entityId' => $this->entityId,
            'paymentType' => 'DB',
            'currency' => 'SAR',
            'amount' => number_format($amount, 2, '.', ''),
            'merchantTransactionId' => $merchantTransactionId,
            'customer.email' => $customerEmail,
            'customer.givenName' => $firstName,
            'customer.surname' => $lastName,
            'billing.street1' => 'Riyadh',
            'billing.city' => 'Riyadh',
            'billing.state' => 'Riyadh',
            'billing.country' => 'SA',
            'billing.postcode' => '12211',
        ];

        if (in_array($this->env, ['test', 'dev'], true)) {
            $payload['integrity'] = 'true';
            $payload['testMode'] = 'EXTERNAL';
            $payload['customParameters[3DS2_enrolled]'] = 'true';
        }

        $this->logApiRequest('create_checkout', 'POST', $this->checkoutUrl, $payload);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authorization,
            ])
                ->asForm()
                ->timeout(30)
                ->post($this->checkoutUrl, $payload);
        } catch (Throwable $e) {
            Log::error('HyperPay create checkout transport failed', [
                'message' => $e->getMessage(),
                'url' => $this->checkoutUrl,
            ]);

            throw new RuntimeException(__('patient_booking.payment_start_failed'), 0, $e);
        }

        $responseData = $response->json();

        if (! is_array($responseData)) {
            $this->logApiResponse('create_checkout', 'POST', $this->checkoutUrl, null, $response->status(), $response->body());

            throw new RuntimeException(__('patient_booking.payment_start_failed'));
        }

        $this->logApiResponse('create_checkout', 'POST', $this->checkoutUrl, $responseData, $response->status());

        $resultCode = (string) data_get($responseData, 'result.code', '');

        if ($response->failed() || ! in_array($resultCode, ['000.200.000', '000.200.100'], true)) {
            $message = (string) (data_get($responseData, 'result.description') ?: __('patient_booking.payment_start_failed'));

            throw new RuntimeException($message);
        }

        $checkoutId = (string) data_get($responseData, 'id', '');

        if ($checkoutId === '') {
            throw new RuntimeException(__('patient_booking.payment_start_failed'));
        }

        return [
            'checkout_id' => $checkoutId,
            'integrity' => data_get($responseData, 'integrity'),
            'entity_id' => $this->entityId,
            'env' => $this->env,
        ];
    }

    private function generateMerchantTransactionId(string $prefix, string $referenceId): string
    {
        $sanitizedReference = substr(preg_replace('/[^a-zA-Z0-9]/', '', $referenceId) ?? '', 0, 16);

        if ($sanitizedReference === '') {
            $sanitizedReference = (string) random_int(100000, 999999);
        }

        return 'MSH_'.$prefix.'_'.$sanitizedReference.'_'.now()->format('YmdHis').random_int(1000, 9999);
    }

    private function fallbackEmail(?int $userId): string
    {
        $domain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'mashora.test';

        return 'patient-'.($userId ?? 0).'-'.random_int(100, 99999).'@'.$domain;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logApiRequest(string $operation, string $method, string $url, array $payload = []): void
    {
        Log::info('HyperPay API request', [
            'operation' => $operation,
            'method' => $method,
            'url' => $url,
            'env' => $this->env,
            'entity_id' => $this->entityId,
            'entity_mode' => config('hyperpay.entity_mode'),
            'payload' => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function logApiResponse(
        string $operation,
        string $method,
        string $url,
        ?array $body,
        ?int $httpStatus = null,
        ?string $rawBody = null,
    ): void {
        Log::info('HyperPay API response', [
            'operation' => $operation,
            'method' => $method,
            'url' => $url,
            'http_status' => $httpStatus,
            'result_code' => data_get($body, 'result.code'),
            'result_description' => data_get($body, 'result.description'),
            'parameter_errors' => data_get($body, 'result.parameterErrors'),
            'checkout_id' => data_get($body, 'id') ?? data_get($body, 'ndc'),
            'merchant_transaction_id' => data_get($body, 'merchantTransactionId'),
            'payment_brand' => data_get($body, 'paymentBrand'),
            'amount' => data_get($body, 'amount'),
            'currency' => data_get($body, 'currency'),
            'body' => $body ?? $rawBody,
        ]);
    }
}
