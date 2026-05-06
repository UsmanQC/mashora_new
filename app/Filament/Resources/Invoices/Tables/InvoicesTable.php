<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor.phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('appointments_count')
                    ->label('Appointments')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state): string => Number::format((float) $state, 2).' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;">')
                    ->html()
                    ->sortable(),
                TextColumn::make('doctor_share')
                    ->formatStateUsing(fn ($state): string => Number::format((float) $state, 2).' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;">')
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mashora_share')
                    ->formatStateUsing(fn ($state): string => Number::format((float) $state, 2).' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;">')
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issue_date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', direction: 'desc')
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
