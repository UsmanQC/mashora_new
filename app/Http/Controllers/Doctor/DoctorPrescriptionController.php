<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\PrescriptionPdfService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DoctorPrescriptionController extends Controller
{
    public function download(Appointment $appointment, PrescriptionPdfService $pdfService): SymfonyResponse
    {
        $doctor = Auth::guard('doctor')->user();

        abort_unless($doctor instanceof Doctor, 403);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);
        abort_unless($pdfService->canGenerate($appointment), 404);

        return $pdfService->downloadResponse($appointment);
    }
}
