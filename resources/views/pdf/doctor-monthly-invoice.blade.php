<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->reference ?? 'Invoice #'.$invoice->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #18181b;
            margin: 0;
            padding: 24px;
            line-height: 1.45;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-right { text-align: right; }
        .brand {
            font-size: 22px;
            font-weight: 700;
            color: #10B981;
            margin: 0 0 4px;
        }
        .muted { color: #71717a; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-paid { background: #d1fae5; color: #047857; }
        .badge-unpaid { background: #fef3c7; color: #b45309; }
        .meta-grid {
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
        }
        .meta-grid td {
            padding: 10px 12px;
            border-bottom: 1px solid #f4f4f5;
            vertical-align: top;
        }
        .meta-grid tr:last-child td { border-bottom: none; }
        .meta-label {
            color: #71717a;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.items th {
            background: #f4f4f5;
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #52525b;
        }
        table.items td {
            padding: 8px 10px;
            border-bottom: 1px solid #e4e4e7;
            vertical-align: top;
        }
        table.items tr:nth-child(even) td { background: #fafafa; }
        .totals {
            width: 100%;
            margin-top: 16px;
        }
        .totals td {
            padding: 6px 0;
        }
        .totals .label { color: #52525b; }
        .totals .value { text-align: right; font-weight: 600; }
        .totals .grand td {
            padding-top: 10px;
            border-top: 2px solid #10B981;
            font-size: 13px;
            font-weight: 700;
            color: #047857;
        }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e4e4e7;
            font-size: 9px;
            color: #a1a1aa;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <p class="brand">{{ config('app.name') }}</p>
            <p class="muted">{{ __('doctor.invoices.pdf_title') }}</p>
        </div>
        <div class="header-right">
            <p style="font-size: 16px; font-weight: 700; margin: 0;">
                {{ $invoice->reference ?: __('doctor.invoices.invoice_number', ['id' => $invoice->id]) }}
            </p>
            @if ($invoice->isPaid())
                <span class="badge badge-paid">{{ __('doctor.invoices.status_paid') }}</span>
            @else
                <span class="badge badge-unpaid">{{ __('doctor.invoices.status_unpaid') }}</span>
            @endif
        </div>
    </div>

    <table class="meta-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%">
                <div class="meta-label">{{ __('doctor.invoices.doctor') }}</div>
                <div>{{ $doctor?->displayName() ?? '—' }}</div>
                @if (filled($doctor?->email))
                    <div class="muted">{{ $doctor->email }}</div>
                @endif
                @if (filled($doctor?->phone))
                    <div class="muted">{{ $doctor->phone }}</div>
                @endif
            </td>
            <td width="50%">
                <div class="meta-label">{{ __('doctor.invoices.period') }}</div>
                <div>
                    @if ($invoice->from_date && $invoice->to_date)
                        {{ $invoice->from_date->format('Y-m-d') }} → {{ $invoice->to_date->format('Y-m-d') }}
                    @else
                        —
                    @endif
                </div>
                <div class="meta-label" style="margin-top: 8px;">{{ __('doctor.invoices.issued') }}</div>
                <div>{{ $invoice->issue_date?->format('Y-m-d') ?? '—' }}</div>
                @if ($invoice->isPaid() && $invoice->paid_at)
                    <div class="meta-label" style="margin-top: 8px;">{{ __('doctor.invoices.paid_at') }}</div>
                    <div>{{ $invoice->paid_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <p style="font-weight: 700; margin: 0 0 6px;">{{ __('doctor.invoices.sessions_heading') }}</p>

    <table class="items">
        <thead>
            <tr>
                <th>{{ __('doctor.invoices.col_reference') }}</th>
                <th>{{ __('doctor.invoices.col_date') }}</th>
                <th>{{ __('doctor.invoices.col_patient') }}</th>
                <th>{{ __('doctor.invoices.col_phone') }}</th>
                <th style="text-align: right;">{{ __('doctor.invoices.col_total') }}</th>
                <th style="text-align: right;">{{ __('doctor.invoices.col_doctor_share') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appointments as $appointment)
                <tr>
                    <td>#{{ $appointment->appointment_number ?: $appointment->id }}</td>
                    <td>
                        {{ $appointment->appointment_date?->format('Y-m-d') ?? '—' }}
                        @if (filled($appointment->start_time))
                            <br><span class="muted">{{ \Illuminate\Support\Str::of($appointment->start_time)->substr(0, 5) }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $appointment->patient_name ?: '—' }}
                        @if (filled($appointment->patient_email))
                            <br><span class="muted">{{ $appointment->patient_email }}</span>
                        @endif
                    </td>
                    <td>{{ $appointment->patient_phone ?: '—' }}</td>
                    <td style="text-align: right;">{{ number_format((float) $appointment->total, 2) }} SAR</td>
                    <td style="text-align: right;">{{ number_format((float) $appointment->doctor_share, 2) }} SAR</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted" style="text-align: center;">{{ __('doctor.invoices.no_sessions') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">{{ __('doctor.invoices.total_sessions') }}</td>
            <td class="value">{{ $appointments->count() }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('doctor.invoices.total_amount') }}</td>
            <td class="value">{{ number_format((float) $invoice->total_amount, 2) }} SAR</td>
        </tr>
        <tr>
            <td class="label">{{ __('doctor.invoices.mashora_share') }}</td>
            <td class="value">{{ number_format((float) $invoice->mashora_share, 2) }} SAR</td>
        </tr>
        <tr class="grand">
            <td class="label">{{ __('doctor.invoices.doctor_share') }}</td>
            <td class="value">{{ number_format((float) $invoice->doctor_share, 2) }} SAR</td>
        </tr>
    </table>

    <div class="footer">
        {{ __('doctor.invoices.pdf_footer', ['app' => config('app.name')]) }}
    </div>
</body>
</html>
