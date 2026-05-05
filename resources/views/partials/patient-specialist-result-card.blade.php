@props([
    /** @var array<string, mixed> $specialist */
    'specialist',
    /** @var int|string $likes */
    'likes',
])

@php
    $id = $specialist['id'];
    $roleKind = $specialist['role_kind'];
    $roleLabel = __('specialist_results.roles.'.$roleKind);
    $badgeColor = $roleKind === 'therapist' ? 'lime' : 'sky';
@endphp

<article
    class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-md shadow-black/10"
    wire:key="specialist-card-{{ $id }}"
>
    <div class="flex gap-4">
        <flux:avatar :name="$specialist['name']" circle size="xl" />

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

                <div class="flex shrink-0 flex-col items-center gap-0.5 text-center">
                    <button
                        type="button"
                        wire:click="incrementLike('{{ $id }}')"
                        title="{{ __('specialist_results.like_incremented') }}"
                        class="rounded-lg text-[#1565c0] transition hover:bg-blue-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0B163E]/30"
                        aria-label="{{ __('specialist_results.like_incremented') }}"
                    >
                        <flux:icon name="heart" variant="outline" class="size-7" />
                    </button>
                    <flux:text class="text-xs font-semibold tabular-nums text-zinc-600">{{ $likes }}</flux:text>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4" x-data="{ open: false }">
        <p class="text-sm leading-relaxed text-zinc-700">
            <span x-bind:class="open ? '' : 'line-clamp-3'">{{ $specialist['bio'] }}</span>
            <button
                type="button"
                class="ms-1 inline text-[#1565c0] underline decoration-[#1565c0]/30 underline-offset-2 hover:text-[#0B163E]"
                @click.prevent="open = ! open"
            >
                <span x-show="! open">{{ __('specialist_results.more') }}</span>
                <span x-cloak x-show="open">{{ __('specialist_results.less') }}</span>
            </button>
        </p>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr,minmax(0,8.5rem)] sm:items-stretch">
        <div class="grid grid-cols-3 gap-2">
            @foreach (['chat' => 'chat-bubble-left-right', 'video' => 'video-camera', 'voice' => 'microphone'] as $ch => $icon)
                <div
                    @class([
                        'flex aspect-square max-h-[4.75rem] flex-col items-center justify-center gap-1 rounded-xl px-2 text-center text-[0.6875rem] font-medium sm:text-xs',
                        $specialist['channels'][$ch] ?? false ? 'border border-transparent bg-zinc-100 text-zinc-900' : 'border border-zinc-200/80 bg-white text-zinc-400 line-through decoration-zinc-300',
                    ])
                >
                    <flux:icon name="{{ $icon }}" variant="{{ ($specialist['channels'][$ch] ?? false) ? 'mini' : 'outline' }}" class="size-6 shrink-0" />
                    {{ __('specialist_results.channel_'.$ch) }}
                </div>
            @endforeach
        </div>

        <div class="rounded-xl bg-zinc-100 px-4 py-4 text-center sm:flex sm:flex-col sm:justify-center">
            <div class="text-2xl font-bold tabular-nums text-zinc-900">{{ $specialist['price_sar'] }}</div>
            <flux:text class="mt-1 text-xs text-zinc-600">
                {{ __('specialist_results.price_suffix_per', ['currency' => __('specialist_results.sar'), 'minutes' => $specialist['session_minutes']]) }}
            </flux:text>
        </div>
    </div>

    <div class="mt-5">
        <flux:text class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-400">
            {{ __('specialist_results.available_times') }}
        </flux:text>
        <div
            class="flex gap-2 overflow-x-auto pb-2"
            role="group"
        >
            @foreach ($specialist['slots'] as $slot)
                @php
                    $slotFormatted = \Illuminate\Support\Carbon::createFromFormat('H:i', $slot)->timezone(config('app.timezone'));
                    $slotLabel = $slotFormatted->locale(app()->getLocale())->translatedFormat('g:i a');
                    $doctorDbId = $specialist['doctor_database_id'] ?? null;
                    $bookHref = $doctorDbId
                        ? route('patient.book-appointments', ['doctor' => $doctorDbId], false)
                            .'?'.http_build_query([
                                'date' => now()->timezone(config('app.timezone'))->format('Y-m-d'),
                                'duration' => $specialist['session_minutes'],
                                'time' => $slot,
                            ])
                        : null;
                @endphp
                @if ($bookHref)
                    <a
                        href="{{ $bookHref }}"
                        wire:navigate
                        class="inline-flex shrink-0 items-center justify-center rounded-full border border-[#1565c0]/50 bg-white px-3 py-2 text-[0.8rem] font-semibold tabular-nums text-[#0B163E] shadow-sm transition hover:border-[#0B163E] hover:bg-blue-600/10"
                    >
                        {{ $slotLabel }}
                    </a>
                @else
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        wire:click="pickSlot('{{ $id }}', '{{ $slot }}')"
                        class="shrink-0 rounded-full border border-[#1565c0]/50 bg-white px-3 py-2 text-[0.8rem] font-semibold tabular-nums text-[#0B163E] shadow-sm hover:border-[#0B163E] hover:bg-blue-600/10"
                    >
                        {{ $slotLabel }}
                    </flux:button>
                @endif
            @endforeach
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2 border-t border-zinc-100 pt-4">
        @foreach ($specialist['tags'] as $tag)
            <flux:badge variant="pill" color="zinc">{{ $tag }}</flux:badge>
        @endforeach
    </div>
</article>
