<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Admin;
use App\Models\Ticket;
use App\Services\TicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label(__('Reply'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn (Ticket $record): bool => ! $record->isClosed())
                ->schema([
                    Textarea::make('message')
                        ->label(__('Message'))
                        ->required()
                        ->rows(5)
                        ->maxLength(5000),
                ])
                ->action(function (Ticket $record, array $data): void {
                    $admin = Auth::guard('admin')->user();
                    if (! $admin instanceof Admin) {
                        return;
                    }

                    app(TicketService::class)->replyAsAdmin($admin, $record, $data['message']);

                    Notification::make()
                        ->title(__('Reply sent'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['replies']);
                }),
            Action::make('close')
                ->label(__('Close ticket'))
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Ticket $record): bool => ! $record->isClosed())
                ->action(function (Ticket $record): void {
                    $admin = Auth::guard('admin')->user();
                    if (! $admin instanceof Admin) {
                        return;
                    }

                    app(TicketService::class)->close($admin, $record);

                    Notification::make()
                        ->title(__('Ticket closed'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
