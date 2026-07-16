<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentRefundRequest;
use Illuminate\Validation\ValidationException;

final class AppointmentRefundProcessingService
{
    public const DESTINATION_WALLET = 'wallet';

    public const DESTINATION_PAYMENT_ACCOUNT = 'payment_account';

    public function __construct(
        private readonly AppointmentWalletService $wallet,
        private readonly MyFatoorahRefundService $myFatoorahRefund,
    ) {}

    /**
     * Total amount the patient paid for this appointment.
     */
    public function amountPaid(Appointment $appointment): float
    {
        $total = max(0, round((float) $appointment->total, 2));

        if ($total <= 0) {
            return 0.0;
        }

        if ($appointment->hasPaymentAccountRefundSource() || (float) $appointment->doctor_share > 0) {
            return $total;
        }

        $walletUsed = max(0, round((float) $appointment->wallet_amount, 2));

        return $walletUsed > 0 ? min($walletUsed, $total) : 0.0;
    }

    public function gatewayAmountPaid(Appointment $appointment): float
    {
        if (! $appointment->hasPaymentAccountRefundSource()) {
            return 0.0;
        }

        return max(0, round($this->amountPaid($appointment) - (float) $appointment->wallet_amount, 2));
    }

    public function amountAlreadyRefunded(Appointment $appointment, ?string $destination = null): float
    {
        $query = $appointment->refundRequests()->where('status', 'processed');

        if ($destination !== null) {
            $query->where('refund_destination', $this->normalizeDestination($destination));
        }

        return round((float) $query->sum('processed_amount'), 2);
    }

    public function maximumRefundableAmount(Appointment $appointment, string $destination): float
    {
        $destination = $this->normalizeDestination($destination);
        $paidOverall = $this->amountPaid($appointment);
        $remainingOverall = max(0, round($paidOverall - $this->amountAlreadyRefunded($appointment), 2));

        if ($remainingOverall <= 0) {
            return 0.0;
        }

        if ($destination === self::DESTINATION_PAYMENT_ACCOUNT) {
            $gatewayPaid = $this->gatewayAmountPaid($appointment);
            $gatewayRemaining = max(0, round($gatewayPaid - $this->amountAlreadyRefunded($appointment, $destination), 2));

            return min($remainingOverall, $gatewayRemaining);
        }

        return $remainingOverall;
    }

    /**
     * @throws ValidationException
     */
    public function resolveProcessableAmount(
        Appointment $appointment,
        AppointmentRefundRequest $request,
        string $resolutionType,
        string $destination,
        ?float $partialAmount = null,
    ): float {
        $destination = $this->normalizeDestination($destination);
        $maximum = $this->maximumRefundableAmount($appointment, $destination);

        if ($maximum < 0.01) {
            throw ValidationException::withMessages([
                'processed_amount' => __('patient.missed.refund_exceeds_paid', ['amount' => number_format(0, 2)]),
            ]);
        }

        $requestedCap = min((float) $request->requested_amount, $maximum);
        $amount = $resolutionType === 'partial'
            ? round((float) $partialAmount, 2)
            : $requestedCap;

        if ($amount < 0.01 || $amount > $maximum) {
            throw ValidationException::withMessages([
                'processed_amount' => __('patient.missed.refund_exceeds_paid', [
                    'amount' => number_format($maximum, 2),
                ]),
            ]);
        }

        return $amount;
    }

    /**
     * @return array{destination: string, refund_invoice_id: string|null}
     */
    public function process(
        AppointmentRefundRequest $request,
        Appointment $appointment,
        float $amount,
        string $destination,
        bool $isPartial = false,
    ): array {
        $destination = $this->normalizeDestination($destination);
        $maximum = $this->maximumRefundableAmount($appointment, $destination);

        if ($amount < 0.01 || $amount > $maximum) {
            throw ValidationException::withMessages([
                'processed_amount' => __('patient.missed.refund_exceeds_paid', [
                    'amount' => number_format($maximum, 2),
                ]),
            ]);
        }

        if ($destination === self::DESTINATION_PAYMENT_ACCOUNT) {
            $refundResult = $this->myFatoorahRefund->refundAppointment(
                $appointment,
                $amount,
                'Admin processed appointment refund request #'.$request->id,
            );

            $appointment->forceFill([
                'status' => 'cancelled',
                'cancel_status' => 'patient_refunded',
                'refund_payment_invoice_id' => $refundResult['refund_invoice_id'],
                'refund_payment_response' => json_encode($refundResult['response']),
            ])->save();

            return [
                'destination' => $destination,
                'refund_invoice_id' => $refundResult['refund_invoice_id'],
            ];
        }

        $this->wallet->refundAmountToPatient(
            $appointment,
            $amount,
            $isPartial ? 'appointment_refund_partial' : 'appointment_refund',
        );

        $appointment->forceFill([
            'status' => 'cancelled',
            'cancel_status' => 'patient_refunded',
        ])->save();

        return [
            'destination' => $destination,
            'refund_invoice_id' => null,
        ];
    }

    public function canRefundToPaymentAccount(Appointment $appointment): bool
    {
        return $appointment->hasPaymentAccountRefundSource();
    }

    public function normalizeDestination(string $destination): string
    {
        return $destination === self::DESTINATION_PAYMENT_ACCOUNT
            ? self::DESTINATION_PAYMENT_ACCOUNT
            : self::DESTINATION_WALLET;
    }
}
