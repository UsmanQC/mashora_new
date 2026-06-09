<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Ticket;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Ticket'))
                    ->columns(2)
                    ->components([
                        TextEntry::make('ticket_number')->label(__('Ticket #')),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('category.name')->label(__('Category')),
                        TextEntry::make('creator_display')
                            ->label(__('Creator'))
                            ->state(fn (Ticket $record): string => $record->creatorDisplayName()),
                        TextEntry::make('creator_audience')
                            ->label(__('Audience'))
                            ->state(fn (Ticket $record): string => $record->creatorAudience())
                            ->badge(),
                        TextEntry::make('subject')->columnSpanFull(),
                        TextEntry::make('message')
                            ->label(__('Initial message'))
                            ->columnSpanFull()
                            ->prose(),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                Section::make(__('Replies'))
                    ->components([
                        RepeatableEntry::make('replies')
                            ->label('')
                            ->schema([
                                TextEntry::make('author_display')
                                    ->label(__('Author'))
                                    ->state(fn ($record): string => $record->authorDisplayName()),
                                TextEntry::make('message')->prose(),
                                TextEntry::make('created_at')->dateTime(),
                            ])
                            ->columns(1)
                            ->placeholder(__('No replies yet.')),
                    ]),
            ]);
    }
}
