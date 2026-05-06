<?php

namespace App\Filament\Resources\Drugs\Pages;

use App\Filament\Resources\Drugs\DrugResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDrug extends EditRecord
{
    protected static string $resource = DrugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
