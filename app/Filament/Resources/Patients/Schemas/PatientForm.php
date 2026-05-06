<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patient account')
                    ->description('Stored on the users table.')
                    ->components([
                        TextInput::make('name')
                            ->label('Full name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email', ignoreRecord: true),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ])
                            ->native(false),
                        DatePicker::make('birth_date')
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                        Toggle::make('profile_completed')
                            ->label('Profile completed')
                            ->inline(false),
                        Toggle::make('status')
                            ->label('Active')
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }
}
