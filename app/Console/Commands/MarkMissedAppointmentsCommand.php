<?php

namespace App\Console\Commands;

use App\Services\AppointmentMissedService;
use Illuminate\Console\Command;

class MarkMissedAppointmentsCommand extends Command
{
    protected $signature = 'appointments:mark-missed';

    protected $description = 'Mark overdue doctor sessions as missed and refund patients';

    public function handle(AppointmentMissedService $missedService): int
    {
        $processed = $missedService->processDueMissedAppointments();

        $this->components->info("Marked {$processed} appointment(s) as missed.");

        return self::SUCCESS;
    }
}
