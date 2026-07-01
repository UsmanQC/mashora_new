<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\DoctorInvoicePdfService;
use App\Services\DoctorWalletService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            self::downloadPdfAction(),
            self::markPaidAction(),
        ];
    }

    public static function downloadPdfAction(): Action
    {
        return Action::make('downloadPdf')
            ->label('Download PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(fn (Invoice $record) => app(DoctorInvoicePdfService::class)->downloadResponse($record));
    }

    public static function markPaidAction(): Action
    {
        return Action::make('markPaid')
            ->label('Mark as paid')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Mark invoice as paid')
            ->modalDescription('This records the payout to the doctor and settles their wallet for this invoice amount.')
            ->visible(fn (Invoice $record): bool => ! $record->isPaid())
            ->action(function (Invoice $record): void {
                app(DoctorWalletService::class)->settleInvoicePayout($record->fresh());

                Notification::make()
                    ->title('Invoice marked as paid')
                    ->success()
                    ->send();
            });
    }
}
