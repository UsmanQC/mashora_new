<div>
    <flux:modal wire:model.self="showMoodModal" class="w-full max-w-full sm:!max-w-4xl" :closable="true">
        <div class="relative px-4 pt-10 pb-8 sm:px-8 md:pb-12">
            <div class="mx-auto mb-8 max-w-2xl space-y-2 text-center lg:mb-10">
                <flux:heading level="3" size="xl" class="font-semibold text-[#10B981]">
                    {{ __('patient.mood_modal_title') }}
                </flux:heading>
                <flux:text class="font-semibold text-[#10B981] md:text-lg">
                    {{ now()->timezone(config('app.timezone'))->locale(app()->getLocale())->translatedFormat(__('patient.mood_modal_date_format')) }}
                </flux:text>
            </div>

            {{-- Mood row — single horizontal scroll on narrow screens --}}
            <div
                class="-mx-1 flex gap-5 overflow-x-auto pb-4 pt-3 sm:flex-wrap sm:justify-center md:gap-7"
                role="group"
                aria-label="{{ __('patient.mood_tracker_mood_row_aria') }}"
            >
                @foreach ($moodKeys as $key)
                    @php
                        $img = $this->moodImageUrl($key);
                        $isSel = $selectedMoodKey === $key;
                        $isDimmed = $selectedMoodKey !== null && !$isSel;
                    @endphp
                    <div class="flex shrink-0 flex-col items-center gap-3 px-2 first:ps-5 last:pe-5 sm:first:ps-2 sm:last:pe-2">
                        <button
                            type="button"
                            wire:click="setMood('{{ $key }}')"
                            wire:key="mood-btn-{{ $key }}"
                            aria-pressed="{{ $isSel ? 'true' : 'false' }}"
                            @class([
                                'flex scale-95 touch-manipulation flex-col items-center rounded-2xl text-[#10B981] transition-all duration-200 outline-none hover:bg-[#10B981]/5 focus-visible:ring-2 focus-visible:ring-[#10B981]',
                                'cursor-pointer',
                                '-m-px border-2 border-transparent p-2 opacity-55 sm:p-3' =>
                                    $selectedMoodKey === null,
                                '-m-0.5 scale-110 rounded-2xl border-2 border-[#10B981] bg-[#10B981]/8 p-2 opacity-100 shadow-md shadow-[#10B981]/25 sm:p-3' =>
                                    $isSel,
                                '-m-px scale-90 border border-transparent p-2 opacity-[0.38] sm:p-3' =>
                                    $isDimmed,
                            ])
                        >
                            @if ($img)
                                <img
                                    src="{{ $img }}"
                                    alt=""
                                    @class([
                                        'pointer-events-none size-14 object-contain sm:size-[4.25rem]',
                                        '[filter:saturate(1.05)_brightness(1.05)] drop-shadow-[0_2px_8px_rgba(21,101,192,0.35)]' =>
                                            $isSel,
                                        'grayscale-[30%]' => !$isSel,
                                    ])
                                    decoding="async"
                                    loading="lazy"
                                />
                            @else
                                <span
                                    @class([
                                        'pointer-events-none flex size-14 items-center justify-center rounded-2xl border border-[#10B981]/15 bg-[#ecfdf5]/90 text-[2rem] selection:bg-transparent sm:size-[4.25rem] sm:text-[2.375rem]',
                                        'border-[#10B981]/40 bg-[#10B981]/12 shadow-inner' => $isSel,
                                        'opacity-80 grayscale-[35%]' => !$isSel,
                                    ])
                                    aria-hidden="true"
                                >
                                    {{ $this->moodEmoji($key) }}
                                </span>
                            @endif
                        </button>
                        <span
                            @class([
                                'pointer-events-none text-center text-sm font-semibold leading-tight sm:text-[0.9375rem]',
                                'text-[#10B981]' => $selectedMoodKey === null,
                                'font-bold text-[#10B981] underline decoration-[#10B981]/35 underline-offset-4' =>
                                    $isSel,
                                'opacity-40' => $isDimmed,
                            ])
                        >
                            {{ __('patient.mood_selector_options.'.$key) }}
                        </span>
                    </div>
                @endforeach
            </div>

            <flux:error name="selectedMoodKey" class="my-6 text-center" />

            @if ($selectedMoodKey !== null && $selectedMoodKey !== '')
                {{-- Opens after tapping a mood: note, share, Save --}}
                <div class="patient-mood-detail-panel">
                    <div
                        class="mx-auto rounded-2xl border border-[#10B981]/15 bg-zinc-50/90 p-6 shadow-sm ring-1 ring-[#10B981]/5 dark:border-zinc-600 dark:bg-zinc-900/50 sm:p-8"
                        role="region"
                        aria-labelledby="patient-mood-detail-label"
                        id="patient-mood-detail-region"
                        tabindex="-1"
                    >
                        <p id="patient-mood-detail-label" class="sr-only">
                            {{ __('patient.mood_tracker_detail_region_label') }}
                        </p>

                        <div class="mx-auto grid max-w-3xl gap-8 lg:max-w-none lg:grid-cols-12 lg:gap-10">
                            <div class="lg:col-span-7">
                                <flux:textarea
                                    wire:model="moodNote"
                                    :label="__('patient.mood_tracker_note_label')"
                                    rows="5"
                                    resize="vertical"
                                    class="min-h-[7.5rem] border-[#10B981]/20 [&_textarea:focus]:border-[#10B981]/55 [&_textarea:focus]:ring-[#10B981]/25"
                                />
                                <flux:error name="moodNote" />
                            </div>

                            <div class="flex flex-col justify-start pt-1 lg:col-span-5">
                                <flux:checkbox
                                    wire:model="shareWithTherapist"
                                    class="[&_input]:accent-[#10B981]"
                                    :label="__('patient.mood_tracker_share_label')"
                                    :description="__('patient.mood_tracker_share_help')"
                                />
                            </div>
                        </div>

                        <div class="mt-10 flex justify-center sm:mt-12">
                            <flux:button
                                type="button"
                                variant="primary"
                                wire:click="saveMood"
                                wire:loading.attr="disabled"
                                class="min-w-[12rem] rounded-lg! px-14! py-4! font-semibold !bg-[#10B981] !text-white hover:!bg-[#059669] sm:min-w-[16rem]"
                            >
                                <span wire:loading.remove>{{ __('patient.mood_tracker_save') }}</span>
                                <span wire:loading>{{ __('patient.mood_tracker_saving') }}</span>
                            </flux:button>
                        </div>
                    </div>
                </div>
            @else
                <flux:text class="mt-8 text-center font-medium text-zinc-500 dark:text-zinc-400">
                    {{ __('patient.mood_tracker_pick_hint') }}
                </flux:text>
            @endif
        </div>
    </flux:modal>
</div>
