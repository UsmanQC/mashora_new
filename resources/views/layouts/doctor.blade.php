<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light bg-[#F3F5F9] font-sans antialiased"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }} — {{ __('doctor.portal_name') }}" />
        @include('partials.pwa', ['themeColor' => '#F3F5F9', 'pwaApp' => 'doctor'])
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        @include('partials.head')
    </head>
    @php
        $guestOnboardingRoutes = [
            'doctor.welcome',
            'doctor.login',
            'doctor.verify-phone',
            'doctor.register',
            'doctor.register.basic.info',
            'doctor.register.bank-account',
            'doctor.register.duration',
            'doctor.register.working-hours',
            'doctor.account-status',
        ];

        $doctorLuxuryMobileNav = auth('doctor')->check() && ! request()->routeIs($guestOnboardingRoutes);

        $doctorLuxuryMobileShell = request()->routeIs([
            'doctor.dashboard',
            'doctor.appointments',
            'doctor.menu',
            'doctor.settings.wallet',
            'doctor.settings.invoices',
            'doctor.settings.invoices.show',
            'doctor.appointments.conversation',
            'doctor.settings.working-hours',
            'doctor.settings.duration',
            'doctor.settings.bank-account',
            'doctor.settings.profile',
            'doctor.ratings',
            'doctor.settings.notifications',
            'doctor.settings.support',
            'doctor.settings.privacy-policy',
            'doctor.prescriptions',
            'doctor.appointments.week',
            'doctor.appointments.medical-history',
            'doctor.appointments.diagnosis',
            'doctor.appointments.prescription',
            'doctor.appointments.follow-up',
        ]);

        $doctorLuxuryMobileHome = request()->routeIs('doctor.dashboard');
        $doctorLuxuryMobileSchedule = request()->routeIs('doctor.appointments');
        $doctorLuxuryMobileMore = request()->routeIs('doctor.menu');
        $doctorLuxuryMobileWallet = request()->routeIs('doctor.settings.wallet');
        $doctorLuxuryMobileWorkingHours = request()->routeIs('doctor.settings.working-hours');
        $doctorLuxuryMobileDuration = request()->routeIs('doctor.settings.duration');
        $doctorLuxuryMobileBankAccount = request()->routeIs('doctor.settings.bank-account');
        $doctorLuxuryMobileProfile = request()->routeIs('doctor.settings.profile');
        $doctorLuxuryMobileRatings = request()->routeIs('doctor.ratings');
        $doctorLuxuryMobileNotifications = request()->routeIs('doctor.settings.notifications');
        $doctorLuxuryMobileSupport = request()->routeIs('doctor.settings.support');
        $doctorLuxuryMobilePrivacyPolicy = request()->routeIs('doctor.settings.privacy-policy');
        $doctorLuxuryMobileInvoices = request()->routeIs('doctor.settings.invoices');
        $doctorLuxuryMobilePrescriptions = request()->routeIs('doctor.prescriptions');
        $doctorLuxuryMobileAppointmentsWeek = request()->routeIs('doctor.appointments.week');

        $doctorLuxuryFullBleed = $doctorLuxuryMobileHome
            || $doctorLuxuryMobileSchedule
            || $doctorLuxuryMobileMore
            || $doctorLuxuryMobileWallet
            || $doctorLuxuryMobileWorkingHours
            || $doctorLuxuryMobileDuration
            || $doctorLuxuryMobileBankAccount
            || $doctorLuxuryMobileProfile
            || $doctorLuxuryMobileRatings
            || $doctorLuxuryMobileNotifications
            || $doctorLuxuryMobileSupport
            || $doctorLuxuryMobilePrivacyPolicy
            || $doctorLuxuryMobileInvoices
            || $doctorLuxuryMobilePrescriptions
            || $doctorLuxuryMobileAppointmentsWeek;
    @endphp
    <body
        @class([
            'min-h-svh antialiased',
            'bg-slate-50' => $doctorLuxuryMobileNav,
            'doctor-luxury-mobile-active' => $doctorLuxuryMobileNav,
        ])
    >
        <aside
            class="portal-chrome-sidebar fixed inset-y-0 start-0 z-50 hidden w-64 min-h-0 flex-col overflow-hidden bg-[#10B981] text-white shadow-lg lg:flex"
            aria-label="{{ __('doctor.sidebar_label') }}"
        >
            <div class="shrink-0 border-b border-white/10 px-4 pb-5 pt-5">
                @include('partials.doctor-brand-strip', ['density' => 'sidebar'])
            </div>
            <nav
                class="portal-sidebar-scroll min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain px-2 py-4"
                aria-label="{{ __('doctor.sidebar_label') }}"
            >
                @include('partials.doctor-sidebar-nav')
            </nav>
        </aside>

        <div
            @class([
                'flex min-w-0 flex-col lg:ps-64',
                'h-svh max-h-svh min-h-0 overflow-hidden' => $doctorLuxuryFullBleed,
                'min-h-svh' => ! $doctorLuxuryFullBleed,
            ])
            x-data="{ doctorMenuOpen: false }"
            @keydown.escape.window="doctorMenuOpen = false"
        >
            @php
                $doctorMenuRouteActive = ! request()->routeIs(
                    'doctor.dashboard',
                    'doctor.appointments',
                    'doctor.appointments.*',
                    'doctor.menu',
                    'doctor.ratings',
                );

                $doctorOnboardingChrome = request()->routeIs($guestOnboardingRoutes);
            @endphp

            <header
                @class([
                    'sticky top-0 z-40 flex items-center justify-between gap-3 border-b border-zinc-200/80 bg-white/95 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur lg:hidden',
                    'hidden' => $doctorLuxuryMobileShell,
                ])
            >
                <div class="min-w-0 flex-1">
                    <a
                        href="{{ $doctorOnboardingChrome ? route('doctor.welcome') : route('doctor.dashboard') }}"
                        wire:navigate
                        class="inline-flex min-w-0 items-center"
                        title="{{ __('patient.brand') }}"
                    >
                        @include('partials.patient-brand-logo', [
                            'imgClass' => 'h-8 w-auto max-w-[min(100%,10.5rem)] object-contain object-start sm:h-9 sm:max-w-[min(100%,11rem)]',
                        ])
                    </a>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if ($doctorOnboardingChrome)
                        @include('partials.doctor-language-switch', ['variant' => 'chrome'])
                    @else
                        <div class="hidden sm:block">
                            @include('partials.doctor-language-switch', ['variant' => 'chrome'])
                        </div>
                        @include('partials.doctor-user-account-menu', ['density' => 'chrome'])
                    @endif
                </div>
            </header>

            <header
                class="sticky top-0 z-30 hidden border-b border-zinc-200/80 bg-white/95 px-6 py-4 backdrop-blur lg:block"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">
                            {{ __('doctor.header.welcome', ['name' => auth('doctor')->user()?->displayName() ?? '']) }}
                        </p>
                        <time class="text-sm text-zinc-500" datetime="{{ now()->toIso8601String() }}">
                            {{ now()->locale(app()->getLocale())->isoFormat('LL') }}
                        </time>
                    </div>
                    <div class="flex items-center gap-2">
                        @include('partials.doctor-language-switch', ['variant' => 'chrome'])
                        <livewire:doctor.components.notifications />
                        @include('partials.doctor-user-account-menu', ['density' => 'chrome'])
                    </div>
                </div>
            </header>

            <main @class([
                'mx-auto w-full max-w-6xl flex-1 min-h-0',
                'max-lg:flex max-lg:flex-col' => $doctorLuxuryFullBleed,
                'px-0 py-0 max-lg:h-svh max-lg:max-h-svh max-lg:overflow-hidden lg:px-6 lg:py-6 lg:pb-8' => $doctorLuxuryFullBleed,
                'px-0 py-0 lg:px-6 lg:py-6 lg:pb-8' => $doctorLuxuryMobileShell && ! $doctorLuxuryFullBleed,
                'px-4 py-6 pb-28 lg:px-6 lg:pb-8' => ! $doctorLuxuryMobileShell && ! $doctorLuxuryMobileNav,
                'px-4 py-6 pb-28 lg:px-6 lg:pb-8' => ! $doctorLuxuryMobileShell && $doctorLuxuryMobileNav,
            ])>
                {{ $slot }}
            </main>

            @include('partials.doctor-luxury-dock')

            @include('partials.doctor-mobile-menu-drawer')
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        @stack('scripts')
        @include('partials.pwa-install-prompt', ['pwaApp' => 'doctor'])
        @fluxScripts
    </body>
</html>
