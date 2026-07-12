<div
    class="doctor-luxury-duration relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-duration"
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
                {{ __('Duration and price') }}
            </h1>
        </div>
    </header>

    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
        <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-5 overflow-y-auto overscroll-contain px-5 pb-4 pt-1">
            <div>
                <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('Session durations') }}
                </h2>

                <div class="space-y-2">
                    @foreach ($durations as $duration)
                        @php
                            $durationKey = (string) $duration->duration;
                            $checked = in_array($durationKey, $doctorDurations, true);
                        @endphp
                        <div
                            wire:key="doctor-duration-{{ $durationKey }}"
                            class="rounded-2xl border border-slate-100 bg-white p-3.5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]"
                        >
                            <label class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="doctorDurations"
                                    value="{{ $durationKey }}"
                                    class="peer sr-only"
                                />
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-slate-200 text-transparent transition peer-checked:border-[#047857] peer-checked:bg-[#047857] peer-checked:text-white">
                                    <flux:icon name="check" variant="mini" class="size-3.5" />
                                </span>
                                <span class="text-sm font-bold text-slate-900">
                                    {{ $duration->duration }} {{ __('minutes') }}
                                </span>
                            </label>

                            @if ($checked)
                                <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                                    <span class="shrink-0 text-xs font-semibold text-slate-500">{{ __('Price') }}</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model="durationPrices.{{ $durationKey }}"
                                        class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-end text-sm font-semibold text-slate-900"
                                    />
                                    <span class="shrink-0 text-xs font-semibold text-slate-400">{{ config('currency.sa_riyal_symbol') }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <flux:error name="doctorDurations" />
                <flux:error name="durationPrices" />
            </div>

            <div>
                <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('Available communication types') }}
                </h2>

                <div class="grid grid-cols-3 gap-2">
                    @foreach ($communications as $communication)
                        <label class="relative block" wire:key="doctor-comm-{{ $communication->communication }}">
                            <input
                                type="checkbox"
                                wire:model.live="selectedCommunications"
                                value="{{ $communication->communication }}"
                                class="peer sr-only"
                            />
                            <span class="flex h-16 flex-col items-center justify-center gap-1 rounded-2xl border border-slate-200 px-2 text-[0.6875rem] font-bold text-slate-500 transition peer-checked:border-transparent peer-checked:bg-[#047857] peer-checked:text-white peer-checked:shadow-[0_4px_14px_-2px_rgba(4,120,87,0.4)]">
                                <flux:icon :name="$this->communicationIcon($communication->communication)" variant="outline" class="size-4" />
                                {{ $communication->title ?: str($communication->communication)->replace('_', ' ')->title() }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <flux:error name="selectedCommunications" />
            </div>

            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900">{{ __('Accept instant appointment notifications') }}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ __('doctor.mobile.duration_instant_hint') }}</p>
                </div>
                <button
                    type="button"
                    wire:click="toggleInstantAppointment"
                    role="switch"
                    aria-checked="{{ $acceptInstantAppointment ? 'true' : 'false' }}"
                    @class([
                        'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200',
                        'bg-[#047857]' => $acceptInstantAppointment,
                        'bg-slate-200' => ! $acceptInstantAppointment,
                    ])
                >
                    <span @class([
                        'inline-block size-5 transform rounded-full bg-white shadow transition-transform duration-200',
                        'translate-x-6 rtl:-translate-x-6' => $acceptInstantAppointment,
                        'translate-x-1 rtl:-translate-x-1' => ! $acceptInstantAppointment,
                    ])></span>
                </button>
            </div>

            @if (session('duration_saved'))
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-700">
                    {{ session('duration_saved') }}
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
