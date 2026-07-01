<?php

namespace App\Filament\Resources\AiConversations\Pages;

use App\Filament\Resources\AiConversations\AiConversationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAiConversation extends ViewRecord
{
    protected static string $resource = AiConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
