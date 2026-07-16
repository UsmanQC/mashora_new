<?php

namespace App\Services;

use App\Models\TemporaryAppointment;
use App\Models\User;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use Throwable;

/**
 * Creates MyFatoorah hosted payment invoices for patient booking checkout.
 */
class MyFatoorahInvoiceService
{
    /**
     * @return array{invoice_url: string, invoice_id: ?string}|null
     */
    public function createBookingInvoice(TemporaryAppointment $temporaryAppointment, float $amountDue, User $customer): ?array
    {
        if ($amountDue <= 0 || empty(config('myfatoorah.api_key'))) {
            return null;
        }

        try {
            $mfObj = new MyFatoorahPayment([
                'apiKey' => (string) config('myfatoorah.api_key'),
                'isTest' => (bool) config('myfatoorah.is_test'),
                'vcCode' => (string) config('myfatoorah.vc_code'),
            ]);

            $data = $mfObj->sendPayment([
                'NotificationOption' => 'LNK',
                'CustomerName' => (string) ($temporaryAppointment->patient_name ?: $customer->name ?: 'Patient'),
                'InvoiceValue' => $amountDue,
                'DisplayCurrencyIso' => 'SAR',
                'CallBackUrl' => route('patient.payment.success', ['temporaryAppointment' => $temporaryAppointment->id]),
                'ErrorUrl' => route('patient.payment.failed', ['temporaryAppointment' => $temporaryAppointment->id]),
                'CustomerReference' => $temporaryAppointment->id,
                'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ]);

            $invoiceUrl = $data->InvoiceURL ?? null;
            $invoiceId = $data->InvoiceId ?? null;

            if (! is_string($invoiceUrl) || $invoiceUrl === '') {
                return null;
            }

            return [
                'invoice_url' => $invoiceUrl,
                'invoice_id' => $invoiceId !== null ? (string) $invoiceId : null,
            ];
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
