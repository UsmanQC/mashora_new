<?php

namespace App\Filament\Resources\AiConversations\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Conversation')
                    ->limit(8)
                    ->copyable(),
                TextColumn::make('user.name')
                    ->label('Patient')
                    ->placeholder('Guest'),
                TextColumn::make('total_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_cost_cents')
                    ->label('Est. cost (cents)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
