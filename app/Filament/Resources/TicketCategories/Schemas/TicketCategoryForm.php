<?php

namespace App\Filament\Resources\TicketCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Ticket category'))
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label(__('Name (English)'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ar')
                            ->label(__('Name (Arabic)'))
                            ->required()
                            ->maxLength(255),
                        Select::make('audience')
                            ->label('Audience')
                            ->options([
                                'patient' => 'Patient',
                                'doctor' => 'Doctor',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('sort_order')
                            ->label(__('Sort order'))
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
