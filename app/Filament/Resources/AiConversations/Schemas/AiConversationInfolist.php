<?php

namespace App\Filament\Resources\AiConversations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conversation')
                    ->columns(2)
                    ->components([
                        TextEntry::make('id'),
                        TextEntry::make('user.name')
                            ->label('Patient')
                            ->placeholder('Guest'),
                        TextEntry::make('total_tokens'),
                        TextEntry::make('estimated_cost_cents')
                            ->label('Est. cost (cents)'),
                        TextEntry::make('ip_address'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
                Section::make('Messages')
                    ->components([
                        RepeatableEntry::make('messages')
                            ->schema([
                                TextEntry::make('role')
                                    ->badge(),
                                TextEntry::make('content')
                                    ->columnSpanFull()
                                    ->markdown(),
                                TextEntry::make('tool_name')
                                    ->label('Tool')
                                    ->placeholder('—'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}
