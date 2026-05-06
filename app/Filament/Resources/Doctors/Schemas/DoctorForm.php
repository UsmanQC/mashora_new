<?php

namespace App\Filament\Resources\Doctors\Schemas;

use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Models\Doctor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Mirrors Mashorapwa-prod admin doctor create/edit:
 * {@see StoreDoctorRequest}
 * {@see resources/views/admin/doctors/form.blade.php}
 */
class DoctorForm
{
    public static function configure(Schema $schema): Schema
    {
        $experienceOptions = collect(range(1, 30))
            ->mapWithKeys(fn (int $y): array => [$y => "{$y} ".($y > 1 ? 'years' : 'year')])
            ->all();

        return $schema
            ->components([
                Section::make('Profile')
                    ->components([
                        FileUpload::make('profile_photo_path')
                            ->label('Photo')
                            ->image()
                            ->disk('public')
                            ->directory('doctors')
                            ->maxSize(10240)
                            ->imageEditor()
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ar')
                            ->label('Name (Arabic)')
                            ->required()
                            ->maxLength(255),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: Doctor::class, column: 'email', ignoreRecord: true),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(15)
                            ->unique(table: Doctor::class, column: 'phone', ignoreRecord: true),
                        Select::make('experience')
                            ->options($experienceOptions)
                            ->placeholder('Choose')
                            ->required()
                            ->native(false),
                        Select::make('degree_id')
                            ->label('Category / Degree')
                            ->relationship(
                                name: 'degree',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn ($query) => $query->orderBy('title'),
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('spoken_languages')
                            ->label('Language in sessions')
                            ->options([
                                'ar' => 'Arabic',
                                'en' => 'English',
                                'ar_en' => 'Arabic and English',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('registration_number')
                            ->label('License number')
                            ->required()
                            ->maxLength(255),
                        Select::make('specialities')
                            ->relationship('specialities', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->label('Specialities')
                            ->columnSpanFull(),
                        Textarea::make('about_ar')
                            ->label('Profile (Arabic)')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('about')
                            ->label('Profile (English)')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Account')
                    ->components([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->rules(['required', 'min:6', 'confirmed'])
                            ->maxLength(255),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                            ->dehydrated(false)
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord),
                Section::make('Status')
                    ->components([
                        Select::make('status')
                            ->options([
                                'approved' => 'Approved',
                                'pending' => 'Pending',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->native(false),
                        Toggle::make('is_online')
                            ->label('Is working / online')
                            ->inline(false),
                        Toggle::make('accept_instant_appointment')
                            ->label('Accept instant appointment')
                            ->inline(false),
                        TextInput::make('commission')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        TextInput::make('medical_career_level')
                            ->label('Medical career level')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Change password')
                    ->components([
                        TextInput::make('new_password')
                            ->password()
                            ->revealable()
                            ->label('New password')
                            ->rules(['nullable', 'min:6', 'confirmed'])
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        TextInput::make('new_password_confirmation')
                            ->password()
                            ->revealable()
                            ->label('Confirm new password')
                            ->dehydrated(false)
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateRecord)),
            ]);
    }
}
