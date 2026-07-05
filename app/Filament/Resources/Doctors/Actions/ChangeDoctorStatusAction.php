<?php

namespace App\Filament\Resources\Doctors\Actions;

use App\Models\Doctor;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ChangeDoctorStatusAction
{
    public static function make(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->modalHeading('Change doctor status')
            ->modalSubmitActionLabel('Update status')
            ->schema(self::schema())
            ->fillForm(fn (Doctor $record): array => [
                'status' => $record->status,
                'rejection_reason' => $record->rejection_reason,
            ])
            ->action(function (Doctor $record, array $data): void {
                $status = (string) $data['status'];

                $record->update([
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected'
                        ? ($data['rejection_reason'] ?? null)
                        : null,
                ]);

                Notification::make()
                    ->title('Doctor status updated')
                    ->body(match ($status) {
                        'approved' => 'The doctor has been approved.',
                        'rejected' => 'The doctor has been rejected.',
                        default => 'The doctor is now pending review.',
                    })
                    ->success()
                    ->send();
            });
    }

    /**
     * @return list<Select|Textarea>
     */
    public static function schema(): array
    {
        return [
            Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->required()
                ->native(false)
                ->live(),
            Textarea::make('rejection_reason')
                ->label('Rejection reason')
                ->helperText('Shared with the doctor by email and shown on their account status page.')
                ->rows(3)
                ->required(fn ($get): bool => $get('status') === 'rejected')
                ->visible(fn ($get): bool => $get('status') === 'rejected')
                ->columnSpanFull(),
        ];
    }
}
