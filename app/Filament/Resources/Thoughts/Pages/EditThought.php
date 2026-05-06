<?php

namespace App\Filament\Resources\Thoughts\Pages;

use App\Filament\Resources\Thoughts\ThoughtResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditThought extends EditRecord
{
    protected static string $resource = ThoughtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
