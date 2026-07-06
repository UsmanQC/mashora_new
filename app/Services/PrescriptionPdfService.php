<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Medication;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class PrescriptionPdfService
{
    public function canGenerate(Appointment $appointment): bool
    {
        return $appointment->medications()->exists();
    }

    public function loadAppointment(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'doctor.degree',
            'doctor.specialities',
            'user:id,birth_date,phone,email',
            'diagnosis',
            'medications' => static fn ($query) => $query->orderBy('id'),
        ]);
    }

    public function downloadFilename(Appointment $appointment): string
    {
        $reference = filled($appointment->appointment_number)
            ? Str::slug((string) $appointment->appointment_number, '-')
            : 'appointment-'.$appointment->id;

        return 'prescription-'.$reference.'.pdf';
    }

    public function downloadResponse(Appointment $appointment): Response
    {
        return $this->makePdf($appointment)->download($this->downloadFilename($appointment));
    }

    public function streamResponse(Appointment $appointment): Response
    {
        return $this->makePdf($appointment)->stream($this->downloadFilename($appointment));
    }

    public function durationLabel(Medication $medication): string
    {
        $duration = trim((string) ($medication->duration ?? ''));

        if ($duration === '') {
            return '—';
        }

        $unit = match ((string) $medication->duration_measurement) {
            'days' => __('doctor.prescription_pdf.unit_days'),
            'week' => __('doctor.prescription_pdf.unit_week'),
            'month' => __('doctor.prescription_pdf.unit_month'),
            default => (string) $medication->duration_measurement,
        };

        return trim($duration.' '.$unit);
    }

    public function imageDataUri(?string $absolutePath): ?string
    {
        if ($absolutePath === null || ! is_file($absolutePath)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }

    private function makePdf(Appointment $appointment): DomPdfWrapper
    {
        if (! $this->canGenerate($appointment)) {
            abort(404);
        }

        $appointment = $this->loadAppointment($appointment);
        $doctor = $appointment->doctor;

        abort_if($doctor === null, 404);

        $logoDataUri = $this->imageDataUri(public_path('images/awan_logo.png'));

        $signatureDataUri = null;

        if (filled($doctor->signature)) {
            $signaturePath = Storage::disk('public')->path((string) $doctor->signature);
            $signatureDataUri = $this->imageDataUri($signaturePath);
        }

        /** @var DomPdfWrapper $pdf */
        $pdf = app('dompdf.wrapper');

        $pdf->loadView('pdf.prescription', [
            'appointment' => $appointment,
            'doctor' => $doctor,
            'diagnosis' => $appointment->diagnosis,
            'medications' => $appointment->medications,
            'patient' => $appointment->user,
            'company' => config('prescription.company'),
            'logoDataUri' => $logoDataUri,
            'signatureDataUri' => $signatureDataUri,
            'issuedAt' => now(config('app.timezone')),
            'durationLabel' => fn (Medication $medication): string => $this->durationLabel($medication),
        ]);
        $pdf->setPaper('a4');

        return $pdf;
    }
}
