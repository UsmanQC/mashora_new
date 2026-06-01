@php
    $appName = config('app.name');
    $name = $doctor->displayName();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Tahoma, Arial, sans-serif; color:#1f2937;">
    <span style="display:none; font-size:1px; color:#f1f5f9; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ __('mail.doctor_rejected.preview', ['app' => $appName]) }}
    </span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#1565c0,#42a5f5); padding:32px; text-align:center;">
                            <div style="font-size:22px; font-weight:700; color:#ffffff; letter-spacing:0.5px;">{{ $appName }}</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px 36px 8px; text-align:center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center">
                                <tr>
                                    <td style="width:64px; height:64px; background-color:#fef2f2; border-radius:50%; text-align:center; vertical-align:middle; font-size:34px; line-height:64px; color:#ef4444;">
                                        &#10005;
                                    </td>
                                </tr>
                            </table>
                            <h1 style="margin:24px 0 0; font-size:22px; font-weight:700; color:#0f172a;">
                                {{ __('mail.doctor_rejected.title') }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 36px 0;">
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#334155;">
                                {{ __('mail.doctor_rejected.greeting', ['name' => $name]) }}
                            </p>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#334155;">
                                {{ __('mail.doctor_rejected.line1', ['app' => $appName]) }}
                            </p>
                        </td>
                    </tr>

                    @if (filled($reason))
                        <tr>
                            <td style="padding:8px 36px 0;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef2f2; border:1px solid #fee2e2; border-radius:12px;">
                                    <tr>
                                        <td style="padding:16px 18px;">
                                            <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#b91c1c; margin-bottom:6px;">
                                                {{ __('mail.doctor_rejected.reason_label') }}
                                            </div>
                                            <div style="font-size:15px; line-height:1.7; color:#7f1d1d;">
                                                {{ $reason }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:24px 36px 40px;">
                            <p style="margin:0 0 20px; font-size:15px; line-height:1.7; color:#334155;">
                                {{ __('mail.doctor_rejected.line2') }}
                            </p>
                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:0 0 20px;">
                            <p style="margin:0 0 6px; font-size:13px; line-height:1.7; color:#64748b;">
                                {!! __('mail.doctor_rejected.help_html') !!}
                            </p>
                            <p style="margin:16px 0 0; font-size:14px; color:#334155;">
                                {{ __('mail.doctor_rejected.signoff') }}<br>
                                <strong>{{ __('mail.doctor_rejected.team', ['app' => $appName]) }}</strong>
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="max-width:560px; margin:20px auto 0; font-size:12px; color:#94a3b8; text-align:center;">
                    &copy; {{ date('Y') }} {{ $appName }}. {{ __('mail.doctor_rejected.rights') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
