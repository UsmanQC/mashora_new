<?php

namespace App\Filament\Resources\Specialities\Schemas;

use App\Http\Controllers\Admin\SpecialitiesController;
use App\Models\Speciality;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Fields aligned with {@see Speciality} and Mashorapwa-prod
 * {@see SpecialitiesController} for titles + status.
 *
 * This database only stores title, title_ar, status (see migration); prod also used
 * descriptions and images — add columns + fillable before exposing those here.
 */
class SpecialityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Speciality'))
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->label(__('Title (English)'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('title_ar')
                            ->label(__('Title (Arabic)'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('status')
                            ->label(__('Active'))
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
