<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Services\DoctorInvoicePdfService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DoctorInvoiceController extends Controller
{
    public function download(Invoice $invoice, DoctorInvoicePdfService $pdfService): SymfonyResponse
    {
        $doctor = Auth::guard('doctor')->user();

        abort_unless($doctor instanceof Doctor, 403);
        abort_unless((int) $invoice->doctor_id === (int) $doctor->id, 403);

        return $pdfService->downloadResponse($invoice);
    }
}
