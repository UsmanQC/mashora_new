<x-layouts::patient>
    @php
        /** @var \App\Models\Appointment|null $appointment */
        $appointment = $appointment ?? null;
        $user = auth()->user();
        $profilePhotoUrl = $user !== null && filled($user->profile_photo_path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $user->profile_photo_path)
            : null;

        $headerSubtitle = __('patient_booking.luxury.payment_success_subtitle');

        if ($appointment !== null) {
            try {
                $dateLabel = \Illuminate\Support\Carbon::parse($appointment->appointment_date)
                    ->locale(app()->getLocale())
                    ->translatedFormat('l, j F Y');
                $timeLabel = \Illuminate\Support\Carbon::createFromFormat('H:i:s', (string) $appointment->start_time)
                    ->timezone(config('app.timezone'))
                    ->locale(app()->getLocale())
                    ->translatedFormat('g:i a');
                $headerSubtitle = implode(' · ', array_filter([$dateLabel, $timeLabel]));
            } catch (\Throwable) {
                // Keep default subtitle when appointment timestamps are unavailable.
            }
        }
    @endphp

    <div class="patient-luxury-payment-success bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-payment-success">
        <div class="sm:hidden">
            @include('partials.patient-luxury-page-header', [
                'title' => __('patient_booking.payment_success_title'),
                'subtitle' => $headerSubtitle,
                'profilePhotoUrl' => $profilePhotoUrl,
                'userName' => $user?->name,
                'testId' => 'patient-payment-success-header',
                'progressStep' => 3,
                'progressTotal' => 3,
            ])

            <main class="space-y-6 px-6 py-6" data-test="patient-payment-success-content">
                <div class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]">
                    <div class="absolute inset-x-0 top-0 h-1 bg-[#10B981]" aria-hidden="true"></div>

                    <div class="mx-auto mb-5 flex size-20 items-center justify-center rounded-full bg-emerald-50 text-[#10B981]">
                        <flux:icon name="check-circle" variant="solid" class="size-12" />
                    </div>

                    <h2 class="text-lg font-bold text-slate-900">{{ __('patient_booking.payment_success_title') }}</h2>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-slate-500">{{ __('patient_booking.payment_success_body') }}</p>

                    @if ($appointment !== null && filled($appointment->appointment_number))
                        <div class="mt-6 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('patient_booking.appointment_number_label') }}</span>
                            <span class="mt-1 block font-bold tabular-nums text-slate-900">{{ $appointment->appointment_number }}</span>
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <a
                        href="{{ route('patient.appointments') }}"
                        wire:navigate
                        class="flex w-full items-center justify-center rounded-2xl bg-[#10B981] py-4 text-base font-bold text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] transition active:scale-[0.98] hover:bg-[#059669]"
                        data-test="patient-payment-success-appointments"
                    >
                        {{ __('patient_booking.view_appointments') }}
                    </a>
                    <a
                        href="{{ route('patient.home') }}"
                        wire:navigate
                        class="flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white py-4 text-base font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        data-test="patient-payment-success-home"
                    >
                        {{ __('patient_booking.back_home') }}
                    </a>
                </div>

                <div class="flex items-center justify-center gap-2 pt-2 text-slate-400">
                    <flux:icon name="lock-closed" variant="outline" class="size-4" />
                    <span class="text-xs font-medium">{{ __('patient_booking.luxury.trust_badge') }}</span>
                </div>
            </main>
        </div>

        <div class="mx-auto hidden w-full max-w-lg px-6 py-4 sm:block sm:px-0 sm:py-0">
            <header class="mb-8 text-center">
                <nav class="mb-3 text-sm text-zinc-600" aria-label="Breadcrumb">
                    <ol class="flex flex-wrap items-center justify-center gap-2">
                        <li>
                            <flux:link :href="route('patient.home')" wire:navigate class="font-medium text-[#10B981] hover:text-[#064e3b]">
                                {{ __('patient.nav.home') }}
                            </flux:link>
                        </li>
                        <li aria-hidden="true" class="text-zinc-400">/</li>
                        <li class="font-semibold text-zinc-900">{{ __('patient_booking.payment_success_title') }}</li>
                    </ol>
                </nav>
            </header>

            <div class="rounded-3xl border border-slate-100/80 bg-white px-8 py-12 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]">
                <flux:icon name="check-circle" variant="solid" class="mx-auto size-14 text-[#10B981]" />
                <h1 class="mt-4 text-xl font-semibold text-slate-900">{{ __('patient_booking.payment_success_title') }}</h1>
                <p class="mt-2 text-slate-600">{{ __('patient_booking.payment_success_body') }}</p>

                @if ($appointment !== null && filled($appointment->appointment_number))
                    <p class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                        <span class="font-semibold">{{ __('patient_booking.appointment_number_label') }}</span>
                        {{ $appointment->appointment_number }}
                    </p>
                @endif

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <flux:button :href="route('patient.appointments')" variant="outline" wire:navigate>
                        {{ __('patient_booking.view_appointments') }}
                    </flux:button>
                    <flux:button :href="route('patient.home')" variant="primary" class="border-[#064e3b] !bg-[#064e3b] !text-white hover:!brightness-[0.97]" wire:navigate>
                        {{ __('patient_booking.back_home') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</x-layouts::patient>
