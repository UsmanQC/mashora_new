<?php

namespace App\Console\Commands;

use App\Services\DoctorMonthlyInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyDoctorInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-monthly {--date= : Issue date (Y-m-d). Defaults to today.}';

    protected $description = 'Generate monthly doctor invoices for completed appointments in the previous calendar month';

    public function handle(DoctorMonthlyInvoiceService $invoiceService): int
    {
        $issueDate = filled($this->option('date'))
            ? Carbon::parse((string) $this->option('date'))
            : now(config('app.timezone'));

        $result = $invoiceService->generateForIssueDate($issueDate);

        $this->components->info(sprintf(
            'Generated %d invoice(s) for %d doctor(s). Skipped %d existing or empty period(s).',
            $result['created'],
            $result['doctors'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
