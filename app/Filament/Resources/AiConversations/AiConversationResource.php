<?php

namespace App\Filament\Resources\AiConversations;

use App\Filament\Resources\AiConversations\Pages\ListAiConversations;
use App\Filament\Resources\AiConversations\Pages\ViewAiConversation;
use App\Filament\Resources\AiConversations\Schemas\AiConversationForm;
use App\Filament\Resources\AiConversations\Schemas\AiConversationInfolist;
use App\Filament\Resources\AiConversations\Tables\AiConversationsTable;
use App\Models\AiConversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiConversationResource extends Resource
{
    protected static ?string $model = AiConversation::class;

    protected static ?string $navigationLabel = 'Conversation history';

    protected static ?string $modelLabel = 'AI conversation';

    protected static ?string $pluralModelLabel = 'AI conversations';

    protected static string|UnitEnum|null $navigationGroup = 'AI Manager';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AiConversationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AiConversationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiConversationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiConversations::route('/'),
            'view' => ViewAiConversation::route('/{record}'),
        ];
    }
}
