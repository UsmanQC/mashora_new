<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Doctors\DoctorResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Patients\PatientResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as ActionsSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Mirrors the prod patient/doctor “Settings” hubs (menu grids linking to profile,
 * password, notifications, prescriptions, invoices, working hours, etc.) as admin shortcuts.
 */
class SettingsHub extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Settings';

    protected static ?string $navigationLabel = 'Overview';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog & content';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        Section::make(__('Patient app'))
                            ->description(__('Profiles, phone numbers, prescriptions, favorites, and notifications are managed per patient account.'))
                            ->schema([
                                ActionsSchema::make([
                                    Action::make('patients')
                                        ->label(PatientResource::getPluralModelLabel())
                                        ->icon(PatientResource::getNavigationIcon())
                                        ->url(PatientResource::getUrl()),
                                ]),
                            ]),
                        Section::make(__('Doctor app'))
                            ->description(__('Personal profile, bank details, working hours, duration/pricing, and related preferences are stored on each doctor.'))
                            ->schema([
                                ActionsSchema::make([
                                    Action::make('doctors')
                                        ->label(DoctorResource::getPluralModelLabel())
                                        ->icon(DoctorResource::getNavigationIcon())
                                        ->url(DoctorResource::getUrl()),
                                ]),
                            ]),
                        Section::make(__('Operations'))
                            ->description(__('Bookings and platform-wide invoice listing.'))
                            ->schema([
                                ActionsSchema::make([
                                    Action::make('appointments')
                                        ->label(AppointmentResource::getPluralModelLabel())
                                        ->icon(AppointmentResource::getNavigationIcon())
                                        ->url(AppointmentResource::getUrl()),
                                    Action::make('invoices')
                                        ->label(InvoiceResource::getPluralModelLabel())
                                        ->icon(InvoiceResource::getNavigationIcon())
                                        ->url(InvoiceResource::getUrl()),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
