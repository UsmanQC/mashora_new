<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\PrescriptionPdfService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PatientPrescriptionController extends Controller
{
    public function download(Appointment $appointment, PrescriptionPdfService $pdfService): SymfonyResponse
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless((int) $appointment->user_id === (int) $user->id, 403);
        abort_unless($pdfService->canGenerate($appointment), 404);

        return $pdfService->downloadResponse($appointment);
    }
}
