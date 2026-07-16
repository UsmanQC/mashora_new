<?php

namespace App\Filament\Resources\AppointmentRefundRequests\Actions;

use App\Models\Admin;
use App\Models\AppointmentRefundRequest;
use App\Services\AppointmentRefundProcessingService;
use App\Services\AppointmentRefundRequestNotifier;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
                Placeholder::make('payment_summary')
                    ->label('Payment summary')
                    ->content(function (AppointmentRefundRequest $record, $get): string {
                        $appointment = $record->appointment;

                        if ($appointment === null) {
                            return '—';
                        }

                        $processing = app(AppointmentRefundProcessingService::class);
                        $destination = (string) ($get('refund_destination')
                            ?? AppointmentRefundProcessingService::DESTINATION_WALLET);
                        $paid = $processing->amountPaid($appointment);
                        $max = $processing->maximumRefundableAmount($appointment, $destination);
                        $alreadyRefunded = $processing->amountAlreadyRefunded($appointment);

                        $lines = [
                            'Patient paid: '.number_format($paid, 2).' SAR',
                            'Already refunded: '.number_format($alreadyRefunded, 2).' SAR',
                            'Maximum refundable ('.($destination === AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT ? 'payment account' : 'wallet').'): '.number_format($max, 2).' SAR',
                        ];

                        return implode("\n", $lines);
                    })
                    ->live(),
                ToggleButtons::make('refund_destination')
                    ->label('Refund destination')
                    ->options([
                        AppointmentRefundProcessingService::DESTINATION_WALLET => 'Patient wallet',
                        AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT => 'Payment account',
                    ])
                    ->default(AppointmentRefundProcessingService::DESTINATION_WALLET)
                    ->inline()
                    ->grouped()
                    ->live()
                    ->required(),
                ToggleButtons::make('resolution_type')
                    ->label('Refund amount')
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
                    ->maxValue(function (AppointmentRefundRequest $record, $get): float {
                        $appointment = $record->appointment;

                        if ($appointment === null) {
                            return max(0.01, (float) $record->requested_amount);
                        }

                        $destination = (string) ($get('refund_destination')
                            ?? AppointmentRefundProcessingService::DESTINATION_WALLET);

                        return max(
                            0.01,
                            app(AppointmentRefundProcessingService::class)->maximumRefundableAmount($appointment, $destination),
                        );
                    })
                    ->step(0.01)
                    ->required(fn ($get): bool => $get('resolution_type') === 'partial')
                    ->visible(fn ($get): bool => $get('resolution_type') === 'partial'),
                Textarea::make('admin_note')
                    ->label('Admin note')
                    ->rows(3),
            ])
            ->fillForm(function (AppointmentRefundRequest $record): array {
                $record->loadMissing('appointment');
                $appointment = $record->appointment;
                $destination = $record->refund_destination
                    ?? AppointmentRefundProcessingService::DESTINATION_WALLET;

                $max = $appointment !== null
                    ? app(AppointmentRefundProcessingService::class)->maximumRefundableAmount($appointment, $destination)
                    : (float) $record->requested_amount;

                return [
                    'refund_destination' => $destination,
                    'resolution_type' => 'full',
                    'processed_amount' => min((float) $record->requested_amount, $max),
                ];
            })
            ->action(function (AppointmentRefundRequest $record, array $data): void {
                $record->loadMissing(['appointment', 'appointment.user', 'appointment.doctor']);
                $appointment = $record->appointment;

                if ($appointment === null || $record->status !== 'approved') {
                    return;
                }

                $resolutionType = (string) ($data['resolution_type'] ?? 'full');
                $destination = (string) ($data['refund_destination'] ?? AppointmentRefundProcessingService::DESTINATION_WALLET);
                $processing = app(AppointmentRefundProcessingService::class);

                if (
                    $destination === AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT
                    && ! $processing->canRefundToPaymentAccount($appointment)
                ) {
                    throw ValidationException::withMessages([
                        'refund_destination' => __('patient.missed.refund_account_missing'),
                    ]);
                }

                $amount = $processing->resolveProcessableAmount(
                    $appointment,
                    $record,
                    $resolutionType,
                    $destination,
                    $resolutionType === 'partial' ? (float) ($data['processed_amount'] ?? 0) : null,
                );

                $processing->process(
                    $record,
                    $appointment,
                    $amount,
                    $destination,
                    $resolutionType === 'partial',
                );

                /** @var Admin|null $admin */
                $admin = Auth::guard('admin')->user();

                $record->update([
                    'status' => 'processed',
                    'resolution_type' => $resolutionType,
                    'refund_destination' => $destination,
                    'processed_amount' => $amount,
                    'processed_at' => now(),
                    'processed_by_admin_id' => $admin?->id,
                    'admin_note' => $data['admin_note'] ?? null,
                ]);

                app(AppointmentRefundRequestNotifier::class)->notifyProcessed($record->fresh() ?? $record);

                $title = $destination === AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT
                    ? 'Refund processed and sent to patient payment account'
                    : 'Refund processed and credited to patient wallet';

                Notification::make()
                    ->title($title)
                    ->success()
                    ->send();
            });
    }
}
