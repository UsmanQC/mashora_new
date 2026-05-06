<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patient account')
                    ->description('Users table')
                    ->components([
                        TextEntry::make('name')->label('Full name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                        TextEntry::make('gender')->badge(),
                        TextEntry::make('birth_date')->date(),
                        IconEntry::make('profile_completed')->boolean()->label('Profile completed'),
                        IconEntry::make('status')->boolean()->label('Active'),
                        TextEntry::make('appointments_count')->label('Appointments')->badge(),
                    ])
                    ->columns(2),
                Section::make('Timestamps')
                    ->components([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                        TextEntry::make('deleted_at')->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
