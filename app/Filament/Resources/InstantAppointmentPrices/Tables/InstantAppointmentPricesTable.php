<?php

namespace App\Filament\Resources\InstantAppointmentPrices\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class InstantAppointmentPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('duration')
                    ->label(__('Minutes'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('durationOption.title')
                    ->label(__('Label'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->formatStateUsing(fn ($state): string => Number::format((float) $state, 2).' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;">')
                    ->html()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('duration')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }
}
