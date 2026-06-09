<?php

namespace App\Filament\Resources\TicketCategories;

use App\Filament\Resources\TicketCategories\Pages\CreateTicketCategory;
use App\Filament\Resources\TicketCategories\Pages\EditTicketCategory;
use App\Filament\Resources\TicketCategories\Pages\ListTicketCategories;
use App\Filament\Resources\TicketCategories\Schemas\TicketCategoryForm;
use App\Filament\Resources\TicketCategories\Tables\TicketCategoriesTable;
use App\Models\TicketCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TicketCategoryResource extends Resource
{
    protected static ?string $model = TicketCategory::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Ticket categories';

    protected static ?string $modelLabel = 'Ticket category';

    protected static ?string $pluralModelLabel = 'Ticket categories';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Clinical & billing';

    protected static ?int $navigationSort = 65;

    public static function form(Schema $schema): Schema
    {
        return TicketCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTicketCategories::route('/'),
            'create' => CreateTicketCategory::route('/create'),
            'edit' => EditTicketCategory::route('/{record}/edit'),
        ];
    }
}
