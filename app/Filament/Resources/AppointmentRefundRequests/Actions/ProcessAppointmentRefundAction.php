<?php

namespace App\Filament\Resources\AppointmentRefundRequests\Actions;

use App\Models\Admin;
use App\Models\AppointmentRefundRequest;
use App\Services\AppointmentRefundRequestNotifier;
use App\Services\AppointmentWalletService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ProcessAppointmentRefundAction
{
    public static function make(): Action
    {
        return Action::make('process')
            ->label('Process')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (AppointmentRefundRequest $record): bool => $record->status === 'approved')
            ->schema([
                ToggleButtons::make('resolution_type')
                    ->label('Refund type')
                    ->options([
                        'full' => 'Full',
                        'partial' => 'Partial',
                    ])
                    ->default('full')
                    ->inline()
                    ->grouped()
                    ->live()
                    ->required(),
                TextInput::make('processed_amount')
                    ->label('Amount')
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(fn (AppointmentRefundRequest $record): float => (float) $record->requested_amount)
                    ->step(0.01)
                    ->required(fn ($get): bool => $get('resolution_type') === 'partial')
                    ->visible(fn ($get): bool => $get('resolution_type') === 'partial'),
                Textarea::make('admin_note')
                    ->label('Admin note')
                    ->rows(3),
            ])
            ->fillForm(fn (AppointmentRefundRequest $record): array => [
                'resolution_type' => 'full',
                'processed_amount' => (float) $record->requested_amount,
            ])
            ->action(function (AppointmentRefundRequest $record, array $data): void {
                $record->loadMissing(['appointment', 'appointment.user', 'appointment.doctor']);
                $appointment = $record->appointment;

                if ($appointment === null || $record->status !== 'approved') {
                    return;
                }

                $resolutionType = (string) ($data['resolution_type'] ?? 'full');
                $requestedAmount = (float) $record->requested_amount;
                $amount = $resolutionType === 'partial'
                    ? round((float) ($data['processed_amount'] ?? 0), 2)
                    : $requestedAmount;

                $amount = min(max($amount, 0.01), $requestedAmount);

                app(AppointmentWalletService::class)->refundAmountToPatient(
                    $appointment,
                    $amount,
                    $resolutionType === 'partial' ? 'appointment_refund_partial' : 'appointment_refund',
                );

                $appointment->forceFill([
                    'status' => 'cancelled',
                    'cancel_status' => 'patient_refunded',
                ])->save();

                /** @var Admin|null $admin */
                $admin = Auth::guard('admin')->user();

                $record->update([
                    'status' => 'processed',
                    'resolution_type' => $resolutionType,
                    'processed_amount' => $amount,
                    'processed_at' => now(),
                    'processed_by_admin_id' => $admin?->id,
                    'admin_note' => $data['admin_note'] ?? null,
                ]);

                app(AppointmentRefundRequestNotifier::class)->notifyProcessed($record->fresh() ?? $record);

                Notification::make()
                    ->title('Refund processed and sent to patient wallet')
                    ->success()
                    ->send();
            });
    }
}
