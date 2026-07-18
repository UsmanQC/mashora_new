<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class MyFatoorahRefundService
{
    /**
     * @return array{refund_invoice_id: string|null, response: array<string, mixed>}
     */
    public function refundAppointment(Appointment $appointment, float $amount, ?string $comment = null): array
    {
        $apiKey = trim((string) config('myfatoorah.api_key'));

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'refund' => __('patient_booking.payment_api_missing'),
            ]);
        }

        $invoiceId = trim((string) ($appointment->resolvedPaymentInvoiceId() ?? ''));

        if ($invoiceId === '') {
            throw ValidationException::withMessages([
                'refund' => __('patient.missed.refund_account_missing'),
            ]);
        }

        // Keep appointment in sync when the invoice only existed on temporary_appointments.
        if ((string) ($appointment->payment_invoice_id ?? '') !== $invoiceId) {
            $appointment->forceFill(['payment_invoice_id' => $invoiceId])->save();
        }

        $refundAmount = round(max(0.01, $amount), 2);

        $http = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(45);

        if (app()->isLocal()) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post($this->apiBaseUrl().'/v2/MakeRefund', [
            'KeyType' => 'InvoiceId',
            'Key' => $invoiceId,
            'ServiceChargeOnCustomer' => false,
            'Amount' => $refundAmount,
            'Comment' => $comment ?: 'Appointment refund request #'.$appointment->id,
            'ExternalIdentifier' => 'APPOINTMENT-REFUND-'.$appointment->id,
        ]);

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        if (! $response->successful() || ! ((bool) ($json['IsSuccess'] ?? false))) {
            $message = trim((string) ($json['Message'] ?? ''));

            throw ValidationException::withMessages([
                'refund' => $message !== '' ? $message : __('patient.missed.refund_account_failed'),
            ]);
        }

        return [
            'refund_invoice_id' => data_get($json, 'Data.RefundInvoiceId')
                ? (string) data_get($json, 'Data.RefundInvoiceId')
                : null,
            'response' => $json,
        ];
    }

    private function apiBaseUrl(): string
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
}
