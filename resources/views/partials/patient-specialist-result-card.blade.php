@props([
    /** @var array<string, mixed> $specialist */
    'specialist',
    /** @var int|string $likes */
    'likes',
    /** @var string $selectedDate */
    'selectedDate' => now()->timezone(config('app.timezone'))->toDateString(),
    /** @var list<string> $availableSlots */
    'availableSlots' => [],
    /** @var string $displayTimezone */
    'displayTimezone' => config('app.timezone'),
])

@php
    $id = $specialist['id'];
    $roleKind = $specialist['role_kind'];
    $roleLabel = __('specialist_results.roles.'.$roleKind);
    $badgeColor = $roleKind === 'therapist' ? 'lime' : 'sky';
@endphp

<article
    class="group relative flex flex-col overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-[0_14px_30px_-22px_rgba(2,6,23,0.4)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_22px_40px_-24px_rgba(2,6,23,0.45)]"
    wire:key="specialist-card-{{ $id }}"
>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#3b63ff] via-[#5e7cff] to-[#9bb0ff] opacity-80"></div>

    <div class="flex gap-4">
        <div class="rounded-full ring-2 ring-[#3b63ff]/20 ring-offset-2 ring-offset-white">
            <flux:avatar :name="$specialist['name']" circle size="xl" />
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <flux:heading size="lg" class="truncate text-zinc-900">{{ $specialist['name'] }}</flux:heading>
                    <div class="mt-2 inline-flex flex-wrap gap-2">
                        <flux:badge size="sm" color="{{ $badgeColor }}" variant="pill">
                            {{ $roleLabel }}
                        </flux:badge>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col items-center gap-0.5 rounded-xl bg-zinc-50/80 px-2 py-1.5 text-center">
                    <button
                        type="button"
                        wire:click="incrementLike('{{ $id }}')"
                        title="{{ __('specialist_results.like_incremented') }}"
                        class="rounded-lg text-[#10B981] transition hover:scale-105 hover:bg-emerald-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#064e3b]/30"
                        aria-label="{{ __('specialist_results.like_incremented') }}"
                    >
                        <flux:icon name="heart" variant="outline" class="size-7" />
                    </button>
                    <flux:text class="text-xs font-semibold tabular-nums text-zinc-600">{{ $likes }}</flux:text>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr,minmax(0,9rem)] sm:items-stretch">
        <div class="grid grid-cols-3 gap-2">
            @foreach (['chat' => 'chat-bubble-left-right', 'video' => 'video-camera', 'voice' => 'microphone'] as $ch => $icon)
                <div
                    @class([
                        'flex aspect-square max-h-[4.75rem] flex-col items-center justify-center gap-1 rounded-2xl px-2 text-center text-[0.6875rem] font-medium transition sm:text-xs',
                        $specialist['channels'][$ch] ?? false
                            ? 'border border-transparent bg-zinc-100 text-zinc-900 shadow-inner shadow-zinc-200/50'
                            : 'border border-zinc-200/80 bg-white text-zinc-400 line-through decoration-zinc-300',
                    ])
                >
                    <flux:icon name="{{ $icon }}" variant="{{ ($specialist['channels'][$ch] ?? false) ? 'mini' : 'outline' }}" class="size-6 shrink-0" />
                    {{ __('specialist_results.channel_'.$ch) }}
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-zinc-100 to-zinc-50 px-4 py-4 text-center ring-1 ring-zinc-200/80 sm:flex sm:flex-col sm:justify-center">
            <div class="text-3xl font-extrabold tabular-nums text-zinc-900">{{ $specialist['price_sar'] }}</div>
            <flux:text class="mt-1 text-xs text-zinc-600">
                {{ __('specialist_results.price_suffix_per', ['currency' => __('specialist_results.sar'), 'minutes' => $specialist['session_minutes']]) }}
            </flux:text>
        </div>
    </div>

    <div class="mt-5">
        <flux:text class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-400">
            {{ __('specialist_results.available_times') }}
        </flux:text>
        
        @if (count($availableSlots) > 0)
            <div class="flex gap-2 overflow-x-auto pb-2" role="group">
                @foreach ($availableSlots as $slot)
                @php
                    $slotFormatted = \Illuminate\Support\Carbon::createFromFormat('H:i', $slot, $displayTimezone)->timezone($displayTimezone);
                    $slotLabel = $slotFormatted->locale(app()->getLocale())->translatedFormat('g:i a');
                    $doctorDbId = $specialist['doctor_database_id'] ?? null;
                    $bookHref = $doctorDbId
                        ? route('patient.book-appointments', ['doctor' => $doctorDbId], false)
                            .'?'.http_build_query([
                                'date' => $selectedDate,
                                'duration' => $specialist['session_minutes'],
                                'time' => $slot,
                            ])
                        : null;
                @endphp
                @if ($bookHref)
                    <a
                        href="{{ $bookHref }}"
                        wire:navigate
                        class="inline-flex shrink-0 items-center justify-center rounded-full border border-[#10B981]/45 bg-white px-3 py-2 text-[0.8rem] font-semibold tabular-nums text-[#064e3b] shadow-sm transition hover:-translate-y-0.5 hover:border-[#064e3b] hover:bg-emerald-600/10"
                    >
                        {{ $slotLabel }}
                    </a>
                @else
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        wire:click="pickSlot('{{ $id }}', '{{ $slot }}')"
                        class="shrink-0 rounded-full border border-[#10B981]/45 bg-white px-3 py-2 text-[0.8rem] font-semibold tabular-nums text-[#064e3b] shadow-sm hover:-translate-y-0.5 hover:border-[#064e3b] hover:bg-emerald-600/10"
                    >
                        {{ $slotLabel }}
                    </flux:button>
                @endif
                @endforeach
            </div>
        @else
            <flux:text class="text-xs text-zinc-500">
                {{ __('specialist_results.no_slots_for_selected_day') }}
            </flux:text>
        @endif
    </div>

    @php
        $visibleTags = array_slice($specialist['tags'], 0, 6);
        $hiddenTags = array_slice($specialist['tags'], 6);
    @endphp
    <div class="mt-4 border-t border-zinc-100 pt-4" x-data="{ showAllTags: false }">
        <div class="flex flex-wrap gap-2">
            @foreach ($visibleTags as $tag)
                <flux:badge variant="pill" color="zinc" class="!rounded-full !border !border-zinc-200 !bg-zinc-100/85 !px-3 !py-1 !text-[0.78rem] !font-medium">{{ $tag }}</flux:badge>
            @endforeach

            @foreach ($hiddenTags as $tag)
                <flux:badge x-cloak x-show="showAllTags" variant="pill" color="zinc" class="!rounded-full !border !border-zinc-200 !bg-zinc-100/85 !px-3 !py-1 !text-[0.78rem] !font-medium">{{ $tag }}</flux:badge>
            @endforeach
        </div>

        @if (count($hiddenTags) > 0)
            <button
                type="button"
                class="mt-3 inline-flex items-center text-sm font-semibold text-[#10B981] transition hover:text-[#064e3b]"
                @click="showAllTags = ! showAllTags"
            >
                <span x-show="! showAllTags">{{ __('specialist_results.more') }}</span>
                <span x-cloak x-show="showAllTags">{{ __('specialist_results.less') }}</span>
            </button>
        @endif
    </div>
</article>
