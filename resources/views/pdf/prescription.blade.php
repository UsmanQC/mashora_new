<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('doctor.prescription_pdf.document_title') }} — {{ $appointment->patient_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #18181b;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        .page {
            padding: 22px 26px 28px;
            position: relative;
        }
        .watermark {
            position: fixed;
            top: 42%;
            left: 50%;
            width: 320px;
            margin-left: -160px;
            opacity: 0.04;
            text-align: center;
            font-size: 72px;
            font-weight: 700;
            color: #10B981;
            z-index: 0;
        }
        .content { position: relative; z-index: 1; }
        .top-bar {
            height: 6px;
            background: linear-gradient(90deg, #10B981 0%, #047857 100%);
            margin: -22px -26px 18px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-left { width: 58%; }
        .header-right {
            width: 42%;
            text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
        }
        .logo {
            height: 42px;
            margin-bottom: 8px;
        }
        .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #047857;
            margin: 0 0 2px;
        }
        .brand-sub {
            font-size: 10px;
            color: #71717a;
            margin: 0;
        }
        .org-lines {
            margin-top: 8px;
            font-size: 9px;
            color: #52525b;
            line-height: 1.55;
        }
        .doc-title {
            font-size: 18px;
            font-weight: 700;
            color: #047857;
            margin: 0 0 4px;
            letter-spacing: 0.02em;
        }
        .doc-subtitle {
            font-size: 10px;
            color: #71717a;
            margin: 0;
        }
        .patient-card {
            width: 100%;
            border: 1px solid #d1fae5;
            border-radius: 10px;
            background: #f0fdf4;
            margin-bottom: 16px;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
        }
        .patient-card td {
            padding: 10px 12px;
            vertical-align: top;
            width: 50%;
            border-bottom: 1px solid #ecfdf5;
        }
        .patient-card tr:last-child td { border-bottom: none; }
        .field-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #059669;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .field-value {
            font-size: 11px;
            font-weight: 600;
            color: #18181b;
        }
        .section {
            margin-bottom: 14px;
        }
        .section-head {
            background: linear-gradient(90deg, #ecfdf5 0%, #f0fdf4 100%);
            border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4px solid #10B981;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 6px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #047857;
            margin: 0;
        }
        .section-title-ar {
            font-size: 10px;
            color: #059669;
            margin: 2px 0 0;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 7px 10px;
            border-bottom: 1px solid #f4f4f5;
            vertical-align: top;
        }
        .info-grid tr:last-child td { border-bottom: none; }
        .info-grid .label {
            width: 34%;
            color: #71717a;
            font-size: 10px;
            font-weight: 600;
        }
        .info-grid .value {
            color: #18181b;
            font-size: 11px;
            font-weight: 600;
        }
        table.medications {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            overflow: hidden;
        }
        table.medications th {
            background: #047857;
            color: #ffffff;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            padding: 9px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        table.medications td {
            padding: 9px 10px;
            border-bottom: 1px solid #e4e4e7;
            vertical-align: top;
            font-size: 10px;
        }
        table.medications tr:nth-child(even) td { background: #fafafa; }
        table.medications tr:last-child td { border-bottom: none; }
        .med-index {
            display: inline-block;
            min-width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            border-radius: 999px;
            background: #d1fae5;
            color: #047857;
            font-weight: 700;
            font-size: 10px;
        }
        .med-name { font-weight: 700; color: #18181b; }
        .med-note {
            margin-top: 4px;
            font-size: 9px;
            color: #52525b;
            font-style: italic;
        }
        .footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 2px solid #ecfdf5;
            display: table;
            width: 100%;
        }
        .footer-left, .footer-right {
            display: table-cell;
            vertical-align: bottom;
        }
        .footer-left { width: 55%; }
        .footer-right {
            width: 45%;
            text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
        }
        .signature-box {
            margin-top: 8px;
        }
        .signature-img {
            max-height: 56px;
            max-width: 180px;
        }
        .stamp {
            display: inline-block;
            border: 2px solid #10B981;
            color: #047857;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .footer-meta {
            margin-top: 16px;
            text-align: center;
            font-size: 9px;
            color: #a1a1aa;
            line-height: 1.6;
        }
        .muted { color: #71717a; }
        .empty { color: #a1a1aa; font-style: italic; }
    </style>
</head>
<body>
    <div class="page">
        <div class="watermark">{{ $company['name'] ?? 'Awaan' }}</div>

        <div class="content">
            <div class="top-bar"></div>

            <div class="header">
                <div class="header-left">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Awaan" class="logo">
                    @else
                        <p class="brand-name">{{ $company['name'] ?? 'Awaan' }}</p>
                    @endif
                    <p class="brand-sub">{{ $company['country'] ?? '' }} · {{ $company['country_ar'] ?? '' }}</p>
                    <div class="org-lines">
                        @if (filled($company['website'] ?? null))
                            <div>{{ $company['website'] }}</div>
                        @endif
                        @if (filled($company['email'] ?? null))
                            <div>{{ $company['email'] }}</div>
                        @endif
                        @if (filled($company['phone'] ?? null))
                            <div>{{ __('doctor.prescription_pdf.customer_service') }}: {{ $company['phone'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="header-right">
                    <p class="doc-title">{{ __('doctor.prescription_pdf.document_title') }}</p>
                    <p class="doc-subtitle">{{ __('doctor.prescription_pdf.document_subtitle') }}</p>
                </div>
            </div>

            <table class="patient-card" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <div class="field-label">{{ __('doctor.prescription_pdf.patient_id') }}</div>
                        <div class="field-value">{{ $patient?->id ?? $appointment->user_id ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="field-label">{{ __('doctor.prescription_pdf.patient_name') }}</div>
                        <div class="field-value">{{ $appointment->patient_name }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">{{ __('doctor.prescription_pdf.appointment_number') }}</div>
                        <div class="field-value">{{ $appointment->appointment_number ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="field-label">{{ __('doctor.prescription_pdf.date_of_birth') }}</div>
                        <div class="field-value">
                            @if ($patient?->birth_date)
                                {{ $patient->birth_date->format('d/m/Y') }}
                            @else
                                <span class="empty">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">{{ __('doctor.prescription_pdf.prescription_date') }}</div>
                        <div class="field-value">{{ $issuedAt->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <div class="field-label">{{ __('doctor.prescription_pdf.session_date') }}</div>
                        <div class="field-value">
                            @if ($appointment->appointment_date)
                                {{ $appointment->appointment_date->format('d/m/Y') }}
                                @if (filled($appointment->start_time))
                                    · {{ substr((string) $appointment->start_time, 0, 5) }}
                                @endif
                            @else
                                <span class="empty">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <div class="section">
                <div class="section-head">
                    <p class="section-title">{{ __('doctor.prescription_pdf.diagnosis_section') }}</p>
                </div>
                <table class="info-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="label">{{ __('doctor.prescription_pdf.treating_doctor') }}</td>
                        <td class="value">{{ $doctor->displayName() }}</td>
                    </tr>
                    @if ($doctor->degree)
                        <tr>
                            <td class="label">{{ __('doctor.prescription_pdf.doctor_degree') }}</td>
                            <td class="value">{{ $doctor->degree->title }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">{{ __('doctor.prescription_pdf.diagnosis') }}</td>
                        <td class="value">{{ $diagnosis?->diagnosis_name ?: '—' }}</td>
                    </tr>
                    @if (filled($diagnosis?->doctor_notes))
                        <tr>
                            <td class="label">{{ __('doctor.prescription_pdf.other_notes') }}</td>
                            <td class="value">{{ $diagnosis->doctor_notes }}</td>
                        </tr>
                    @endif
                    @if (filled($diagnosis?->treatment_plan))
                        <tr>
                            <td class="label">{{ __('doctor.prescription_pdf.treatment_plan') }}</td>
                            <td class="value">{{ $diagnosis->treatment_plan }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">{{ __('doctor.prescription_pdf.medical_license') }}</td>
                        <td class="value">{{ $doctor->registration_number ?: '—' }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-head">
                    <p class="section-title">{{ __('doctor.prescription_pdf.medications_section') }}</p>
                </div>
                <table class="medications" cellpadding="0" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 6%;">#</th>
                            <th style="width: 24%;">{{ __('doctor.prescription_pdf.col_medicine') }}</th>
                            <th style="width: 14%;">{{ __('doctor.prescription_pdf.col_dosage') }}</th>
                            <th style="width: 14%;">{{ __('doctor.prescription_pdf.col_frequency') }}</th>
                            <th style="width: 20%;">{{ __('doctor.prescription_pdf.col_usage') }}</th>
                            <th style="width: 14%;">{{ __('doctor.prescription_pdf.col_duration') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($medications as $index => $medication)
                            <tr>
                                <td><span class="med-index">{{ $index + 1 }}</span></td>
                                <td>
                                    <span class="med-name">{{ $medication->name }}</span>
                                    @if (filled($medication->instructions))
                                        <div class="med-note">{{ $medication->instructions }}</div>
                                    @endif
                                </td>
                                <td>{{ $medication->dosage ?: '—' }}</td>
                                <td>{{ $medication->frequency ?: '—' }}</td>
                                <td>{{ $medication->usage ?: '—' }}</td>
                                <td>{{ $durationLabel($medication) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="footer">
                <div class="footer-left">
                    <div class="field-label">{{ __('doctor.prescription_pdf.doctor_signature') }}</div>
                    <div class="signature-box">
                        @if ($signatureDataUri)
                            <img src="{{ $signatureDataUri }}" alt="Signature" class="signature-img">
                        @else
                            <span class="muted">{{ $doctor->displayName() }}</span>
                        @endif
                    </div>
                    <div class="muted" style="margin-top: 6px; font-size: 9px;">
                        {{ __('doctor.prescription_pdf.license_line', ['number' => $doctor->registration_number ?: '—']) }}
                    </div>
                </div>
                <div class="footer-right">
                    <span class="stamp">{{ __('doctor.prescription_pdf.official_stamp') }}</span>
                </div>
            </div>

            <div class="footer-meta">
                {{ __('doctor.prescription_pdf.footer_notice', ['company' => $company['name'] ?? 'Awaan']) }}
                · {{ $issuedAt->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
