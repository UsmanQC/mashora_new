<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

/**
 * Fields align with {@see Invoice} / Mashorapwa-prod invoices migration.
 */
class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice')
                    ->components([
                        TextEntry::make('reference')->label('Reference')->placeholder('—'),
                        TextEntry::make('doctor.name')->label('Doctor'),
                        TextEntry::make('doctor.email')->label('Doctor email')->placeholder('—'),
                        TextEntry::make('doctor.phone')->label('Doctor phone')->placeholder('—'),
                        TextEntry::make('appointments_count')->label('Appointments #')->badge(),
                        TextEntry::make('issue_date')->date()->placeholder('—'),
                        TextEntry::make('from_date')->date()->placeholder('—'),
                        TextEntry::make('to_date')->date()->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Amounts')
                    ->components([
                        TextEntry::make('total_amount')
                            ->formatStateUsing(fn ($state): string => Number::format((float) $state, 2).' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;">')
                            ->html(),
                        TextEntry::make('doctor_share')
                            ->formatStateUsing(fn ($state): string => Number::format((float) $state, 2).' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;">')
                            ->html(),
                        TextEntry::make('mashora_share')
                            ->formatStateUsing(fn ($state): string => Number::format((float) $state, 2).' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;">')
                            ->html(),
                        TextEntry::make('payment_status')->badge(),
                        TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('PDF')
                    ->components([
                        TextEntry::make('link_pdf')
                            ->label('PDF link')
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                    ])
                    ->collapsed(),
                Section::make('Timestamps')
                    ->components([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                        TextEntry::make('deleted_at')->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
