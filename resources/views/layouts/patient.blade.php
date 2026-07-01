<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light bg-[#F3F5F9]"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }}" />
        {{-- Patient portal emerald chrome --}}
        @include('partials.pwa', ['themeColor' => '#10B981', 'pwaApp' => 'patient'])
        @include('partials.head')
    </head>
    <body
        class="min-h-svh bg-[#F3F5F9] pb-24 antialiased sm:flex sm:min-h-svh sm:pb-0"
    >
        <script>
            (function () {
                window.MashoraRealtimeAlerts = window.MashoraRealtimeAlerts || {};

                window.MashoraRealtimeAlerts.stopIncomingRing = function () {
                    if (window.MashoraRealtimeAlerts.ringIntervalId) {
                        window.clearInterval(window.MashoraRealtimeAlerts.ringIntervalId);
                        window.MashoraRealtimeAlerts.ringIntervalId = null;
                    }
                };

                window.MashoraRealtimeAlerts.playIncomingRing = function () {
                    window.MashoraRealtimeAlerts.stopIncomingRing();
                };

                window.MashoraRealtimeAlerts.stopIncomingRing();

                window.addEventListener('mashora:incoming-call', () => {
                    window.MashoraRealtimeAlerts?.stopIncomingRing();
                });

                window.addEventListener('mashora:call-ended', () => {
                    window.MashoraRealtimeAlerts?.stopIncomingRing();
                });
            })();
        </script>
        {{-- Mobile header — same blue chrome as legacy sidebar; guest signup via dock → /patient/phone --}}
        @php
            $portalBack = \App\Support\PatientPortalBackNavigation::resolve();
        @endphp
        <header
            class="relative sticky top-0 z-40 flex shrink-0 items-center justify-end gap-2 border-b border-emerald-900/40 bg-[#10B981] px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] text-white backdrop-blur-sm sm:hidden"
        >
            <div class="absolute inset-y-0 left-4 flex items-center gap-1" dir="ltr">
                @if ($portalBack !== null)
                    @include('partials.patient-portal-header-back', ['portalBack' => $portalBack])
                @endif

                @include('partials.patient-brand-strip', ['density' => 'compact', 'align' => 'start'])
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @include('partials.patient-language-switch', ['variant' => 'header'])
                @auth
                    @include('partials.patient-user-account-menu', ['density' => 'header'])
                @endauth
            </div>
        </header>

        @if ($portalBack !== null && request()->routeIs('patient.schedule.filter'))
            <div
                id="patient-portal-swipe-hint"
                class="patient-auth-swipe-hint pointer-events-none fixed inset-y-0 start-0 z-50 flex w-14 items-center justify-center sm:hidden"
                aria-hidden="true"
            >
                <span class="flex size-9 items-center justify-center rounded-full bg-white/95 text-[#10B981] shadow-md ring-1 ring-[#10B981]/15">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 rtl:rotate-180" aria-hidden="true">
                        <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
        @endif

        {{-- Desktop / tablet sidebar — same width as doctor portal --}}
        <aside
            class="portal-chrome-sidebar sticky top-0 z-40 hidden h-svh min-h-0 w-64 shrink-0 flex-col overflow-hidden bg-[#10B981] text-white shadow-lg sm:flex"
            aria-label="{{ __('patient.sidebar_label') }}"
        >
            <div class="shrink-0 border-b border-white/10 px-4 pb-5 pt-5">
                @include('partials.patient-brand-strip', ['density' => 'sidebar'])
            </div>

            <nav
                class="portal-sidebar-scroll min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain px-2 py-4"
                aria-label="{{ __('patient.nav.label') }}"
            >
                @include('partials.patient-sidebar-nav')
            </nav>

            <div class="shrink-0 space-y-3 border-t border-white/10 bg-[#10B981] px-4 py-4">
                @include('partials.patient-language-switch', ['variant' => 'header', 'showLabel' => true])
                @auth
                    @include('partials.patient-user-account-menu', ['density' => 'sidebar'])
                @endauth
            </div>
        </aside>

        <main class="relative flex min-h-svh min-w-0 flex-1 flex-col">
            @auth
                <livewire:patient-portal-chrome-bar />
                <livewire:patient-mood-picker-modal />
            @endauth
            <div class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 pb-28 sm:px-6 lg:pb-8">
                {{ $slot }}
            </div>
        </main>

        {{-- Mobile dock — chromed like legacy sidebar --}}
        <nav
            class="fixed bottom-0 inset-x-0 z-50 flex border-t border-emerald-950/40 bg-[#10B981] px-1 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1 shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.2)] sm:hidden"
            aria-label="{{ __('patient.nav.label') }}"
        >
            @include('partials.patient-dock-buttons', ['theme' => 'legacy'])
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        @include('partials.patient-global-incoming-call-listener')
        @include('partials.patient-global-join-call-banner')
        @stack('scripts')

        @include('partials.pwa-install-prompt', ['pwaApp' => 'patient'])

        @fluxScripts
    </body>
</html>
