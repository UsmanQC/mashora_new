@php
    /** @var \App\Models\Appointment $appointment */
    /** @var \Livewire\Component $component */
    $status = (string) $appointment->status;
    $showTimer = $component->shouldShowStartTimer($appointment);
    $showPaymentTimer = $component->shouldShowPaymentTimer($appointment);
    $canJoinSession = $status === 'in_process' && $appointment->allowsPatientCalls();
    $canOpenChat = $component->canOpenChat($appointment);
    $sessionStartPending = $appointment->isSessionStartRequestPending();
    $canResolvePaymentMissed = $component->canResolvePaymentMissed($appointment);
    $awaitingDoctor = in_array($status, ['new', 'rescheduled'], true)
        && (! $appointment->is_follow_up || $appointment->allowsPatientCalls())
        && ! $sessionStartPending
        && ! $canOpenChat;
    $canResolveMissed = $component->canResolveMissed($appointment);
    $hasMissedRefund = $component->hasMissedRefund($appointment);
    $hasAction = $appointment->status === 'pending_follow_up' || $canJoinSession || $canOpenChat || $sessionStartPending || $awaitingDoctor || $canResolveMissed || $canResolvePaymentMissed || ($appointment->isDoctorMissed() && $hasMissedRefund);
    $doctorName = $appointment->doctor?->displayName() ?: __('patient.appointments.title');
@endphp

