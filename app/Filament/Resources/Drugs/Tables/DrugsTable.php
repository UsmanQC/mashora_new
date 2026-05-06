<?php

namespace App\Filament\Resources\Drugs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DrugsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('scientific_name')
                    ->label(__('Scientific'))
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->scientific_name),
                TextColumn::make('trade_name')
                    ->label(__('Trade'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pharmaceutical_form')
                    ->label(__('Form'))
                    ->toggleable(),
                TextColumn::make('administration_route')
                    ->label(__('Route'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('strengths_count')
                    ->label(__('Dosages'))
                    ->sortable(),
                IconColumn::make('flag')
                    ->label(__('Flag'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', direction: 'desc')
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
