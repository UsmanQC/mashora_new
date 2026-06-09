<?php

namespace App\Filament\Resources\TicketCategories\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('English'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_ar')
                    ->label(__('Arabic'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('audience')
                    ->label(__('Audience'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('Sort'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->counts('tickets')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
