<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Models\Appointment;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('appointment_number')
                    ->label('Type & Appointment ID')
                    ->description(fn (Appointment $record): ?string => $record->appointment_type === 'instant' ? 'Instant' : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->description(fn (Appointment $record): ?string => $record->patient_phone ?: null)
                    ->searchable(true, function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->where('patient_name', 'like', "%{$search}%")
                                ->orWhere('patient_phone', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('status')
                    ->label('Order status')
                    ->formatStateUsing(function (?string $state, Appointment $record): string {
                        $base = Str::ucfirst(str_replace('_', ' ', (string) $state));
                        if ($state === 'cancelled' && filled($record->appointment_cancel_status)) {
                            return trim($base.' '.$record->appointment_cancel_status);
                        }

                        return $base;
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('doctor.name_locale')
                    ->label('Service provider')
                    ->searchable(true, function (Builder $query, string $search): Builder {
                        return $query->whereHas('doctor', function (Builder $q) use ($search): void {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('name_ar', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('appointment_date')
                    ->label('Slot date & time')
                    ->formatStateUsing(function ($state, Appointment $record): string {
                        return $record->appointment_date?->format('d M Y') ?? '—';
                    })
                    ->description(function (Appointment $record): ?string {
                        if (! $record->start_time || ! $record->end_time) {
                            return null;
                        }
                        $start = Carbon::parse($record->start_time)->format('h:i A');
                        $end = Carbon::parse($record->end_time)->format('h:i A');

                        return "{$start} - {$end}";
                    })
                    ->sortable(),
                TextColumn::make('actual_end_at')
                    ->label('Actual completed')
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? Carbon::parse($state)->format('d M Y h:i A')
                        : '')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => $state !== null && $state !== '' ? number_format((float) $state, 2) : '—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', direction: 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'new' => 'New appointment',
                        'in_process' => 'In process',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'not_attended' => 'Not attended',
                        'rescheduled' => 'Rescheduled',
                    ]),
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship(
                        'doctor',
                        'name',
                        fn (Builder $query) => $query->where('status', 'approved'),
                    )
                    ->searchable()
                    ->preload(),
                Filter::make('created_between')
                    ->label('Created appointment date')
                    ->schema([
                        DatePicker::make('create_from')->label('From'),
                        DatePicker::make('create_to')->label('To'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['create_from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['create_to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('appointment_between')
                    ->label('Appointment date')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('To'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('appointment_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('appointment_date', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
