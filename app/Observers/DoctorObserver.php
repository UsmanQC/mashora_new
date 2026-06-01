<?php

namespace App\Observers;

use App\Mail\DoctorAccountApprovedMail;
use App\Mail\DoctorAccountRejectedMail;
use App\Models\Doctor;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DoctorObserver
{
    /**
     * Notify the doctor by email when a super admin approves or rejects their account.
     */
    public function updated(Doctor $doctor): void
    {
        if (! $doctor->wasChanged('status')) {
            return;
        }

        if ($doctor->status === 'approved') {
            $this->sendStatusMail($doctor, new DoctorAccountApprovedMail($doctor), 'approval');

            return;
        }

        if ($doctor->status === 'rejected') {
            $this->sendStatusMail(
                $doctor,
                new DoctorAccountRejectedMail($doctor, $doctor->rejection_reason),
                'rejection',
            );
        }
    }

    private function sendStatusMail(Doctor $doctor, Mailable $mailable, string $type): void
    {
        if (! filled($doctor->email)) {
            return;
        }

        $locale = $doctor->spoken_languages === 'en' ? 'en' : 'ar';

        try {
            Mail::to($doctor->email)
                ->locale($locale)
                ->send($mailable);

            Log::info("Doctor {$type} email sent.", [
                'doctor_id' => $doctor->id,
                'email' => $doctor->email,
                'locale' => $locale,
            ]);
        } catch (\Throwable $exception) {
            Log::error("Failed to send doctor {$type} email.", [
                'doctor_id' => $doctor->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
