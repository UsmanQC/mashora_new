<?php

namespace App\Filament\Resources\InstantAppointmentPrices\Pages;

use App\Filament\Resources\InstantAppointmentPrices\InstantAppointmentPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditInstantAppointmentPrice extends EditRecord
{
    protected static string $resource = InstantAppointmentPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
