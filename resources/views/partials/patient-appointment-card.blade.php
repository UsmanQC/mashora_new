@php
    /** @var \App\Models\Appointment $appointment */
    /** @var \Livewire\Component $component */
    $status = (string) $appointment->status;
    $showTimer = $component->shouldShowStartTimer($appointment);
    $canJoinSession = $status === 'in_process';
    $awaitingDoctor = in_array($status, ['new', 'rescheduled'], true);
    $hasAction = $appointment->status === 'pending_follow_up' || $canJoinSession || $awaitingDoctor;
    $doctorName = $appointment->doctor?->displayName() ?: __('patient.appointments.title');
@endphp

<article class="flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_8px_30px_-12px_rgba(21,101,192,0.18)] ring-1 ring-zinc-100">
    @if ($showTimer)
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
                <span class="text-sm font-semibold tracking-tight">{{ $component->statusLabel($status) }}</span>
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
                    @unless ($showTimer)
                        <span @class([
                            'inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                            $component->statusBadgeClasses($status),
                        ])>
                            {{ $component->statusLabel($status) }}
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
                    <flux:button
                        :href="$appointment->patient_confirmed_at === null ? route('patient.follow-up.confirm', $appointment) : route('patient.follow-up.pay', $appointment)"
                        wire:navigate
                        class="w-full !rounded-xl !border-violet-200 !bg-violet-50 !py-2.5 !text-violet-900 hover:!bg-violet-100"
                        icon="credit-card"
                    >
                        {{ __('patient.follow_up.confirm_and_pay') }}
                    </flux:button>
                @elseif ($canJoinSession)
                    <flux:button
                        :href="route('patient.appointments.conversation', ['appointment' => $appointment->id])"
                        wire:navigate
                        class="w-full !rounded-xl !bg-[#10B981] !py-2.5 !text-white shadow-md shadow-[#10B981]/25 hover:!brightness-95"
                        icon="video-camera"
                    >
                        {{ __('patient.appointments.join_session') }}
                    </flux:button>
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