<article class="flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_8px_30px_-12px_rgba(21,101,192,0.18)] ring-1 ring-zinc-100">
    @if ($showPaymentTimer)
        @include('partials.patient-payment-deadline-banner', [
            'expiresAtIso' => $component->paymentExpiresAtIso($appointment),
            'variant' => 'card',
        ])
    @elseif ($showTimer)
        <div
            x-data="appointmentStartTimer(@js($component->sessionStartsAtIso($appointment)))"
            x-init="start()"
            class="flex items-center justify-between gap-3 bg-gradient-to-r from-[#10B981] to-[#047857] px-4 py-3 text-white"
            :aria-label="ariaLabel"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="relative flex size-2.5 shrink-0">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-white/50 opacity-75"></span>
                    <span class="relative inline-flex size-2.5 rounded-full bg-white"></span>
                </span>
                <span class="text-sm font-semibold tracking-tight">{{ $component->statusLabelFor($appointment) }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-xs font-bold tabular-nums backdrop-blur-sm">
                <flux:icon name="clock" variant="micro" class="size-3.5 opacity-90" />
                <span x-show="showPrefix" class="font-medium opacity-90">{{ __('patient.appointments.starts_in_label') }}</span>
                <span x-text="label"></span>
            </div>
        </div>
    @endif

    <div class="flex flex-1 flex-col p-4 sm:p-5">
        {{-- Doctor --}}
        <div class="flex items-start gap-3.5">
            <flux:avatar
                :name="$doctorName"
                circle
                size="lg"
                class="shrink-0 bg-[#10B981]/10 text-[#10B981] [&_[data-slot=avatar]]:size-12 sm:[&_[data-slot=avatar]]:size-14"
            />

            <div class="min-w-0 flex-1 pt-0.5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                            {{ __('patient.appointments.specialist_label') }}
                        </p>
                        <h2 class="mt-0.5 text-base font-semibold leading-snug text-zinc-900 sm:text-lg">
                            {{ $doctorName }}
                        </h2>
                    </div>
                    @unless ($showTimer || $showPaymentTimer)
                        <span @class([
                            'inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                            $component->statusBadgeClassesFor($appointment),
                        ])>
                            {{ $component->statusLabelFor($appointment) }}
                        </span>
                    @endunless
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="mt-4 grid grid-cols-2 gap-2.5">
            <div class="flex items-center gap-2.5 rounded-xl border border-zinc-100 bg-zinc-50/80 px-3 py-2.5">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#10B981] shadow-sm ring-1 ring-zinc-100">
                    <flux:icon name="calendar-days" variant="micro" class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.date_label') }}</p>
                    <p class="truncate text-sm font-semibold text-zinc-800">{{ $component->formattedSessionDate($appointment) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 rounded-xl border border-zinc-100 bg-zinc-50/80 px-3 py-2.5">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#10B981] shadow-sm ring-1 ring-zinc-100">
                    <flux:icon name="clock" variant="micro" class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.time_label') }}</p>
                    <p class="truncate text-sm font-semibold tabular-nums text-zinc-800">{{ $component->formattedSessionTime($appointment) }}</p>
                </div>
            </div>
        </div>

        {{-- Action --}}
        @if ($hasAction)
            <div class="mt-4">
                @if ($appointment->status === 'pending_follow_up')
                    @if ($appointment->requiresPatientPayment())
                        <flux:button
                            :href="route('patient.follow-up.confirm', $appointment)"
                            wire:navigate
                            class="w-full !rounded-xl !bg-violet-600 !py-2.5 !text-white hover:!brightness-95"
                            icon="credit-card"
                        >
                            {{ __('patient.scheduled_appointment.confirm_and_pay') }}
                        </flux:button>
                    @else
                        <flux:button
                            :href="$appointment->patient_confirmed_at === null ? route('patient.follow-up.confirm', $appointment) : route('patient.follow-up.pay', $appointment)"
                            wire:navigate
                            class="w-full !rounded-xl !border-violet-200 !bg-violet-50 !py-2.5 !text-violet-900 hover:!bg-violet-100"
                            icon="credit-card"
                        >
                            {{ __('patient.follow_up.confirm_and_pay') }}
                        </flux:button>
                    @endif
                @elseif ($canResolvePaymentMissed)
                    <div class="rounded-xl border border-orange-200/90 bg-gradient-to-r from-orange-50 to-amber-50/80 px-3.5 py-3">
                        <p class="text-sm font-semibold text-orange-950">{{ __('patient.scheduled_appointment.missed_card_title') }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-orange-900/90">{{ __('patient.scheduled_appointment.missed_card_body') }}</p>
                        <flux:button
                            :href="route('patient.appointments.payment-missed-reschedule', $appointment)"
                            wire:navigate
                            class="mt-3 w-full !rounded-xl !bg-violet-600 !py-2.5 !text-white hover:!brightness-95"
                            icon="calendar-days"
                        >
                            {{ __('patient.scheduled_appointment.choose_appointment') }}
                        </flux:button>
                    </div>
                @elseif ($canJoinSession)
                    <flux:button
                        :href="route('patient.appointments.conversation', ['appointment' => $appointment->id])"
                        wire:navigate
                        class="w-full !rounded-xl !bg-[#10B981] !py-2.5 !text-white shadow-md shadow-[#10B981]/25 hover:!brightness-95"
                        icon="video-camera"
                    >
                        {{ __('patient.appointments.join_session') }}
                    </flux:button>
                @elseif ($sessionStartPending)
                    <div class="rounded-xl border border-amber-200/90 bg-gradient-to-r from-amber-50 to-orange-50/80 px-3.5 py-3">
                        <p class="text-sm font-semibold text-amber-950">{{ __('patient.appointments.session_start_request_pending') }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-900/90">{{ __('patient.appointments.session_start_request_banner') }}</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                            <flux:button
                                type="button"
                                wire:click="approveSessionStart({{ $appointment->id }})"
                                wire:loading.attr="disabled"
                                class="flex-1 !rounded-xl !bg-[#10B981] !py-2.5 !text-white hover:!brightness-95"
                            >
                                {{ __('patient.appointments.session_start_request_approve') }}
                            </flux:button>
                            <flux:button
                                type="button"
                                wire:click="declineSessionStart({{ $appointment->id }})"
                                wire:loading.attr="disabled"
                                class="flex-1 !rounded-xl !border-zinc-300 !bg-white !py-2.5 !text-black hover:!bg-zinc-50"
                            >
                                {{ __('patient.appointments.session_start_request_decline') }}
                            </flux:button>
                        </div>
                    </div>
                @elseif ($canOpenChat)
                    <flux:button
                        :href="route('patient.appointments.conversation', ['appointment' => $appointment->id])"
                        wire:navigate
                        class="w-full !rounded-xl !border-emerald-200 !bg-emerald-50 !py-2.5 !text-emerald-900 hover:!bg-emerald-100"
                        icon="chat-bubble-left-right"
                    >
                        {{ __('patient.appointments.open_chat') }}
                    </flux:button>
                    @if (! $appointment->is_follow_up)
                        <p class="mt-2 text-center text-xs text-emerald-800/80">
                            {{ __('patient.appointments.chat_open_until_card', [
                                'date' => $appointment->chatOpenUntil()->locale(app()->getLocale())->translatedFormat('d M Y'),
                            ]) }}
                        </p>
                    @endif
                @elseif ($awaitingDoctor)
                    <div class="flex items-start gap-3 rounded-xl border border-amber-200/80 bg-gradient-to-r from-amber-50 to-orange-50/80 px-3.5 py-3">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                            <flux:icon name="clock" variant="mini" class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-amber-950">{{ __('patient.appointments.waiting_for_doctor') }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-amber-800/80">{{ __('patient.appointments.waiting_for_doctor_hint') }}</p>
                        </div>
                    </div>
                @elseif ($canResolveMissed)
                    <div class="rounded-xl border border-orange-200/90 bg-gradient-to-r from-orange-50 to-amber-50/80 px-3.5 py-3">
                        <p class="text-sm font-semibold text-orange-950">{{ __('patient.missed.prompt') }}</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                            <flux:button
                                :href="route('patient.appointments.missed-reschedule', $appointment)"
                                wire:navigate
                                class="flex-1 !rounded-xl !border-[#10B981]/30 !bg-white !py-2.5 !text-[#047857] hover:!bg-[#10B981]/5"
                                icon="calendar-days"
                            >
                                {{ __('patient.missed.reschedule') }}
                            </flux:button>
                            <flux:button
                                type="button"
                                wire:click="promptRefundMissed({{ $appointment->id }})"
                                class="flex-1 !rounded-xl !bg-[#10B981] !py-2.5 !text-white hover:!brightness-95"
                                icon="banknotes"
                            >
                                {{ __('patient.missed.refund') }}
                            </flux:button>
                        </div>
                    </div>
                @elseif ($appointment->isDoctorMissed() && $hasMissedRefund)
                    <div class="flex items-center gap-2 rounded-xl border border-emerald-200/90 bg-emerald-50 px-3.5 py-3 text-sm font-semibold text-emerald-900">
                        <flux:icon name="check-circle" variant="mini" class="size-5 shrink-0 text-emerald-600" />
                        {{ __('patient.missed.refunded_label') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- Footer --}}
        @if (filled($appointment->appointment_number) || filled($appointment->patient_name))
            <div class="mt-4 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-t border-zinc-100 pt-3 text-xs text-zinc-500">
                @if (filled($appointment->appointment_number))
                    <span>
                        {{ __('patient.appointments.reference_label') }}
                        <span class="font-medium text-zinc-600">#{{ $appointment->appointment_number }}</span>
                    </span>
                @endif
                @if (filled($appointment->patient_name))
                    <span class="truncate font-medium text-zinc-500">{{ $appointment->patient_name }}</span>
                @endif
            </div>
        @endif
    </div>
</article>
