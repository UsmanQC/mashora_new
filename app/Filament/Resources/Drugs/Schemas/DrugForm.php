<?php

namespace App\Filament\Resources\Drugs\Schemas;

use App\Http\Controllers\Admin\DrugsController;
use App\Models\Drug;
use App\Models\DrugStrength;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Matches Mashorapwa-prod {@see DrugsController}:
 * scientific_name, trade_name, pharmaceutical_form, administration_route (required),
 * optional strength rows via {@see DrugStrength} (dosage).
 *
 * Extra columns on {@see Drug}: strength, strength_unit, flag (migration).
 */
class DrugForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Drug'))
                    ->columns(2)
                    ->components([
                        TextInput::make('scientific_name')
                            ->label(__('Scientific name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('trade_name')
                            ->label(__('Trade name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('pharmaceutical_form')
                            ->label(__('Pharmaceutical form'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('administration_route')
                            ->label(__('Administration route'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('strength')
                            ->label(__('Strength'))
                            ->maxLength(255),
                        TextInput::make('strength_unit')
                            ->label(__('Strength unit'))
                            ->maxLength(255),
                        Toggle::make('flag')
                            ->label(__('Flagged'))
                            ->inline(false),
                    ]),
                Section::make(__('Dosage options'))
                    ->description(__('Optional strength lines (same idea as prod nested strengths).'))
                    ->components([
                        Repeater::make('strengths')
                            ->relationship()
                            ->schema([
                                TextInput::make('dosage')
                                    ->label(__('Dosage'))
                                    ->maxLength(255),
                            ])
                            ->columns(1)
                            ->addActionLabel(__('Add dosage')),
                    ]),
            ]);
    }
}
