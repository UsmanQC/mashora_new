<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;

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
     * Refund a cancelled appointment to the patient wallet and reverse doctor earning.
     */
    public function refundToPatient(Appointment $appointment): void
    {
        $appointment->loadMissing(['doctor', 'user']);

        $refund = (float) $appointment->total;
        $patient = $appointment->user;

        if ($patient instanceof User && $refund > 0) {
            $patient->depositFloat($refund, [
                'type' => 'appointment_refund',
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
            ]);
        }

        $doctor = $appointment->doctor;
        $doctorShare = (float) $appointment->doctor_share;

        if ($doctor instanceof Doctor && $doctorShare > 0) {
            $doctor->forceWithdrawFloat($doctorShare, [
                'type' => 'appointment_refund_reversal',
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
            ]);
        }
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
