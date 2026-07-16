<?php

namespace App\Filament\Resources\AppointmentRefundRequests\Tables;

use App\Filament\Resources\AppointmentRefundRequests\Actions\ProcessAppointmentRefundAction;
use App\Models\AppointmentRefundRequest;
use App\Services\AppointmentRefundRequestNotifier;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppointmentRefundRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('appointment_id')
                    ->label('Appointment')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable(),
                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(),
                TextColumn::make('requested_by')
                    ->label('Requested by')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'doctor' => 'Doctor',
                        default => 'Patient',
                    })
                    ->color(fn (?string $state): string => $state === 'doctor' ? 'info' : 'gray'),
                TextColumn::make('reason_key')
                    ->label('Reason')
                    ->formatStateUsing(function (string $state, AppointmentRefundRequest $record): string {
                        if ($record->wasRequestedByDoctor() && filled($record->reason_note)) {
                            return (string) $record->reason_note;
                        }

                        return str($state)->replace('_', ' ')->title()->toString();
                    })
                    ->wrap()
                    ->searchable(),
                TextColumn::make('requested_amount')
                    ->label('Requested')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('refund_destination')
                    ->label('Destination')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'payment_account' => 'Payment account',
                        default => 'Wallet',
                    })
                    ->color(fn (?string $state): string => $state === 'payment_account' ? 'info' : 'gray'),
                TextColumn::make('processed_amount')
                    ->label('Processed')
                    ->money('SAR')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'processed' => 'success',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Requested at')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_review' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'processed' => 'Processed',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (AppointmentRefundRequest $record): bool => $record->status === 'pending_review')
                    ->action(function (AppointmentRefundRequest $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                            'rejected_at' => null,
                            'admin_note' => null,
                        ]);

                        app(AppointmentRefundRequestNotifier::class)->notifyApproved($record->fresh() ?? $record);

                        Notification::make()
                            ->title('Refund request approved')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->schema([
                        Textarea::make('admin_note')
                            ->label('Rejection note')
                            ->rows(3)
                            ->required(),
                    ])
                    ->visible(fn (AppointmentRefundRequest $record): bool => in_array($record->status, ['pending_review', 'approved'], true))
                    ->action(function (AppointmentRefundRequest $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'rejected_at' => now(),
                            'approved_at' => null,
                            'admin_note' => $data['admin_note'] ?? null,
                        ]);

                        app(AppointmentRefundRequestNotifier::class)->notifyRejected($record->fresh() ?? $record);

                        Notification::make()
                            ->title('Refund request rejected')
                            ->success()
                            ->send();
                    }),
                ProcessAppointmentRefundAction::make(),
            ])
            ->toolbarActions([]);
    }
}
