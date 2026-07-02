@php
    $chevronForward = app()->getLocale() === 'ar' ? 'arrow-left' : 'arrow-right';
    $chevronBack = app()->getLocale() === 'ar' ? 'arrow-right' : 'arrow-left';
@endphp

@if ($mobileStep === 1)
    <main class="space-y-8 px-6 py-6" data-test="patient-booking-step-intake">
        <section class="space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">{{ __('patient_booking.luxury.who_title') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('patient_booking.luxury.who_hint') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3" role="radiogroup" aria-label="{{ __('patient_booking.booking_for_label') }}">
                @foreach (['self' => ['icon' => 'user', 'label' => __('patient_booking.for_self')], 'another' => ['icon' => 'users', 'label' => __('patient_booking.for_other')]] as $value => $meta)
                    <button
                        type="button"
                        wire:key="booking-for-{{ $value }}"
                        wire:click="selectAppointmentFor('{{ $value }}')"
                        @class([
                            'patient-luxury-booking-option relative flex flex-col items-center justify-center gap-2 rounded-2xl border-2 p-4 text-center',
                            'patient-luxury-booking-option--active border-[#10B981] bg-emerald-50' => $appointmentFor === $value,
                            'border-slate-100 bg-white hover:border-slate-200' => $appointmentFor !== $value,
                        ])
                        aria-pressed="{{ $appointmentFor === $value ? 'true' : 'false' }}"
                    >
                        <span @class([
                            'absolute top-3 start-3 transition-opacity',
                            'opacity-100' => $appointmentFor === $value,
                            'opacity-0' => $appointmentFor !== $value,
                        ])>
                            <flux:icon name="check-circle" variant="solid" class="size-5 text-[#10B981]" />
                        </span>
                        <flux:icon
                            name="{{ $meta['icon'] }}"
                            variant="outline"
                            @class([
                                'size-6 stroke-[1.5]',
                                'text-[#059669]' => $appointmentFor === $value,
                                'text-slate-400' => $appointmentFor !== $value,
                            ])
                        />
                        <span @class([
                            'text-sm',
                            'font-semibold text-emerald-900' => $appointmentFor === $value,
                            'font-medium text-slate-600' => $appointmentFor !== $value,
                        ])>{{ $meta['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">{{ __('patient_booking.luxury.method_title') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('patient_booking.luxury.method_hint') }}</p>
            </div>

            <div class="grid grid-cols-3 gap-3" role="group" aria-label="{{ __('patient_booking.communication_label') }}">
                @foreach (['video' => 'video-camera', 'voice' => 'phone', 'chat' => 'chat-bubble-left-right'] as $channel => $icon)
                    <button
                        type="button"
                        wire:key="booking-comm-{{ $channel }}"
                        wire:click="selectCommunication('{{ $channel }}')"
                        @class([
                            'patient-luxury-booking-option relative flex flex-col items-center justify-center gap-2 rounded-2xl border-2 p-3 text-center',
                            'patient-luxury-booking-option--active border-[#10B981] bg-emerald-50' => $this->communicationCardActive($channel),
                            'border-slate-100 bg-white hover:border-slate-200' => ! $this->communicationCardActive($channel),
                        ])
                        aria-pressed="{{ $this->communicationCardActive($channel) ? 'true' : 'false' }}"
                    >
                        <flux:icon
                            name="{{ $icon }}"
                            variant="outline"
                            @class([
                                'size-5 stroke-[1.5]',
                                'text-[#059669]' => $this->communicationCardActive($channel),
                                'text-slate-400' => ! $this->communicationCardActive($channel),
                            ])
                        />
                        <span @class([
                            'text-xs',
                            'font-semibold text-emerald-900' => $this->communicationCardActive($channel),
                            'font-medium text-slate-600' => ! $this->communicationCardActive($channel),
                        ])>{{ __('patient_booking.channel_'.$channel) }}</span>
                    </button>
                @endforeach
            </div>
            @error('communications')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </section>

        <section class="space-y-5 border-t border-slate-100 pt-6">
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-700">
                    {{ __('patient_booking.patient_name') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                        <flux:icon name="user" variant="outline" class="size-5 text-slate-400" />
                    </div>
                    <input
                        type="text"
                        wire:model.blur="patientName"
                        autocomplete="name"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white py-3.5 pe-11 ps-4 text-sm text-slate-900 shadow-sm transition focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/20"
                        placeholder="{{ __('patient_booking.patient_name') }}"
                    />
                </div>
                @error('patientName')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-700">
                    {{ __('patient_booking.patient_phone') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    type="tel"
                    wire:model.blur="patientPhone"
                    autocomplete="tel"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm text-slate-900 shadow-sm transition focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/20"
                    placeholder="{{ __('patient_booking.patient_phone') }}"
                />
                @error('patientPhone')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-700">{{ __('patient_booking.luxury.notes_label') }}</label>
                <textarea
                    wire:model.blur="patientNotes"
                    rows="3"
                    maxlength="300"
                    class="w-full resize-none rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-900 shadow-sm transition focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/20"
                    placeholder="{{ __('patient_booking.luxury.notes_placeholder') }}"
                ></textarea>
                @error('patientNotes')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </section>
    </main>
@else
    <main class="space-y-6 px-4 py-6" data-test="patient-booking-step-summary">
        <div class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            @if ($this->doctorPhotoUrl() !== null)
                <img src="{{ $this->doctorPhotoUrl() }}" alt="" class="size-14 shrink-0 rounded-full object-cover" />
            @else
                <flux:avatar :name="$this->doctor->displayName()" circle class="size-14 shrink-0" />
            @endif
            <div class="min-w-0">
                <h2 class="truncate text-sm font-bold text-slate-900">{{ $this->doctor->displayName() }}</h2>
                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $this->doctorSpecialtyLabel() }}</p>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm">
            <div class="absolute inset-x-0 top-0 h-1 bg-[#10B981]" aria-hidden="true"></div>

            <div class="space-y-5 p-6">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">{{ __('patient_booking.session_date') }}</span>
                        <span class="font-bold text-slate-900">{{ $this->displayedLuxurySessionDate() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">{{ __('patient_booking.session_time') }}</span>
                        <span class="font-bold text-slate-900">{{ $this->displayedSessionTime() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">{{ __('patient_booking.session_duration') }}</span>
                        <span class="font-bold text-slate-900">{{ __('patient_booking.luxury.duration_minutes', ['minutes' => $durationMinutes]) }}</span>
                    </div>
                </div>

                <div class="patient-luxury-booking-receipt-divider w-full" aria-hidden="true"></div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">{{ __('patient_booking.session_price') }}</span>
                        <span class="font-semibold tabular-nums text-slate-900">{{ number_format($sessionPrice, 0) }} {{ __('patient_booking.sar') }}</span>
                    </div>

                    @if ($discountAmount > 0)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">{{ __('patient_booking.discount') }}</span>
                            <span class="font-semibold tabular-nums text-slate-900">−{{ number_format($discountAmount, 0) }} {{ __('patient_booking.sar') }}</span>
                        </div>
                    @endif

                    <div class="mt-2 flex gap-2">
                        <div class="relative min-w-0 flex-1">
                            <flux:icon name="tag" variant="outline" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                wire:model.live="discountCode"
                                autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pe-9 ps-3 text-sm uppercase transition focus:border-[#10B981] focus:outline-none"
                                placeholder="{{ __('patient_booking.luxury.discount_placeholder') }}"
                            />
                        </div>
                        <button
                            type="button"
                            wire:click="applyDiscountCode"
                            class="shrink-0 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            {{ __('patient_booking.discount_apply') }}
                        </button>
                    </div>
                    @error('discountCode')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-end justify-between gap-3 border-t border-slate-100 bg-slate-50 p-6">
                <div>
                    <span class="mb-1 block text-xs font-semibold text-slate-500">{{ __('patient_booking.luxury.total_due') }}</span>
                    <span class="text-xs text-slate-400">{{ __('patient_booking.luxury.vat_included') }}</span>
                </div>
                <div class="flex items-baseline gap-1 text-[#059669]">
                    <span class="text-2xl font-bold tabular-nums">{{ number_format($this->totalPrice(), 0) }}</span>
                    <span class="text-sm font-bold">{{ __('patient_booking.sar') }}</span>
                </div>
            </div>
        </div>

        @error('appointment_conflict')
            <p class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
        @enderror

        <div class="flex items-center justify-center gap-2 pt-2 text-slate-400">
            <flux:icon name="lock-closed" variant="outline" class="size-4" />
            <span class="text-xs font-medium">{{ __('patient_booking.luxury.trust_badge') }}</span>
        </div>
    </main>
@endif
