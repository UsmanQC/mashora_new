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
    ]);

    $doctorLuxuryMobileHome = request()->routeIs('doctor.dashboard');
    $doctorLuxuryMobileSchedule = request()->routeIs('doctor.appointments');
    $doctorLuxuryMobileMore = request()->routeIs('doctor.menu');

    $doctorLuxuryFullBleed = $doctorLuxuryMobileHome
        || $doctorLuxuryMobileSchedule
        || $doctorLuxuryMobileMore;

    $activeHome = request()->routeIs('doctor.dashboard');
    $activeSchedule = request()->routeIs('doctor.appointments', 'doctor.appointments.*');
    $activeWallet = request()->routeIs('doctor.settings.wallet', 'doctor.settings.invoices', 'doctor.settings.invoices.show');
    $activeMore = request()->routeIs('doctor.menu');

    $hrefHome = route('doctor.dashboard');
    $hrefSchedule = route('doctor.appointments');
    $hrefWallet = route('doctor.settings.wallet');
    $hrefMore = route('doctor.menu');

    $doctor = auth('doctor')->user();
    $consultHref = $hrefSchedule;

    if ($doctor instanceof \App\Models\Doctor) {
        $nextUp = $doctor->appointments()
            ->whereIn('status', ['new', 'in_process'])
            ->orderBy('scheduled_at')
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->first();

        if ($nextUp !== null) {
            $consultHref = route('doctor.appointments.conversation', $nextUp);
        }
    }
@endphp

@if ($doctorLuxuryMobileNav)
    <div
        class="pointer-events-none fixed inset-x-0 bottom-0 z-[100] px-5 pb-[max(1rem,env(safe-area-inset-bottom))] lg:hidden"
        data-test="doctor-luxury-dock"
        x-data="{ callInProgress: false }"
        x-on:consultation-call-active.window="callInProgress = true"
        x-on:consultation-call-ended.window="callInProgress = false"
        x-show="!callInProgress"
        x-cloak
    >
        <nav
            class="patient-glass-nav pointer-events-auto mx-auto flex max-w-md items-end justify-between gap-0 rounded-full px-1 py-1 shadow-[0_8px_30px_rgb(0,0,0,0.08)]"
            aria-label="{{ __('doctor.mobile_nav_label') }}"
        >
            <a
                href="{{ $hrefHome }}"
                wire:navigate
                @class([
                    'patient-luxury-dock-btn',
                    'patient-luxury-dock-btn--active' => $activeHome,
                ])
                data-test="doctor-luxury-dock-home"
            >
                <flux:icon name="home" variant="outline" class="patient-luxury-dock-btn__icon" />
                <span class="patient-luxury-dock-btn__label">{{ __('doctor.nav.dashboard') }}</span>
            </a>

            <a
                href="{{ $hrefSchedule }}"
                wire:navigate
                @class([
                    'patient-luxury-dock-btn',
                    'patient-luxury-dock-btn--active' => $activeSchedule,
                ])
                data-test="doctor-luxury-dock-schedule"
            >
                <flux:icon name="calendar-days" variant="outline" class="patient-luxury-dock-btn__icon" />
                <span class="patient-luxury-dock-btn__label">{{ __('doctor.mobile.dock_schedule') }}</span>
            </a>

            <div class="relative -top-3.5 z-[55] w-14 shrink-0">
                <a
                    href="{{ $consultHref }}"
                    wire:navigate
                    class="patient-luxury-dock-fab pointer-events-auto relative z-[55] mx-auto"
                    aria-label="{{ __('doctor.mobile.open_consultation') }}"
                    data-test="doctor-luxury-dock-consult"
                >
                    <flux:icon name="video-camera" variant="solid" class="size-6 shrink-0 text-white" />
                </a>
            </div>

            <a
                href="{{ $hrefWallet }}"
                wire:navigate
                @class([
                    'patient-luxury-dock-btn',
                    'patient-luxury-dock-btn--active' => $activeWallet,
                ])
                data-test="doctor-luxury-dock-wallet"
            >
                <flux:icon name="credit-card" variant="outline" class="patient-luxury-dock-btn__icon" />
                <span class="patient-luxury-dock-btn__label">{{ __('doctor.menu.wallet') }}</span>
            </a>

            <a
                href="{{ $hrefMore }}"
                wire:navigate
                @class([
                    'patient-luxury-dock-btn',
                    'patient-luxury-dock-btn--active' => $activeMore,
                ])
                data-test="doctor-mobile-menu-open"
            >
                <flux:icon name="ellipsis-horizontal" variant="outline" class="patient-luxury-dock-btn__icon" />
                <span class="patient-luxury-dock-btn__label">{{ __('doctor.nav.menu') }}</span>
            </a>
        </nav>
    </div>
@endif
