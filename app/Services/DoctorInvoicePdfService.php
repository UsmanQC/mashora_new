<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class DoctorInvoicePdfService
{
    public function loadInvoice(Invoice $invoice): Invoice
    {
        return $invoice->load([
            'doctor:id,name,name_ar,email,phone',
            'appointments' => static function ($query): void {
                $query
                    ->select([
                        'id',
                        'invoice_id',
                        'appointment_number',
                        'appointment_date',
                        'start_time',
                        'patient_name',
                        'patient_phone',
                        'patient_email',
                        'total',
                        'doctor_share',
                        'mashora_share',
                        'status',
                    ])
                    ->orderBy('appointment_date')
                    ->orderBy('start_time');
            },
        ]);
    }

    public function downloadFilename(Invoice $invoice): string
    {
        $reference = filled($invoice->reference)
            ? Str::slug($invoice->reference, '-')
            : 'invoice-'.$invoice->id;

        return $reference.'.pdf';
    }

    public function downloadResponse(Invoice $invoice): Response
    {
        return $this->makePdf($invoice)->download($this->downloadFilename($invoice));
    }

    public function streamResponse(Invoice $invoice): Response
    {
        return $this->makePdf($invoice)->stream($this->downloadFilename($invoice));
    }

    private function makePdf(Invoice $invoice): DomPdfWrapper
    {
        $invoice = $this->loadInvoice($invoice);

        /** @var DomPdfWrapper $pdf */
        $pdf = app('dompdf.wrapper');

        $pdf->loadView('pdf.doctor-monthly-invoice', [
            'invoice' => $invoice,
            'doctor' => $invoice->doctor,
            'appointments' => $invoice->appointments,
        ]);
        $pdf->setPaper('a4');

        return $pdf;
    }
}
