<?php

namespace App\Filament\Resources\Drugs;

use App\Filament\Resources\Drugs\Pages\CreateDrug;
use App\Filament\Resources\Drugs\Pages\EditDrug;
use App\Filament\Resources\Drugs\Pages\ListDrugs;
use App\Filament\Resources\Drugs\Schemas\DrugForm;
use App\Filament\Resources\Drugs\Tables\DrugsTable;
use App\Models\Drug;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DrugResource extends Resource
{
    protected static ?string $model = Drug::class;

    protected static ?string $recordTitleAttribute = 'trade_name';

    protected static ?string $navigationLabel = 'Drugs';

    protected static ?string $modelLabel = 'Drug';

    protected static ?string $pluralModelLabel = 'Drugs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog & content';

    protected static ?int $navigationSort = 58;

    public static function form(Schema $schema): Schema
    {
        return DrugForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DrugsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDrugs::route('/'),
            'create' => CreateDrug::route('/create'),
            'edit' => EditDrug::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('strengths');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
