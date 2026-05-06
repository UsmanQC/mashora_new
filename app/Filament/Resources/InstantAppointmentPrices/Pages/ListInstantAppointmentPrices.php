<?php

namespace App\Filament\Resources\InstantAppointmentPrices\Pages;

use App\Filament\Resources\InstantAppointmentPrices\InstantAppointmentPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstantAppointmentPrices extends ListRecords
{
    protected static string $resource = InstantAppointmentPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
