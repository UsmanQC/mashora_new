<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('patient.diagnosis_pdf.document_title') }} — {{ $appointment->patient_name }}</title>
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
            font-size: 64px;
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
        .block-card {
            border: 1px solid #e4e4e7;
            border-radius: 10px;
            padding: 12px 14px;
            background: #ffffff;
            margin-bottom: 10px;
        }
        .block-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #059669;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .block-body {
            font-size: 11px;
            color: #27272a;
            line-height: 1.65;
            white-space: pre-wrap;
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
        .signature-box { margin-top: 8px; }
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
                            <div>{{ __('patient.diagnosis_pdf.customer_service') }}: {{ $company['phone'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="header-right">
                    <p class="doc-title">{{ __('patient.diagnosis_pdf.document_title') }}</p>
                    <p class="doc-subtitle">{{ __('patient.diagnosis_pdf.document_subtitle') }}</p>
                </div>
            </div>

            <table class="patient-card" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <div class="field-label">{{ __('patient.diagnosis_pdf.patient_name') }}</div>
                        <div class="field-value">{{ $appointment->patient_name }}</div>
                    </td>
                    <td>
                        <div class="field-label">{{ __('patient.diagnosis_pdf.appointment_number') }}</div>
                        <div class="field-value">{{ $appointment->appointment_number ?: '—' }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="field-label">{{ __('patient.diagnosis_pdf.date_of_birth') }}</div>
                        <div class="field-value">
                            @if ($patient?->birth_date)
                                {{ $patient->birth_date->format('d/m/Y') }}
                            @else
                                <span class="empty">—</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="field-label">{{ __('patient.diagnosis_pdf.report_date') }}</div>
                        <div class="field-value">{{ $issuedAt->format('d/m/Y') }}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="field-label">{{ __('patient.diagnosis_pdf.session_date') }}</div>
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
                    <p class="section-title">{{ __('patient.diagnosis_pdf.clinician_section') }}</p>
                </div>
                <table class="info-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="label">{{ __('patient.diagnosis_pdf.treating_doctor') }}</td>
                        <td class="value">{{ $doctor->displayName() }}</td>
                    </tr>
                    @if ($doctor->degree)
                        <tr>
                            <td class="label">{{ __('patient.diagnosis_pdf.doctor_degree') }}</td>
                            <td class="value">{{ $doctor->degree->title }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">{{ __('patient.diagnosis_pdf.medical_license') }}</td>
                        <td class="value">{{ $doctor->registration_number ?: '—' }}</td>
                    </tr>
                    @if ($maritalStatusLabel !== '—')
                        <tr>
                            <td class="label">{{ __('patient.diagnosis_pdf.marital_status') }}</td>
                            <td class="value">{{ $maritalStatusLabel }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            <div class="section">
                <div class="section-head">
                    <p class="section-title">{{ __('patient.diagnosis_pdf.diagnosis_section') }}</p>
                </div>
                <div class="block-card">
                    <div class="block-label">{{ __('patient.diagnosis_pdf.diagnosis_name') }}</div>
                    <div class="block-body">{{ $diagnosis?->diagnosis_name ?: '—' }}</div>
                </div>
                @if (filled($diagnosis?->medical_history))
                    <div class="block-card">
                        <div class="block-label">{{ __('patient.diagnosis_pdf.medical_history') }}</div>
                        <div class="block-body">{{ $diagnosis->medical_history }}</div>
                    </div>
                @endif
                @if (filled($diagnosis?->treatment_plan))
                    <div class="block-card">
                        <div class="block-label">{{ __('patient.diagnosis_pdf.treatment_plan') }}</div>
                        <div class="block-body">{{ $diagnosis->treatment_plan }}</div>
                    </div>
                @endif
            </div>

            <div class="footer">
                <div class="footer-left">
                    <div class="field-label">{{ __('patient.diagnosis_pdf.doctor_signature') }}</div>
                    <div class="signature-box">
                        @if ($signatureDataUri)
                            <img src="{{ $signatureDataUri }}" alt="Signature" class="signature-img">
                        @else
                            <span class="muted">{{ $doctor->displayName() }}</span>
                        @endif
                    </div>
                    <div class="muted" style="margin-top: 6px; font-size: 9px;">
                        {{ __('patient.diagnosis_pdf.license_line', ['number' => $doctor->registration_number ?: '—']) }}
                    </div>
                </div>
                <div class="footer-right">
                    <span class="stamp">{{ __('patient.diagnosis_pdf.official_stamp') }}</span>
                </div>
            </div>

            <div class="footer-meta">
                {{ __('patient.diagnosis_pdf.footer_notice', ['company' => $company['name'] ?? 'Awaan']) }}
                · {{ $issuedAt->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
