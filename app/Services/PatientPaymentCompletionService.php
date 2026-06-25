<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TemporaryAppointment;
use App\Support\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;
use Throwable;

/**
 * Confirms MyFatoorah payment and creates a booked {@link Appointment} from a paid {@link TemporaryAppointment}.
 */
final class PatientPaymentCompletionService
{
    public function forceCompleteForTesting(TemporaryAppointment $temporaryAppointment): ?Appointment
    {
        $temporaryAppointment->loadMissing('doctor');

        if ($temporaryAppointment->appointment_id !== null) {
            return Appointment::query()->find($temporaryAppointment->appointment_id);
        }

        if ($temporaryAppointment->doctor_id === null) {
            return null;
        }

        try {
            return DB::transaction(function () use ($temporaryAppointment): Appointment {
                $temporaryAppointment->refresh();

                if ($temporaryAppointment->appointment_id !== null) {
                    $existing = Appointment::query()->find($temporaryAppointment->appointment_id);
                    if ($existing !== null) {
                        return $existing;
                    }
                }

                $appointment = self::createAppointmentRecord($temporaryAppointment);
                self::syncCommunications($appointment, $temporaryAppointment);
                self::finalizeWallet($appointment, $temporaryAppointment);

                $temporaryAppointment->appointment_id = $appointment->id;
                $temporaryAppointment->payment_status = 'paid';
                $temporaryAppointment->payment_response = json_encode([
                    'provider' => PaymentGateway::driver(),
                    'mode' => 'local_test_fallback',
                    'reason' => 'ssl_certificate_issue',
                    'paid_at' => now()->toIso8601String(),
                ]);
                $temporaryAppointment->save();

                return $appointment;
            });
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Verifies payment with MyFatoorah and completes booking. Idempotent if already paid.
     *
     * @return array{appointment: ?Appointment, state: 'paid'|'needs_config'|'pending'|'failed'}
     */
    public function confirmIfPaid(TemporaryAppointment $temporaryAppointment, Request $request): array
    {
        $temporaryAppointment->loadMissing('doctor');

        if ($temporaryAppointment->appointment_id !== null) {
            $existing = Appointment::query()->find($temporaryAppointment->appointment_id);
            if ($existing !== null) {
                return [
                    'appointment' => $existing,
                    'state' => 'paid',
                ];
            }
        }

        if ($temporaryAppointment->doctor_id === null) {
            return ['appointment' => null, 'state' => 'failed'];
        }

        if (! PaymentGateway::isConfigured()) {
            return ['appointment' => null, 'state' => 'needs_config'];
        }

        if (PaymentGateway::isStripe()) {
            return $this->confirmStripeIfPaid($temporaryAppointment, $request);
        }

        if (PaymentGateway::isHyperPay()) {
            return $this->confirmHyperpayIfPaid($temporaryAppointment, $request);
        }

        try {
            $mf = new MyFatoorahPaymentStatus(self::mfConfig());
            $keyData = self::resolveStatusKey($temporaryAppointment, $request);

            $data = $mf->getPaymentStatus(
                $keyData['key'],
                $keyData['type'],
                (string) $temporaryAppointment->id,
                self::amountDue($temporaryAppointment),
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
            $appointment = DB::transaction(function () use ($temporaryAppointment, $data): Appointment {
                $temporaryAppointment->refresh();

                if ($temporaryAppointment->appointment_id !== null) {
                    $found = Appointment::query()->find($temporaryAppointment->appointment_id);
                    if ($found !== null) {
                        return $found;
                    }
                }

                $appointment = self::createAppointmentRecord($temporaryAppointment);
                self::syncCommunications($appointment, $temporaryAppointment);
                self::finalizeWallet($appointment, $temporaryAppointment);

                $temporaryAppointment->appointment_id = $appointment->id;
                $temporaryAppointment->payment_status = 'paid';
                $temporaryAppointment->payment_response = json_encode($data);
                $temporaryAppointment->save();

                return $appointment;
            });
        } catch (Throwable $e) {
            report($e);

            return ['appointment' => null, 'state' => 'failed'];
        }

        return ['appointment' => $appointment, 'state' => 'paid'];
    }

    /**
     * @return array{appointment: ?Appointment, state: 'paid'|'needs_config'|'pending'|'failed'}
     */
    private function confirmHyperpayIfPaid(TemporaryAppointment $temporaryAppointment, Request $request): array
    {
        $checkoutId = $request->string('checkoutId')->toString();

        if ($checkoutId === '') {
            $checkoutId = (string) ($temporaryAppointment->payment_session_id ?? '');
        }

        if ($checkoutId === '') {
            return ['appointment' => null, 'state' => 'failed'];
        }

        $entityId = $request->string('entityId')->toString();

        if ($entityId === '') {
            $entityId = (string) config('hyperpay.entity_id_b2c');
        }

        /** @var HyperpayCheckoutService $hyperpay */
        $hyperpay = App::make(HyperpayCheckoutService::class);

        try {
            $responseData = $hyperpay->fetchPaymentResult($checkoutId, $entityId);
        } catch (Throwable $e) {
            report($e);

            return ['appointment' => null, 'state' => 'failed'];
        }

        if (! $hyperpay->responseBelongsToBooking($responseData, $temporaryAppointment)) {
            return ['appointment' => null, 'state' => 'failed'];
        }

        $status = $hyperpay->getPaymentStatus((string) data_get($responseData, 'result.code'));

        if (in_array($status, ['processing', 'pending'], true)) {
            return ['appointment' => null, 'state' => 'pending'];
        }

        if ($status !== 'success') {
            return ['appointment' => null, 'state' => 'failed'];
        }

        try {
            $paymentReferenceId = $hyperpay->paymentReferenceId($responseData);

            $appointment = DB::transaction(function () use ($temporaryAppointment, $responseData, $paymentReferenceId): Appointment {
                $temporaryAppointment->refresh();

                if ($temporaryAppointment->appointment_id !== null) {
                    $found = Appointment::query()->find($temporaryAppointment->appointment_id);
                    if ($found !== null) {
                        return $found;
                    }
                }

                $appointment = self::createAppointmentRecord($temporaryAppointment);
                self::syncCommunications($appointment, $temporaryAppointment);
                self::finalizeWallet($appointment, $temporaryAppointment);

                $temporaryAppointment->appointment_id = $appointment->id;
                $temporaryAppointment->payment_status = 'paid';
                $temporaryAppointment->payment_session_id = (string) data_get($responseData, 'ndc', $temporaryAppointment->payment_session_id);
                $temporaryAppointment->payment_invoice_id = $paymentReferenceId !== ''
                    ? $paymentReferenceId
                    : $temporaryAppointment->payment_invoice_id;
                $temporaryAppointment->payment_response = json_encode([
                    'provider' => PaymentGateway::DRIVER_HYPERPAY,
                    'checkout_id' => data_get($responseData, 'ndc'),
                    'payment_id' => data_get($responseData, 'id'),
                    'merchant_transaction_id' => data_get($responseData, 'merchantTransactionId'),
                    'result_code' => data_get($responseData, 'result.code'),
                    'result_description' => data_get($responseData, 'result.description'),
                    'payment_brand' => data_get($responseData, 'paymentBrand'),
                    'amount' => data_get($responseData, 'amount'),
                    'currency' => data_get($responseData, 'currency'),
                ]);
                $temporaryAppointment->save();

                if ($paymentReferenceId !== '') {
                    $appointment->forceFill(['payment_invoice_id' => $paymentReferenceId])->save();
                }

                return $appointment;
            });
        } catch (Throwable $e) {
            report($e);

            return ['appointment' => null, 'state' => 'failed'];
        }

        return ['appointment' => $appointment, 'state' => 'paid'];
    }

    /**
     * @return array{appointment: ?Appointment, state: 'paid'|'needs_config'|'pending'|'failed'}
     */
    private function confirmStripeIfPaid(TemporaryAppointment $temporaryAppointment, Request $request): array
    {
        $sessionId = $request->string('session_id')->toString();

        if ($sessionId === '') {
            $sessionId = (string) ($temporaryAppointment->payment_session_id ?? '');
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

        if (! $stripe->sessionBelongsToBooking($session, $temporaryAppointment)) {
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

            $appointment = DB::transaction(function () use ($temporaryAppointment, $session, $paymentReferenceId): Appointment {
                $temporaryAppointment->refresh();

                if ($temporaryAppointment->appointment_id !== null) {
                    $found = Appointment::query()->find($temporaryAppointment->appointment_id);
                    if ($found !== null) {
                        return $found;
                    }
                }

                $appointment = self::createAppointmentRecord($temporaryAppointment);
                self::syncCommunications($appointment, $temporaryAppointment);
                self::finalizeWallet($appointment, $temporaryAppointment);

                $temporaryAppointment->appointment_id = $appointment->id;
                $temporaryAppointment->payment_status = 'paid';
                $temporaryAppointment->payment_session_id = (string) $session->id;
                $temporaryAppointment->payment_invoice_id = $paymentReferenceId;
                $temporaryAppointment->payment_response = json_encode([
                    'provider' => PaymentGateway::DRIVER_STRIPE,
                    'session_id' => $session->id,
                    'payment_status' => $session->payment_status,
                    'amount_total' => $session->amount_total,
                    'currency' => $session->currency,
                ]);
                $temporaryAppointment->save();

                return $appointment;
            });
        } catch (Throwable $e) {
            report($e);

            return ['appointment' => null, 'state' => 'failed'];
        }

        return ['appointment' => $appointment, 'state' => 'paid'];
    }

    /**
     * @return array{key: string|int, type: string}
     */
    private static function resolveStatusKey(TemporaryAppointment $temporaryAppointment, Request $request): array
    {
        if ($request->filled('paymentId')) {
            return ['key' => $request->string('paymentId')->toString(), 'type' => 'PaymentId'];
        }

        if ($temporaryAppointment->payment_invoice_id) {
            return ['key' => $temporaryAppointment->payment_invoice_id, 'type' => 'InvoiceId'];
        }

        return ['key' => $temporaryAppointment->id, 'type' => 'CustomerReference'];
    }

    private static function createAppointmentRecord(TemporaryAppointment $temporaryAppointment): Appointment
    {
        return Appointment::create([
            'appointment_number' => self::generateAppointmentNumber(),
            'doctor_id' => $temporaryAppointment->doctor_id,
            'user_id' => $temporaryAppointment->user_id,
            'scheduled_at' => $temporaryAppointment->scheduled_at,
            'appointment_date' => $temporaryAppointment->appointment_date,
            'start_time' => $temporaryAppointment->start_time,
            'end_time' => $temporaryAppointment->end_time,
            'duration' => $temporaryAppointment->duration,
            'appointment_for' => $temporaryAppointment->appointment_for,
            'patient_name' => $temporaryAppointment->patient_name,
            'patient_email' => $temporaryAppointment->patient_email,
            'patient_phone' => $temporaryAppointment->patient_phone,
            'patient_notes' => $temporaryAppointment->patient_notes,
            'amount' => $temporaryAppointment->amount,
            'discount' => $temporaryAppointment->discount,
            'tax' => $temporaryAppointment->tax,
            'total' => $temporaryAppointment->total,
            'wallet_amount' => (float) $temporaryAppointment->wallet_amount,
            'appointment_type' => $temporaryAppointment->appointment_type ?? 'regular',
            'instant_counseling' => $temporaryAppointment->instant_counseling,
            'status' => 'new',
            'parent_id' => $temporaryAppointment->parent_id,
            'payment_invoice_id' => $temporaryAppointment->payment_invoice_id,
            'payment_invoice_url' => $temporaryAppointment->payment_invoice_url,
        ]);
    }

    private static function syncCommunications(Appointment $appointment, TemporaryAppointment $temporaryAppointment): void
    {
        $channels = is_array($temporaryAppointment->communications)
            ? $temporaryAppointment->communications
            : [];

        $allowed = ['chat', 'voice_call', 'video_call'];

        foreach ($channels as $channel) {
            if (! is_string($channel) || ! in_array($channel, $allowed, true)) {
                continue;
            }

            $exists = DB::table('communications')
                ->where('communication', $channel)
                ->exists();

            if (! $exists) {
                continue;
            }

            DB::table('appointment_communication')->insertOrIgnore([
                'appointment_id' => $appointment->id,
                'communication' => $channel,
            ]);
        }
    }

    /**
     * Complete booking fully covered by the patient's wallet (no card charge).
     */
    public function completeWithWalletOnly(TemporaryAppointment $temporaryAppointment): ?Appointment
    {
        $temporaryAppointment->loadMissing('doctor');

        if ($temporaryAppointment->appointment_id !== null) {
            return Appointment::query()->find($temporaryAppointment->appointment_id);
        }

        if ($temporaryAppointment->doctor_id === null) {
            return null;
        }

        try {
            return DB::transaction(function () use ($temporaryAppointment): Appointment {
                $temporaryAppointment->refresh();

                if ($temporaryAppointment->appointment_id !== null) {
                    $existing = Appointment::query()->find($temporaryAppointment->appointment_id);
                    if ($existing !== null) {
                        return $existing;
                    }
                }

                $appointment = self::createAppointmentRecord($temporaryAppointment);
                self::syncCommunications($appointment, $temporaryAppointment);
                self::finalizeWallet($appointment, $temporaryAppointment);

                $temporaryAppointment->appointment_id = $appointment->id;
                $temporaryAppointment->payment_status = 'paid';
                $temporaryAppointment->payment_response = json_encode([
                    'provider' => 'wallet',
                    'mode' => 'wallet_only',
                    'paid_at' => now()->toIso8601String(),
                ]);
                $temporaryAppointment->save();

                return $appointment;
            });
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function amountDue(TemporaryAppointment $temporaryAppointment): float
    {
        return max(0.0, round((float) $temporaryAppointment->total - (float) $temporaryAppointment->wallet_amount, 2));
    }

    private static function finalizeWallet(Appointment $appointment, TemporaryAppointment $temporaryAppointment): void
    {
        $wallet = App::make(AppointmentWalletService::class);
        $wallet->chargePatientWallet($appointment, (float) $temporaryAppointment->wallet_amount);
        $wallet->creditDoctorEarning($appointment);
    }

    public static function generateAppointmentNumber(): string
    {
        do {
            $number = 'APP'.now()->format('Ymd').strtoupper(Str::random(6));
        } while (Appointment::withTrashed()->where('appointment_number', $number)->exists());

        return $number;
    }

    /**
     * @return array{apiKey: string, isTest: bool, vcCode: string}
     */
    private static function mfConfig(): array
    {
        return [
            'apiKey' => (string) config('myfatoorah.api_key'),
            'isTest' => (bool) config('myfatoorah.is_test'),
            'vcCode' => (string) config('myfatoorah.vc_code'),
        ];
    }
}
