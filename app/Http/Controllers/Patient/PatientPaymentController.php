<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\TemporaryAppointment;
use App\Services\PatientPaymentCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use Throwable;

class PatientPaymentController extends Controller
{
    public function success(Request $request, TemporaryAppointment $temporaryAppointment): RedirectResponse|View
    {
        abort_unless($temporaryAppointment->user_id === auth()->id(), 403);

        /** @var PatientPaymentCompletionService $completion */
        $completion = app(PatientPaymentCompletionService::class);
        $result = $completion->confirmIfPaid($temporaryAppointment, $request);

        if ($result['state'] === 'paid' && $result['appointment'] !== null) {
            return view('patient.payment-success', [
                'temporaryAppointment' => $temporaryAppointment->fresh(),
                'appointment' => $result['appointment'],
            ]);
        }

        if ($result['state'] === 'needs_config') {
            return Redirect::route('patient.checkout', $temporaryAppointment)
                ->with('flash_payment', __('patient_booking.payment_pending_configure'));
        }

        if ($result['state'] === 'pending') {
            return Redirect::route('patient.checkout', $temporaryAppointment)
                ->with('flash_payment', __('patient_booking.payment_still_pending'));
        }

        return Redirect::route('patient.payment.failed', $temporaryAppointment);
    }

    public function failed(TemporaryAppointment $temporaryAppointment): View
    {
        abort_unless($temporaryAppointment->user_id === auth()->id(), 403);

        return view('patient.payment-failed', [
            'temporaryAppointment' => $temporaryAppointment,
        ]);
    }

    /**
     * Embedded card flow: uses InitiateSession + ExecutePayment invoice URL (Mashorapwa-prod parity).
     */
    public function executePayment(TemporaryAppointment $temporaryAppointment): RedirectResponse
    {
        abort_unless($temporaryAppointment->user_id === auth()->id(), 403);

        if (empty(config('myfatoorah.api_key'))) {
            return Redirect::route('patient.checkout', $temporaryAppointment)
                ->with('flash_payment', __('patient_booking.payment_api_missing'));
        }

        $tempAppointment = TemporaryAppointment::query()
            ->where('id', $temporaryAppointment->id)
            ->where('user_id', auth()->id())
            ->whereNotNull('payment_session_id')
            ->firstOrFail();

        $mfConfig = [
            'apiKey' => (string) config('myfatoorah.api_key'),
            'isTest' => (bool) config('myfatoorah.is_test'),
            'vcCode' => (string) config('myfatoorah.vc_code'),
        ];

        try {
            $paymentData = [
                'SessionId' => $tempAppointment->payment_session_id,
                'CustomerName' => (string) $tempAppointment->patient_name,
                'InvoiceValue' => (float) $tempAppointment->total,
                'CallBackUrl' => route('patient.payment.success', ['temporaryAppointment' => $tempAppointment->id]),
                'ErrorUrl' => route('patient.payment.failed', ['temporaryAppointment' => $tempAppointment->id]),
                'CustomerReference' => $tempAppointment->id,
                'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ];

            $mfObj = new MyFatoorahPayment($mfConfig);
            $mfInvoiceData = $mfObj->getInvoiceURL($paymentData, 0, null, $tempAppointment->payment_session_id);

            if (! empty($mfInvoiceData['invoiceURL'])) {
                $tempAppointment->payment_invoice_url = $mfInvoiceData['invoiceURL'];
                $tempAppointment->payment_invoice_id = isset($mfInvoiceData['invoiceId']) ? (string) $mfInvoiceData['invoiceId'] : null;
                $tempAppointment->save();

                return Redirect::away($mfInvoiceData['invoiceURL']);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return Redirect::route('patient.payment.failed', $tempAppointment);
    }
}
