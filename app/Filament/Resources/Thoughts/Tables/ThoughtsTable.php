<?php

namespace App\Filament\Resources\Thoughts\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ThoughtsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('description_en')
                    ->label(__('English'))
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->description_en)
                    ->searchable(),
                TextColumn::make('description_ar')
                    ->label(__('Arabic'))
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->description_ar)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('auth_en')
                    ->label(__('Author (EN)'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('auth_ar')
                    ->label(__('Author (AR)'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('status')
                    ->label(__('Published'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable(),
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
