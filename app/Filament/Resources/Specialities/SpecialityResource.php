<?php

namespace App\Filament\Resources\Specialities;

use App\Filament\Resources\Specialities\Pages\CreateSpeciality;
use App\Filament\Resources\Specialities\Pages\EditSpeciality;
use App\Filament\Resources\Specialities\Pages\ListSpecialities;
use App\Filament\Resources\Specialities\Schemas\SpecialityForm;
use App\Filament\Resources\Specialities\Tables\SpecialitiesTable;
use App\Models\Speciality;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SpecialityResource extends Resource
{
    protected static ?string $model = Speciality::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Specialities';

    protected static ?string $modelLabel = 'Speciality';

    protected static ?string $pluralModelLabel = 'Specialities';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog & content';

    protected static ?int $navigationSort = 54;

    public static function form(Schema $schema): Schema
    {
        return SpecialityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpecialitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecialities::route('/'),
            'create' => CreateSpeciality::route('/create'),
            'edit' => EditSpeciality::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('doctors');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
