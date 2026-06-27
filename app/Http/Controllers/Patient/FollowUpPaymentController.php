<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\FollowUpPaymentCompletionService;
use App\Services\HyperpayCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use Throwable;

class FollowUpPaymentController extends Controller
{
    public function success(Request $request, Appointment $appointment): RedirectResponse|View
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        /** @var FollowUpPaymentCompletionService $completion */
        $completion = app(FollowUpPaymentCompletionService::class);
        $result = $completion->confirmIfPaid($appointment, $request);

        if ($result['state'] === 'paid' && $result['appointment'] !== null) {
            return view('patient.payment-success', [
                'temporaryAppointment' => null,
                'appointment' => $result['appointment'],
            ]);
        }

        if ($result['state'] === 'needs_config') {
            return Redirect::route('patient.follow-up.pay', $appointment)
                ->with('flash_payment', __('patient_booking.payment_pending_configure'));
        }

        if ($result['state'] === 'pending') {
            return Redirect::route('patient.follow-up.pay', $appointment)
                ->with('flash_payment', __('patient_booking.payment_still_pending'));
        }

        return Redirect::route('patient.follow-up.payment.failed', $appointment);
    }

    public function failed(Request $request, Appointment $appointment): RedirectResponse|View
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        if ($appointment->status === 'new') {
            return view('patient.payment-success', [
                'temporaryAppointment' => null,
                'appointment' => $appointment->fresh(),
            ]);
        }

        if (HyperpayCheckoutService::requestHasReturnParameters($request)) {
            /** @var FollowUpPaymentCompletionService $completion */
            $completion = app(FollowUpPaymentCompletionService::class);
            $result = $completion->confirmIfPaid($appointment->fresh(), $request);

            if ($result['state'] === 'paid' && $result['appointment'] !== null) {
                return view('patient.payment-success', [
                    'temporaryAppointment' => null,
                    'appointment' => $result['appointment'],
                ]);
            }
        }

        return view('patient.payment-failed', [
            'temporaryAppointment' => null,
            'appointment' => $appointment,
        ]);
    }

    public function executePayment(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === auth()->id(), 403);
        abort_unless($appointment->isPendingFollowUp() && $appointment->patient_confirmed_at !== null, 404);

        if (empty(config('myfatoorah.api_key'))) {
            return Redirect::route('patient.follow-up.pay', $appointment)
                ->with('flash_payment', __('patient_booking.payment_api_missing'));
        }

        $appointment = Appointment::query()
            ->whereKey($appointment->id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending_follow_up')
            ->whereNotNull('payment_session_id')
            ->firstOrFail();

        $mfConfig = [
            'apiKey' => (string) config('myfatoorah.api_key'),
            'isTest' => (bool) config('myfatoorah.is_test'),
            'vcCode' => (string) config('myfatoorah.vc_code'),
        ];

        try {
            $paymentData = [
                'SessionId' => $appointment->payment_session_id,
                'CustomerName' => (string) $appointment->patient_name,
                'InvoiceValue' => FollowUpPaymentCompletionService::amountDue($appointment),
                'CallBackUrl' => route('patient.follow-up.payment.success', $appointment),
                'ErrorUrl' => route('patient.follow-up.payment.failed', $appointment),
                'CustomerReference' => 'FOLLOWUP-'.$appointment->id,
                'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ];

            $mfObj = new MyFatoorahPayment($mfConfig);
            $mfInvoiceData = $mfObj->getInvoiceURL($paymentData, 0, null, $appointment->payment_session_id);

            if (! empty($mfInvoiceData['invoiceURL'])) {
                $appointment->payment_invoice_url = $mfInvoiceData['invoiceURL'];
                $appointment->payment_invoice_id = isset($mfInvoiceData['invoiceId']) ? (string) $mfInvoiceData['invoiceId'] : null;
                $appointment->save();

                return Redirect::away($mfInvoiceData['invoiceURL']);
            }
        } catch (Throwable $e) {
            report($e);

            if (app()->isLocal() && str_contains(strtolower($e->getMessage()), 'ssl certificate')) {
                $booked = app(FollowUpPaymentCompletionService::class)->completeWithWalletOnly(
                    $appointment->fresh(['doctor'])
                );

                if ($booked !== null && $booked->status === 'new') {
                    return Redirect::route('patient.follow-up.payment.success', $appointment)
                        ->with('flash_payment', __('patient_booking.payment_local_ssl_fallback'));
                }
            }
        }

        return Redirect::route('patient.follow-up.payment.failed', $appointment);
    }
}
