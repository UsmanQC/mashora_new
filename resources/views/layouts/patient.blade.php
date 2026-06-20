<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light bg-slate-100"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }}" />
        {{-- Matches legacy patient.mashora.co blue chrome --}}
        <meta name="theme-color" content="#1565c0" />
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
        <link rel="manifest" href="/manifest.webmanifest" />
        @include('partials.head')
    </head>
    <body
        class="min-h-svh bg-slate-100 pb-24 antialiased sm:flex sm:min-h-svh sm:pb-0"
    >
        {{-- Mobile header — same blue chrome as legacy sidebar; guest signup via dock → /patient/phone --}}
        <header
            class="sticky top-0 z-40 flex shrink-0 items-center justify-between gap-3 border-b border-blue-900/40 bg-[#1565c0] px-4 py-3 text-white backdrop-blur-sm sm:hidden"
        >
            <div class="min-w-0 flex-1">
                @include('partials.patient-brand-strip', ['density' => 'compact'])
            </div>
            @auth
                @include('partials.patient-user-account-menu', ['density' => 'header'])
            @endauth
        </header>

        {{-- Desktop / tablet — legacy sidebar: solid blue + white icons + navy active item --}}
        <aside
            class="sticky top-0 z-40 hidden h-svh w-[17rem] shrink-0 flex-col border-e border-[#1565c0] bg-[#1565c0] text-white shadow-lg shadow-black/10 sm:flex"
            aria-label="{{ __('patient.sidebar_label') }}"
        >
            <div class="border-b border-white/15 px-4 pb-4 pt-5">
                @include('partials.patient-brand-strip', ['density' => 'sidebar'])
            </div>

            <nav class="flex-1 overflow-y-auto px-2.5 pb-3 pt-2" aria-label="{{ __('patient.nav.label') }}">
                @include('partials.patient-dock-buttons', ['orientation' => 'vertical', 'theme' => 'legacy'])
            </nav>
        </aside>

        <main class="relative min-h-svh min-w-0 flex-1">
            @auth
                <livewire:patient-portal-chrome-bar />
                <livewire:patient-mood-picker-modal />
            @endauth
            {{ $slot }}
        </main>

        {{-- Mobile dock — chromed like legacy sidebar --}}
        <nav
            class="fixed bottom-0 inset-x-0 z-50 flex border-t border-blue-950/40 bg-[#1565c0] px-1 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1 shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.2)] sm:hidden"
            aria-label="{{ __('patient.nav.label') }}"
        >
            @include('partials.patient-dock-buttons', ['theme' => 'legacy'])
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        @stack('scripts')

        @fluxScripts
    </body>
</html>
