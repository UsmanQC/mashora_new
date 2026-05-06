<?php

namespace App\Filament\Resources\Thoughts;

use App\Filament\Resources\Thoughts\Pages\CreateThought;
use App\Filament\Resources\Thoughts\Pages\EditThought;
use App\Filament\Resources\Thoughts\Pages\ListThoughts;
use App\Filament\Resources\Thoughts\Schemas\ThoughtForm;
use App\Filament\Resources\Thoughts\Tables\ThoughtsTable;
use App\Models\Thought;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ThoughtResource extends Resource
{
    protected static ?string $model = Thought::class;

    protected static ?string $navigationLabel = 'Thoughts';

    protected static ?string $modelLabel = 'Thought';

    protected static ?string $pluralModelLabel = 'Thoughts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog & content';

    protected static ?int $navigationSort = 52;

    public static function form(Schema $schema): Schema
    {
        return ThoughtForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThoughtsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThoughts::route('/'),
            'create' => CreateThought::route('/create'),
            'edit' => EditThought::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
