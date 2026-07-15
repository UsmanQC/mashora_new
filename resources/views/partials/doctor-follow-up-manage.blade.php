@php
    /** @var \App\Models\Appointment|null $followUp */
    /** @var bool $canReschedule */
    $canReschedule = $canReschedule ?? false;
@endphp

@if ($followUp instanceof \App\Models\Appointment)
    <flux:callout
        variant="success"
        icon="information-circle"
        class="mt-6 !border-[#10B981]/40 !bg-[#D1FAE5] [&_*]:!text-black"
        data-test="doctor-follow-up-manage"
    >
        <div class="space-y-2">
            <p class="font-semibold">
                {{ $followUp->isPendingFollowUp()
                    ? __('doctor.follow_up.pending_title')
                    : __('doctor.follow_up.scheduled_title') }}
            </p>
            <p class="text-sm">
                {{ $followUp->isPendingFollowUp()
                    ? __('doctor.follow_up.pending_body')
                    : __('doctor.follow_up.scheduled_body') }}
            </p>
            <p class="text-sm font-medium">
                @if ($followUp->isPendingFollowUp())
                    {{ __('doctor.follow_up.pending_status') }} —
                @endif
                {{ $followUp->appointment_date?->format('d/m/Y') }}
                {{ $this->displaySlot(substr((string) $followUp->start_time, 0, 5)) }}
                — {{ $this->followUpStatusLabel($followUp) }}
            </p>
            <p class="text-sm">{{ __('doctor.follow_up.manage_actions_hint') }}</p>
        </div>
    </flux:callout>

    @unless ($this->isRescheduling)
        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
            @if ($canReschedule)
                <flux:button
                    type="button"
                    wire:click="startRescheduleFollowUp"
                    variant="primary"
                    class="!bg-[#047857] !text-white hover:!brightness-95"
                    icon="calendar-days"
                    data-test="doctor-follow-up-reschedule"
                >
                    {{ __('doctor.follow_up.reschedule') }}
                </flux:button>
            @endif
            <flux:button
                type="button"
                wire:click="promptCancelFollowUp"
                class="!border-rose-200 !bg-rose-50 !text-rose-700 hover:!bg-rose-100"
                icon="x-circle"
                data-test="doctor-follow-up-cancel"
            >
                {{ __('doctor.follow_up.cancel') }}
            </flux:button>
        </div>
    @endunless

    @if ($this->isRescheduling)
        <div
            class="mt-6 rounded-2xl border border-[#10B981]/50 bg-[#D1FAE5] p-5 text-black shadow-sm sm:p-6 [&_[data-flux-description]]:text-black [&_[data-flux-label]]:text-black [&_[data-flux-text]]:text-black"
            data-test="doctor-follow-up-reschedule-box"
        >
            <flux:heading size="md" class="font-semibold text-black">{{ __('doctor.follow_up.reschedule_title') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-black">{{ __('doctor.follow_up.reschedule_body', ['days' => $this->windowDays()]) }}</flux:text>

            <form wire:submit="saveReschedule" class="mt-5 space-y-5">
                <flux:field>
                    <flux:label>{{ __('doctor.follow_up.date_label') }}</flux:label>
                    <flux:input wire:model.live="newDate" type="date" min="{{ $this->minDate() }}" max="{{ $this->maxDate() }}" required />
                    <flux:description class="text-xs leading-relaxed sm:text-sm">{{ __('doctor.follow_up.date_window_hint', [
                        'min' => $this->minDate(),
                        'max' => $this->maxDate(),
                        'days' => $this->windowDays(),
                        'session' => $this->sessionDateLabel(),
                    ]) }}</flux:description>
                    <flux:error name="newDate" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('doctor.follow_up.time_label') }}</flux:label>
                    @if ($this->availableSlots === [])
                        <flux:callout variant="warning" icon="exclamation-circle" class="mt-2">
                            {{ __('doctor.follow_up.no_slots') }}
                        </flux:callout>
                    @else
                        <div wire:key="follow-up-reschedule-slots-{{ $newDate }}" class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($this->availableSlots as $slot)
                                <button
                                    type="button"
                                    wire:click="$set('selectedTime', '{{ $slot }}')"
                                    @class([
                                        'rounded-full border px-4 py-2 text-sm font-semibold transition',
                                        'border-[#047857] bg-[#047857] text-white' => $selectedTime === $slot,
                                        'border-zinc-200 bg-white text-zinc-700 hover:border-emerald-400' => $selectedTime !== $slot,
                                    ])
                                >
                                    {{ $this->displaySlot($slot) }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <flux:error name="selectedTime" />
                </flux:field>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <flux:button type="button" wire:click="dismissRescheduleFollowUp" class="!border-zinc-300 !bg-white !text-zinc-800">
                        {{ __('doctor.appointments.cancel_modal.dismiss') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                        {{ __('doctor.follow_up.reschedule_submit') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif
@endif
