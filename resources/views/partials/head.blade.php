<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />

<title>{{ config('app.name') }}</title>

<link rel="icon" href="{{ asset('images/favicon-awaan.png') }}" type="image/png">

@include('partials.thmanyah-font')
@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
