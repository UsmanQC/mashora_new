<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\TemporaryAppointment;
use App\Services\HyperpayCheckoutService;
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

        $temporaryAppointment->refresh();

        $alreadyBooked = $this->resolveBookedAppointment($temporaryAppointment);

        if ($alreadyBooked !== null) {
            return view('patient.payment-success', [
                'temporaryAppointment' => $temporaryAppointment,
                'appointment' => $alreadyBooked,
            ]);
        }

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

    public function failed(Request $request, TemporaryAppointment $temporaryAppointment): RedirectResponse|View
    {
        abort_unless($temporaryAppointment->user_id === auth()->id(), 403);

        $temporaryAppointment->refresh();

        $alreadyBooked = $this->resolveBookedAppointment($temporaryAppointment);

        if ($alreadyBooked !== null) {
            return view('patient.payment-success', [
                'temporaryAppointment' => $temporaryAppointment,
                'appointment' => $alreadyBooked,
            ]);
        }

        if (HyperpayCheckoutService::requestHasReturnParameters($request)) {
            /** @var PatientPaymentCompletionService $completion */
            $completion = app(PatientPaymentCompletionService::class);
            $result = $completion->confirmIfPaid($temporaryAppointment, $request);

            if ($result['state'] === 'paid' && $result['appointment'] !== null) {
                return view('patient.payment-success', [
                    'temporaryAppointment' => $temporaryAppointment->fresh(),
                    'appointment' => $result['appointment'],
                ]);
            }
        }

        return view('patient.payment-failed', [
            'temporaryAppointment' => $temporaryAppointment,
        ]);
    }

    private function resolveBookedAppointment(TemporaryAppointment $temporaryAppointment): ?Appointment
    {
        if ($temporaryAppointment->payment_status !== 'paid' || $temporaryAppointment->appointment_id === null) {
            return null;
        }

        return Appointment::query()->find($temporaryAppointment->appointment_id);
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
                'InvoiceValue' => PatientPaymentCompletionService::amountDue($tempAppointment),
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

            if (app()->isLocal() && str_contains(strtolower($e->getMessage()), 'ssl certificate')) {
                /** @var PatientPaymentCompletionService $completion */
                $completion = app(PatientPaymentCompletionService::class);
                $appointment = $completion->forceCompleteForTesting($tempAppointment);

                if ($appointment !== null) {
                    return Redirect::route('patient.payment.success', $tempAppointment)
                        ->with('flash_payment', __('patient_booking.payment_local_ssl_fallback'));
                }
            }
        }

        return Redirect::route('patient.payment.failed', $tempAppointment);
    }
}
