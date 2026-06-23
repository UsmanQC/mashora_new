<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Support\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;
use Throwable;

/**
 * Activates a doctor-scheduled follow-up {@link Appointment} after the patient pays.
 */
final class FollowUpPaymentCompletionService
{
    /**
     * @return array{appointment: ?Appointment, state: 'paid'|'needs_config'|'pending'|'failed'}
     */
    public function confirmIfPaid(Appointment $appointment, Request $request): array
    {
        $appointment->loadMissing('doctor');

        if (! $appointment->isPendingFollowUp()) {
            return ['appointment' => $appointment->status === 'new' ? $appointment : null, 'state' => 'paid'];
        }

        if ($appointment->patient_confirmed_at === null) {
            return ['appointment' => null, 'state' => 'failed'];
        }

        if (! PaymentGateway::isConfigured()) {
            return ['appointment' => null, 'state' => 'needs_config'];
        }

        if (PaymentGateway::isStripe()) {
            return $this->confirmStripeIfPaid($appointment, $request);
        }

        try {
            $mf = new MyFatoorahPaymentStatus($this->mfConfig());
            $keyData = $this->resolveStatusKey($appointment, $request);

            $data = $mf->getPaymentStatus(
                $keyData['key'],
                $keyData['type'],
                (string) $appointment->id,
                self::amountDue($appointment),
                'SAR'
            );
        } catch (Throwable $e) {
            report($e);

            return ['appointment' => null, 'state' => 'failed'];
        }

        $status = $data->InvoiceStatus ?? '';

        if (! in_array($status, ['Paid', 'DuplicatePayment'], true)) {
            return ['appointment' => null, 'state' => $status === 'Pending' ? 'pending' : 'failed'];
        }

        try {
            $booked = DB::transaction(function () use ($appointment, $data): Appointment {
                $appointment->refresh();

                if (! $appointment->isPendingFollowUp()) {
                    return $appointment;
                }

                return $this->activate($appointment, (float) $appointment->wallet_amount, $data);
            });
        } catch (Throwable $e) {
            report($e);

            return ['appointment' => null, 'state' => 'failed'];
        }

        return ['appointment' => $booked, 'state' => 'paid'];
    }

    public function completeWithWalletOnly(Appointment $appointment): ?Appointment
    {
        $appointment->loadMissing('doctor');

        if (! $appointment->isPendingFollowUp() || $appointment->patient_confirmed_at === null) {
            return $appointment->status === 'new' ? $appointment : null;
        }

        if (self::amountDue($appointment) > 0) {
            return null;
        }

        try {
            return DB::transaction(function () use ($appointment): Appointment {
                $appointment->refresh();

                if (! $appointment->isPendingFollowUp()) {
                    return $appointment;
                }

                return $this->activate($appointment, (float) $appointment->wallet_amount);
            });
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function amountDue(Appointment $appointment): float
    {
        return max(0.0, round((float) $appointment->total - (float) $appointment->wallet_amount, 2));
    }

    /**
     * @return array{appointment: ?Appointment, state: 'paid'|'needs_config'|'pending'|'failed'}
     */
    private function confirmStripeIfPaid(Appointment $appointment, Request $request): array
    {
        $sessionId = $request->string('session_id')->toString();

        if ($sessionId === '') {
            $sessionId = (string) ($appointment->payment_session_id ?? '');
        }

        if ($sessionId === '' || ! str_starts_with($sessionId, 'cs_')) {
            return ['appointment' => null, 'state' => 'failed'];
        }

        /** @var StripeCheckoutService $stripe */
        $stripe = App::make(StripeCheckoutService::class);
        $session = $stripe->retrieveSession($sessionId);

        if ($session === null) {
            return ['appointment' => null, 'state' => 'failed'];
        }

        if (! $stripe->sessionBelongsToFollowUp($session, $appointment)) {
            return ['appointment' => null, 'state' => 'failed'];
        }

        if ($session->payment_status === 'unpaid') {
            return ['appointment' => null, 'state' => 'pending'];
        }

        if (! $stripe->isSessionPaid($session)) {
            return ['appointment' => null, 'state' => 'failed'];
        }

        try {
            $paymentReferenceId = $stripe->paymentReferenceId($session);

            $booked = DB::transaction(function () use ($appointment, $session, $paymentReferenceId): Appointment {
                $appointment->refresh();

                if (! $appointment->isPendingFollowUp()) {
                    return $appointment;
                }

                $activated = $this->activate($appointment, (float) $appointment->wallet_amount, [
                    'provider' => PaymentGateway::DRIVER_STRIPE,
                    'session_id' => $session->id,
                    'payment_status' => $session->payment_status,
                    'InvoiceId' => $paymentReferenceId,
                ]);

                $activated->forceFill([
                    'payment_session_id' => (string) $session->id,
                ])->save();

                return $activated;
            });
        } catch (Throwable $e) {
            report($e);

            return ['appointment' => null, 'state' => 'failed'];
        }

        return ['appointment' => $booked, 'state' => 'paid'];
    }

    /**
     * @return array{key: string|int, type: string}
     */
    private function resolveStatusKey(Appointment $appointment, Request $request): array
    {
        if ($request->filled('paymentId')) {
            return ['key' => $request->string('paymentId')->toString(), 'type' => 'PaymentId'];
        }

        if ($appointment->payment_invoice_id) {
            return ['key' => $appointment->payment_invoice_id, 'type' => 'InvoiceId'];
        }

        return ['key' => 'FOLLOWUP-'.$appointment->id, 'type' => 'CustomerReference'];
    }

    /**
     * @param  object|array<string, mixed>|null  $paymentResponse
     */
    private function activate(Appointment $appointment, float $walletAmount, object|array|null $paymentResponse = null): Appointment
    {
        $wallet = App::make(AppointmentWalletService::class);

        $appointment->forceFill([
            'status' => 'new',
            'wallet_amount' => 0,
        ])->save();

        if ($walletAmount > 0) {
            $wallet->chargePatientWallet($appointment, $walletAmount);
        }

        if ((float) $appointment->total > 0) {
            $wallet->creditDoctorEarning($appointment);
        }

        if ($paymentResponse !== null) {
            $appointment->forceFill([
                'payment_invoice_id' => data_get($paymentResponse, 'InvoiceId') ?? $appointment->payment_invoice_id,
            ])->save();
        }

        $appointment->loadMissing('doctor');
        if ($appointment->doctor instanceof Doctor) {
            app(PatientAppointmentNotifier::class)->notifyFollowUpBooked($appointment, $appointment->doctor);
        }

        return $appointment->fresh();
    }

    /**
     * @return array{apiKey: string, isTest: bool, vcCode: string}
     */
    private function mfConfig(): array
    {
        return [
            'apiKey' => (string) config('myfatoorah.api_key'),
            'isTest' => (bool) config('myfatoorah.is_test'),
            'vcCode' => (string) config('myfatoorah.vc_code'),
        ];
    }
}
