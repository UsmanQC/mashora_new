@php
    $themeColor = $themeColor ?? config('pwa.theme_color');
@endphp

<link rel="manifest" href="{{ asset('manifest.webmanifest') }}" />
<meta name="theme-color" content="{{ $themeColor }}" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="default" />
<meta name="apple-mobile-web-app-title" content="{{ config('pwa.short_name') }}" />
<link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}" />
