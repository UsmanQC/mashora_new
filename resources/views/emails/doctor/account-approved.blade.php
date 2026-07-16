@php
    $appName = config('app.name');
    $name = $doctor->displayName();
    $isRtl = in_array(app()->getLocale(), ['ar'], true);
    $align = $isRtl ? 'right' : 'left';
    $logoUrl = $logoUrl ?? asset('images/awan_logo.png');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#ecfdf5; font-family:'Segoe UI', Tahoma, Arial, Helvetica, sans-serif; color:#1f2937; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    <span style="display:none !important; font-size:1px; color:#ecfdf5; max-height:0; max-width:0; opacity:0; overflow:hidden; mso-hide:all;">
        {{ __('mail.doctor_approved.preview', ['app' => $appName]) }}
    </span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ecfdf5; width:100%;">
        <tr>
            <td align="center" style="padding:28px 16px 40px;">
                {{-- Brand header --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">
                    <tr>
                        <td align="center" style="padding:0 0 20px;">
                            <a href="{{ config('app.url') }}" target="_blank" style="text-decoration:none;">
                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ $appName }}"
                                    width="148"
                                    style="display:block; width:148px; max-width:60%; height:auto; border:0; outline:none; text-decoration:none;"
                                >
                            </a>
                        </td>
                    </tr>
                </table>

                {{-- Card --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(16,185,129,0.12);">
                    {{-- Accent bar --}}
                    <tr>
                        <td style="height:6px; background:linear-gradient(90deg,#10B981 0%,#34d399 55%,#E3AE54 100%); font-size:0; line-height:0;">&nbsp;</td>
                    </tr>

                    {{-- Hero --}}
                    <tr>
                        <td align="center" style="padding:36px 32px 8px; background:linear-gradient(180deg,#f0fdf4 0%,#ffffff 100%);">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" valign="middle" style="width:72px; height:72px; background-color:#10B981; border-radius:50%; box-shadow:0 8px 20px rgba(16,185,129,0.35);">
                                        <span style="display:inline-block; font-size:34px; line-height:72px; color:#ffffff; font-weight:700;">&#10003;</span>
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:22px 0 0; font-size:26px; line-height:1.3; font-weight:700; color:#064e3b; letter-spacing:-0.02em;">
                                {{ __('mail.doctor_approved.title') }}
                            </h1>
                            <p style="margin:10px 0 0; font-size:14px; line-height:1.5; color:#059669; font-weight:600;">
                                {{ __('mail.doctor_approved.badge') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body copy --}}
                    <tr>
                        <td style="padding:24px 36px 8px; text-align:{{ $align }};">
                            <p style="margin:0 0 14px; font-size:16px; line-height:1.7; color:#0f172a; font-weight:600;">
                                {{ __('mail.doctor_approved.greeting', ['name' => $name]) }}
                            </p>
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.75; color:#334155;">
                                {{ __('mail.doctor_approved.line1', ['app' => $appName]) }}
                            </p>
                            <p style="margin:0 0 8px; font-size:15px; line-height:1.75; color:#334155;">
                                {{ __('mail.doctor_approved.line2') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Next steps --}}
                    <tr>
                        <td style="padding:12px 36px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px; text-align:{{ $align }};">
                                        <p style="margin:0 0 14px; font-size:13px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase; color:#10B981;">
                                            {{ __('mail.doctor_approved.next_steps_title') }}
                                        </p>

                                        @foreach ([
                                            __('mail.doctor_approved.step_hours'),
                                            __('mail.doctor_approved.step_prices'),
                                            __('mail.doctor_approved.step_online'),
                                        ] as $step)
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 10px;">
                                                <tr>
                                                    <td valign="top" width="28" style="width:28px; padding-{{ $isRtl ? 'left' : 'right' }}:10px;">
                                                        <span style="display:inline-block; width:22px; height:22px; line-height:22px; text-align:center; background-color:#d1fae5; color:#059669; border-radius:50%; font-size:12px; font-weight:700;">&#10003;</span>
                                                    </td>
                                                    <td valign="top" style="font-size:14px; line-height:1.55; color:#334155;">
                                                        {{ $step }}
                                                    </td>
                                                </tr>
                                            </table>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td align="center" style="padding:28px 36px 12px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" style="border-radius:12px; background-color:#10B981; box-shadow:0 8px 18px rgba(16,185,129,0.28);">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $loginUrl }}" style="height:48px;v-text-anchor:middle;width:240px;" arcsize="25%" stroke="f" fillcolor="#10B981">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:Segoe UI,Arial,sans-serif;font-size:15px;font-weight:bold;">
                                                {{ __('mail.doctor_approved.cta') }}
                                            </center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{{ $loginUrl }}" target="_blank" style="display:inline-block; padding:15px 36px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px; background-color:#10B981;">
                                            {{ __('mail.doctor_approved.cta') }}
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:14px 0 0; font-size:12px; line-height:1.5; color:#94a3b8;">
                                {{ __('mail.doctor_approved.cta_hint') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Sign-off --}}
                    <tr>
                        <td style="padding:20px 36px 36px; text-align:{{ $align }};">
                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:0 0 20px;">
                            <p style="margin:0 0 8px; font-size:14px; line-height:1.7; color:#334155;">
                                {{ __('mail.doctor_approved.signoff') }}<br>
                                <strong style="color:#064e3b;">{{ __('mail.doctor_approved.team', ['app' => $appName]) }}</strong>
                            </p>
                            <p style="margin:16px 0 0; font-size:13px; line-height:1.7; color:#64748b;">
                                {!! __('mail.doctor_approved.help_html') !!}
                            </p>
                        </td>
                    </tr>
                </table>

                {{-- Footer --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">
                    <tr>
                        <td align="center" style="padding:22px 12px 0;">
                            <img
                                src="{{ $logoUrl }}"
                                alt=""
                                width="72"
                                style="display:block; width:72px; height:auto; opacity:0.85; border:0; margin:0 auto 10px;"
                            >
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#64748b;">
                                &copy; {{ date('Y') }} {{ $appName }}. {{ __('mail.doctor_approved.rights') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
