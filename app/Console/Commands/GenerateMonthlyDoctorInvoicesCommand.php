<?php

namespace App\Console\Commands;

use App\Services\DoctorMonthlyInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyDoctorInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-monthly {--date= : Issue date (Y-m-d). Defaults to today.}';

    protected $description = 'Generate doctor invoices (TEMP: previous calendar day — monthly logic commented in DoctorMonthlyInvoiceService)';

    public function handle(DoctorMonthlyInvoiceService $invoiceService): int
    {
        $issueDate = filled($this->option('date'))
            ? Carbon::parse((string) $this->option('date'))
            : now(config('app.timezone'));

        $billDate = $issueDate->copy()->subDay()->toDateString();

        $this->components->warn('TEMP daily mode: billing completed appointments on '.$billDate);

        $result = $invoiceService->generateForIssueDate($issueDate);

        $this->components->info(sprintf(
            'Generated %d invoice(s) for %d doctor(s) (sessions on %s). Skipped %d existing or empty period(s).',
            $result['created'],
            $result['doctors'],
            $billDate,
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
