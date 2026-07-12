<?php

namespace App\Filament\Resources\AppointmentRefundRequests\Pages;

use App\Filament\Resources\AppointmentRefundRequests\AppointmentRefundRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListAppointmentRefundRequests extends ListRecords
{
    protected static string $resource = AppointmentRefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
