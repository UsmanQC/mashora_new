<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\DiagnosisPdfService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PatientDiagnosisController extends Controller
{
    public function preview(Appointment $appointment, DiagnosisPdfService $pdfService): SymfonyResponse
    {
        $this->authorizePatientDiagnosis($appointment, $pdfService);

        return $pdfService->streamResponse($appointment);
    }

    public function download(Appointment $appointment, DiagnosisPdfService $pdfService): SymfonyResponse
    {
        $this->authorizePatientDiagnosis($appointment, $pdfService);

        return $pdfService->downloadResponse($appointment);
    }

    private function authorizePatientDiagnosis(Appointment $appointment, DiagnosisPdfService $pdfService): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless((int) $appointment->user_id === (int) $user->id, 403);
        abort_unless($pdfService->canGenerate($appointment), 404);
    }
}
