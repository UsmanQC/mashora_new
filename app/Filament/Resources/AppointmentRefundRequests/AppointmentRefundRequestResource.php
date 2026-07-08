<?php

namespace App\Filament\Resources\AppointmentRefundRequests;

use App\Filament\Resources\AppointmentRefundRequests\Pages\ListAppointmentRefundRequests;
use App\Filament\Resources\AppointmentRefundRequests\Tables\AppointmentRefundRequestsTable;
use App\Models\AppointmentRefundRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AppointmentRefundRequestResource extends Resource
{
    protected static ?string $model = AppointmentRefundRequest::class;

    protected static ?string $navigationLabel = 'Refund Requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Clinical & billing';

    protected static ?int $navigationSort = 35;

    public static function table(Table $table): Table
    {
        return AppointmentRefundRequestsTable::configure($table);
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
            'index' => ListAppointmentRefundRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'patient:id,name,email',
                'doctor:id,name,name_ar',
                'appointment:id,appointment_number,appointment_date,start_time,total',
            ]);
    }
}
