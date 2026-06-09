<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Models\Ticket;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_number')
                    ->label(__('Ticket #'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->sortable(),
                TextColumn::make('creator_display')
                    ->label(__('Creator'))
                    ->state(fn (Ticket $record): string => $record->creatorDisplayName())
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHasMorph('creator', ['App\Models\User', 'App\Models\Doctor'], function ($creatorQuery, string $type) use ($search): void {
                            if ($type === 'App\Models\User') {
                                $creatorQuery->where('name', 'like', "%{$search}%");
                            } else {
                                $creatorQuery->where(function ($doctorQuery) use ($search): void {
                                    $doctorQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('name_ar', 'like', "%{$search}%");
                                });
                            }
                        });
                    }),
                TextColumn::make('creator_audience')
                    ->label(__('Audience'))
                    ->state(fn (Ticket $record): string => $record->creatorAudience())
                    ->badge(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Ticket::STATUS_OPEN => 'warning',
                        Ticket::STATUS_ANSWERED => 'info',
                        Ticket::STATUS_CLOSED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Ticket::STATUS_OPEN => 'Open',
                        Ticket::STATUS_ANSWERED => 'Answered',
                        Ticket::STATUS_CLOSED => 'Closed',
                    ]),
                SelectFilter::make('creator_type')
                    ->label('Audience')
                    ->options([
                        'App\Models\User' => 'Patient',
                        'App\Models\Doctor' => 'Doctor',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
