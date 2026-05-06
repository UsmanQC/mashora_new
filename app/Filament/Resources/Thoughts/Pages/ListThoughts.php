<?php

namespace App\Filament\Resources\Thoughts\Pages;

use App\Filament\Resources\Thoughts\ThoughtResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThoughts extends ListRecords
{
    protected static string $resource = ThoughtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
