<?php

namespace App\Services;

use App\Models\Appointment;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class DiagnosisPdfService
{
    public function canGenerate(Appointment $appointment): bool
    {
        return $appointment->diagnosis()->exists();
    }

    public function loadAppointment(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'doctor.degree',
            'doctor.specialities',
            'user:id,birth_date,phone,email',
            'diagnosis',
        ]);
    }

    public function downloadFilename(Appointment $appointment): string
    {
        $reference = filled($appointment->appointment_number)
            ? Str::slug((string) $appointment->appointment_number, '-')
            : 'appointment-'.$appointment->id;

        return 'diagnosis-report-'.$reference.'.pdf';
    }

    public function downloadResponse(Appointment $appointment): Response
    {
        return $this->makePdf($appointment)->download($this->downloadFilename($appointment));
    }

    public function streamResponse(Appointment $appointment): Response
    {
        return $this->makePdf($appointment)->stream($this->downloadFilename($appointment));
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

    public function maritalStatusLabel(?string $status): string
    {
        return match ($status) {
            'married' => __('patient.diagnoses_page.marital_married'),
            'unmarried' => __('patient.diagnoses_page.marital_single'),
            default => filled($status) ? (string) $status : '—',
        };
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

        $pdf->loadView('pdf.diagnosis', [
            'appointment' => $appointment,
            'doctor' => $doctor,
            'diagnosis' => $appointment->diagnosis,
            'patient' => $appointment->user,
            'company' => config('prescription.company'),
            'logoDataUri' => $logoDataUri,
            'signatureDataUri' => $signatureDataUri,
            'issuedAt' => now(config('app.timezone')),
            'maritalStatusLabel' => $this->maritalStatusLabel($appointment->diagnosis?->marital_status),
        ]);
        $pdf->setPaper('a4');

        return $pdf;
    }
}
