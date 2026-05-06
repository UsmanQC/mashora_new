<?php

namespace App\Filament\Resources\InstantAppointmentPrices;

use App\Filament\Resources\InstantAppointmentPrices\Pages\CreateInstantAppointmentPrice;
use App\Filament\Resources\InstantAppointmentPrices\Pages\EditInstantAppointmentPrice;
use App\Filament\Resources\InstantAppointmentPrices\Pages\ListInstantAppointmentPrices;
use App\Filament\Resources\InstantAppointmentPrices\Schemas\InstantAppointmentPriceForm;
use App\Filament\Resources\InstantAppointmentPrices\Tables\InstantAppointmentPricesTable;
use App\Models\InstantAppointmentPrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class InstantAppointmentPriceResource extends Resource
{
    protected static ?string $model = InstantAppointmentPrice::class;

    protected static ?string $navigationLabel = 'Instant Appointment Prices';

    protected static ?string $modelLabel = 'Instant appointment price';

    protected static ?string $pluralModelLabel = 'Instant appointment prices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog & content';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return InstantAppointmentPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstantAppointmentPricesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstantAppointmentPrices::route('/'),
            'create' => CreateInstantAppointmentPrice::route('/create'),
            'edit' => EditInstantAppointmentPrice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('durationOption');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
