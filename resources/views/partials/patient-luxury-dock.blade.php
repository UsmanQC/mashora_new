@php
    $guest = ! auth()->check();
    $phoneEntry = route('patient.phone');

    $activeHome = request()->routeIs('patient.home');
    $activeAppointments = request()->routeIs([
        'patient.appointments',
        'patient.schedule.filter',
        'patient.schedule.specialists',
        'patient.book-appointments',
        'patient.checkout',
        'patient.checkout.demo',
        'patient.payment.success',
        'patient.payment.failed',
        'patient.appointments.conversation',
        'patient.appointments.missed.reschedule',
    ]);
    $activeMessages = request()->routeIs('patient.notifications');
    $activeProfile = request()->routeIs([
        'patient.menu',
        'profile.edit',
        'patient.wallet',
        'patient.settings.*',
    ]);

    $hrefHome = route('patient.home');
    $hrefAppointments = $guest ? $phoneEntry : route('patient.appointments');
    $hrefMessages = $guest ? $phoneEntry : route('patient.notifications');
    $hrefProfile = $guest ? $phoneEntry : route('patient.menu');

    $unreadMessages = 0;
    if (! $guest) {
        $unreadMessages = \App\Models\Notification::query()
            ->where('userable_type', \App\Models\User::class)
            ->where('userable_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }
@endphp

<div
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 px-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:hidden"
    data-test="patient-luxury-dock"
>
    <nav
        class="patient-glass-nav pointer-events-auto mx-auto flex max-w-md items-center justify-between rounded-full px-2 py-2 shadow-[0_8px_30px_rgb(0,0,0,0.08)]"
        aria-label="{{ __('patient.nav.label') }}"
    >
        <a
            href="{{ $hrefHome }}"
            wire:navigate
            @class([
                'patient-luxury-dock-btn',
                'patient-luxury-dock-btn--active' => $activeHome,
            ])
            data-test="patient-luxury-dock-home"
        >
            <flux:icon name="squares-2x2" variant="outline" class="patient-luxury-dock-btn__icon" />
            <span class="patient-luxury-dock-btn__label">{{ __('patient.nav.home') }}</span>
        </a>

        <a
            href="{{ $hrefAppointments }}"
            wire:navigate
            @class([
                'patient-luxury-dock-btn',
                'patient-luxury-dock-btn--active' => $activeAppointments,
            ])
            data-test="patient-luxury-dock-appointments"
        >
            <flux:icon name="calendar-days" variant="outline" class="patient-luxury-dock-btn__icon" />
            <span class="patient-luxury-dock-btn__label">{{ __('patient.nav.my_appointments') }}</span>
        </a>

        <div class="relative -top-5 shrink-0">
            <button
                type="button"
                data-open-ai-chatbot
                class="patient-luxury-dock-fab"
                aria-label="{{ __('patient.nav.ai_assistant') }}"
                data-test="patient-luxury-dock-chatbot"
            >
                <flux:icon name="sparkles" variant="outline" class="size-6 shrink-0" />
            </button>
        </div>

        <a
            href="{{ $hrefMessages }}"
            wire:navigate
            @class([
                'patient-luxury-dock-btn relative',
                'patient-luxury-dock-btn--active' => $activeMessages,
            ])
            data-test="patient-luxury-dock-messages"
        >
            <flux:icon name="chat-bubble-left-ellipsis" variant="outline" class="patient-luxury-dock-btn__icon" />
            <span class="patient-luxury-dock-btn__label">{{ __('patient.nav.messages') }}</span>
            @if ($unreadMessages > 0)
                <span class="absolute top-2 end-4 size-2 rounded-full bg-[#10B981] ring-2 ring-white" aria-hidden="true"></span>
            @endif
        </a>

        <a
            href="{{ $hrefProfile }}"
            wire:navigate
            @class([
                'patient-luxury-dock-btn',
                'patient-luxury-dock-btn--active' => $activeProfile,
            ])
            data-test="patient-luxury-dock-profile"
        >
            <flux:icon name="user" variant="outline" class="patient-luxury-dock-btn__icon" />
            <span class="patient-luxury-dock-btn__label">{{ __('patient.nav.my_account') }}</span>
        </a>
    </nav>
</div>
