@php
    $pwaApp = $pwaApp ?? 'patient';
    $appConfig = config("pwa.apps.{$pwaApp}", config('pwa.apps.patient'));
    $themeColor = $themeColor ?? ($appConfig['theme_color'] ?? '#10B981');
    $manifestRoute = $pwaApp === 'doctor' ? 'manifest.doctor' : 'manifest';
@endphp

<link rel="manifest" href="{{ route($manifestRoute) }}" />
<meta name="description" content="{{ $appConfig['description'] }}" />
<meta name="theme-color" content="{{ $themeColor }}" />
<meta name="color-scheme" content="light only" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="default" />
<meta name="apple-mobile-web-app-title" content="{{ $appConfig['short_name'] }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
