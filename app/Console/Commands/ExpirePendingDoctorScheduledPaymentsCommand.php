<?php

namespace App\Console\Commands;

use App\Services\DoctorScheduledAppointmentService;
use Illuminate\Console\Command;

class ExpirePendingDoctorScheduledPaymentsCommand extends Command
{
    protected $signature = 'appointments:expire-pending-payments';

    protected $description = 'Cancel doctor-scheduled appointments when the patient payment window has expired';

    public function handle(DoctorScheduledAppointmentService $service): int
    {
        $count = $service->expireDuePayments();

        $this->info("Expired {$count} unpaid doctor-scheduled appointment(s).");

        return self::SUCCESS;
    }
}
