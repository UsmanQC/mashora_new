<div
    class="doctor-luxury-working-hours relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-working-hours"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.menu') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.auth.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-xl font-bold tracking-tight text-slate-900">
                {{ __('Working hours') }}
            </h1>
        </div>
    </header>

    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
        <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-3 overflow-y-auto overscroll-contain px-5 pb-4 pt-1">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-full bg-[#10B981]/10 px-3 py-2 text-xs font-bold text-[#047857]"
                >
                    <flux:icon name="square-2-stack" variant="mini" class="size-4" />
                    {{ __('doctor.mobile.working_hours_copy_action') }}
                </button>
                <button
                    type="button"
                    wire:click="setVacationMode"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm"
                >
                    <flux:icon name="paper-airplane" variant="mini" class="size-4" />
                    {{ __('doctor.mobile.working_hours_vacation_action') }}
                </button>
            </div>

            @foreach ($daysOfWeek as $dayOfWeek)
                @php $isOn = in_array($dayOfWeek, $availabilities, true); @endphp
                <section
                    wire:key="doctor-wh-day-{{ $dayOfWeek }}"
                    @class([
                        'rounded-3xl border p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition',
                        'border-slate-100 bg-white' => $isOn,
                        'border-slate-100 bg-slate-100/60' => ! $isOn,
                    ])
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p @class([
                                'text-sm font-bold capitalize',
                                'text-slate-900' => $isOn,
                                'text-slate-400' => ! $isOn,
                            ])>
                                {{ __($dayOfWeek) }}
                            </p>
                            <p @class([
                                'mt-0.5 text-xs',
                                'text-slate-500' => $isOn,
                                'text-slate-400' => ! $isOn,
                            ])>
                                {{ $this->daySummaryLabel($dayOfWeek) }}
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="toggleDay('{{ $dayOfWeek }}')"
                            role="switch"
                            aria-checked="{{ $isOn ? 'true' : 'false' }}"
                            aria-label="{{ __($dayOfWeek) }}"
                            @class([
                                'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200',
                                'bg-[#047857]' => $isOn,
                                'bg-slate-200' => ! $isOn,
                            ])
                        >
                            <span @class([
                                'inline-block size-5 transform rounded-full bg-white shadow transition-transform duration-200',
                                'translate-x-6 rtl:-translate-x-6' => $isOn,
                                'translate-x-1 rtl:-translate-x-1' => ! $isOn,
                            ])></span>
                        </button>
                    </div>

                    @if ($isOn)
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @foreach ($this->dayChipsFor($dayOfWeek) as $chip)
                                @if ($chip['type'] === 'break')
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">
                                        {{ __('doctor.mobile.working_hours_break_label') }} {{ $chip['label'] }}
                                    </span>
                                @else
                                    @php $slotIndex = $chip['index']; @endphp
                                    <div
                                        x-data="{ editing: false }"
                                        wire:key="doctor-wh-slot-{{ $dayOfWeek }}-{{ $slotIndex }}"
                                        class="inline-flex"
                                    >
                                        <button
                                            type="button"
                                            x-show="!editing"
                                            @click="editing = true"
                                            class="inline-flex items-center rounded-full bg-[#10B981]/10 px-3 py-1.5 text-xs font-bold text-[#047857]"
                                        >
                                            {{ $chip['label'] }}
                                        </button>
                                        <div
                                            x-show="editing"
                                            x-cloak
                                            class="flex items-center gap-1.5 rounded-full border border-emerald-200 bg-white px-2 py-1"
                                        >
                                            <input
                                                type="time"
                                                step="1"
                                                wire:model="workingHours.{{ $dayOfWeek }}.{{ $slotIndex }}.start_time"
                                                class="doctor-working-hours-time-input w-16 rounded-md border-0 bg-transparent p-0 text-xs font-semibold text-slate-900 focus:ring-0"
                                            />
                                            <span class="text-slate-300">–</span>
                                            <input
                                                type="time"
                                                step="1"
                                                wire:model="workingHours.{{ $dayOfWeek }}.{{ $slotIndex }}.end_time"
                                                class="doctor-working-hours-time-input w-16 rounded-md border-0 bg-transparent p-0 text-xs font-semibold text-slate-900 focus:ring-0"
                                            />
                                            <button type="button" @click="editing = false" class="text-[#047857]" aria-label="{{ __('Save') }}">
                                                <flux:icon name="check" variant="mini" class="size-4" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeSlot('{{ $dayOfWeek }}', {{ $slotIndex }})"
                                                class="text-rose-500"
                                                aria-label="{{ __('Remove') }}"
                                            >
                                                <flux:icon name="trash" variant="mini" class="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <button
                                type="button"
                                wire:click="addSlot('{{ $dayOfWeek }}')"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600"
                            >
                                <flux:icon name="plus" variant="mini" class="size-3.5" />
                                {{ __('doctor.mobile.working_hours_add_shift') }}
                            </button>
                        </div>
                    @endif
                </section>
            @endforeach

            <flux:error name="availabilities" />
            <flux:error name="workingHours" />

            @if (session('working_hours_saved'))
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-700">
                    {{ session('working_hours_saved') }}
                </p>
            @endif
        </main>

        <div class="shrink-0 border-t border-slate-100 bg-white px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-3">
            <button
                type="submit"
                class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-[#047857] text-sm font-bold text-white shadow-sm transition active:scale-[0.98]"
            >
                {{ __('Save') }}
            </button>
        </div>
    </form>
</div>
