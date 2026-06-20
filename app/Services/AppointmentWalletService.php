<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Bavix\Wallet\Enums\TransactionType;
use Bavix\Wallet\Models\Transaction;

/**
 * Wallet movements for appointment payments and refunds (bavix/laravel-wallet).
 */
final class AppointmentWalletService
{
    /**
     * Credit the doctor's share when an appointment is paid.
     */
    public function creditDoctorEarning(Appointment $appointment): void
    {
        $appointment->loadMissing('doctor');
        $doctor = $appointment->doctor;

        if (! $doctor instanceof Doctor) {
            return;
        }

        [$doctorShare, $mashoraShare] = $this->computeShares($appointment);

        $appointment->forceFill([
            'doctor_share' => $doctorShare,
            'mashora_share' => $mashoraShare,
        ])->save();

        if ($doctorShare > 0) {
            $doctor->depositFloat($doctorShare, [
                'type' => 'appointment_earning',
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
            ]);
        }
    }

    /**
     * Withdraw from patient wallet toward a booking. Returns amount actually taken.
     */
    public function chargePatientWallet(Appointment $appointment, float $requested): float
    {
        if ($requested <= 0) {
            return 0.0;
        }

        $patient = $appointment->user;

        if (! $patient instanceof User) {
            return 0.0;
        }

        $amount = min($requested, (float) $patient->balanceFloat);

        if ($amount <= 0) {
            return 0.0;
        }

        $patient->withdrawFloat($amount, [
            'type' => 'appointment_payment',
            'appointment_id' => $appointment->id,
            'appointment_number' => $appointment->appointment_number,
        ]);

        $appointment->forceFill(['wallet_amount' => $amount])->save();

        return $amount;
    }

    /**
     * Refund a doctor-cancelled appointment: full session total to the patient wallet,
     * full session total debited from the doctor wallet (including commission share),
     * and platform commission waived on the booking record.
     */
    public function refundToPatient(Appointment $appointment): void
    {
        $appointment->loadMissing(['doctor', 'user']);

        if ($this->hasExistingRefund($appointment) || ! $this->appointmentWasPaid($appointment)) {
            return;
        }

        $refund = (float) $appointment->total;

        if ($refund <= 0) {
            return;
        }

        $patient = $appointment->user;

        if ($patient instanceof User) {
            $patient->depositFloat($refund, [
                'type' => 'appointment_refund',
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
            ]);
        }

        $doctor = $appointment->doctor;

        if ($doctor instanceof Doctor) {
            $doctor->forceWithdrawFloat($refund, [
                'type' => 'appointment_refund_reversal',
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'doctor_share' => (float) $appointment->doctor_share,
                'mashora_share' => (float) $appointment->mashora_share,
            ]);
        }

        $appointment->forceFill([
            'doctor_share' => 0,
            'mashora_share' => 0,
        ])->save();
    }

    private function appointmentWasPaid(Appointment $appointment): bool
    {
        return (float) $appointment->doctor_share > 0
            || filled($appointment->payment_invoice_id)
            || (float) $appointment->wallet_amount > 0;
    }

    private function hasExistingRefund(Appointment $appointment): bool
    {
        $patient = $appointment->user;

        if (! $patient instanceof User) {
            return false;
        }

        return Transaction::query()
            ->where('payable_type', $patient::class)
            ->where('payable_id', $patient->id)
            ->where('type', TransactionType::Deposit)
            ->where('confirmed', true)
            ->get()
            ->contains(function (Transaction $transaction) use ($appointment): bool {
                $meta = is_array($transaction->meta) ? $transaction->meta : [];

                return ($meta['type'] ?? null) === 'appointment_refund'
                    && (int) ($meta['appointment_id'] ?? 0) === (int) $appointment->id;
            });
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function computeShares(Appointment $appointment): array
    {
        $appointment->loadMissing('doctor');

        $total = (float) $appointment->total;
        $commission = max(0.0, min(100.0, (float) ($appointment->doctor?->commission ?? 0)));

        $mashoraShare = round($total * $commission / 100, 2);
        $doctorShare = round($total - $mashoraShare, 2);

        return [$doctorShare, $mashoraShare];
    }
}
