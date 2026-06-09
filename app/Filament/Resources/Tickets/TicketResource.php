<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\Schemas\TicketInfolist;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $recordTitleAttribute = 'ticket_number';

    protected static ?string $navigationLabel = 'Support tickets';

    protected static ?string $modelLabel = 'Support ticket';

    protected static ?string $pluralModelLabel = 'Support tickets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Clinical & billing';

    protected static ?int $navigationSort = 60;

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'creator', 'replies.author']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['category', 'creator', 'replies.author']);
    }
}
