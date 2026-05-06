<?php

namespace App\Filament\Resources\Doctors\Schemas;

use App\Models\Doctor;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DoctorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->components([
                        ImageEntry::make('profile_photo_path')
                            ->label('Photo')
                            ->disk('public')
                            ->height(120)
                            ->columnSpanFull(),
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('name_ar')->label('Name (Arabic)'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone')->copyable(),
                        TextEntry::make('gender')->badge(),
                        TextEntry::make('spoken_languages')->label('Languages')->badge(),
                        TextEntry::make('profile_detail_path')
                            ->label('Profile detail URL')
                            ->url(fn (?string $state): ?string => filled($state) ? url($state) : null)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),
                Section::make('Professional')
                    ->components([
                        TextEntry::make('degree.title_locale')->label('Degree'),
                        TextEntry::make('specialities_summary')
                            ->label('Specialities')
                            ->state(fn (Doctor $record): string => $record->specialities
                                ->map(fn ($s) => $s->title_locale)
                                ->filter()
                                ->implode(', ') ?: '—')
                            ->columnSpanFull(),
                        TextEntry::make('registration_number'),
                        TextEntry::make('experience')->suffix(' yrs'),
                        TextEntry::make('medical_career_level'),
                        TextEntry::make('about')->columnSpanFull(),
                        TextEntry::make('about_ar')->label('About (Arabic)')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Status & settings')
                    ->components([
                        TextEntry::make('status')->badge(),
                        TextEntry::make('active_status')->label('Online (active)')->badge(),
                        IconEntry::make('is_online')->boolean(),
                        IconEntry::make('profile_completed')->boolean(),
                        IconEntry::make('accept_instant_appointment')->boolean(),
                        TextEntry::make('commission')->suffix('%'),
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
