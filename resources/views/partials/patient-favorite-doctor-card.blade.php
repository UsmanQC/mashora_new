@props([
    /** @var array<string, mixed> $specialist */
    'specialist',
    /** @var int|string $likes */
    'likes',
    /** @var bool $likedByUser */
    'likedByUser' => true,
])

@php
    $id = $specialist['id'];
    $roleLabel = __('specialist_results.roles.'.$specialist['role_kind']);
    $doctorDbId = $specialist['doctor_database_id'] ?? null;
    $bookUrl = filled($doctorDbId)
        ? route('patient.book-appointments', ['doctor' => $doctorDbId])
        : route('patient.schedule.filter');
    $photoUrl = $specialist['photo_url'] ?? null;
    $visibleTags = array_slice((array) ($specialist['tags'] ?? []), 0, 3);
    $channels = (array) ($specialist['channels'] ?? []);
    $isOnline = (bool) ($specialist['is_online'] ?? false);
@endphp

<article
    class="group relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-[0_14px_30px_-22px_rgba(2,6,23,0.35)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_22px_40px_-24px_rgba(2,6,23,0.42)]"
    wire:key="favorite-doctor-{{ $id }}"
    data-test="patient-favorite-doctor-card"
>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#10B981] via-[#059669] to-[#34d399]"></div>

    <div class="p-5 pb-4">
        <div class="flex gap-4">
            <div
                class="relative shrink-0"
                x-data="{ imageError: false }"
            >
                <div class="rounded-full bg-gradient-to-br from-emerald-50 to-white p-1 shadow-sm ring-1 ring-emerald-100">
                    @if (filled($photoUrl))
                        <img
                            src="{{ $photoUrl }}"
                            alt=""
                            x-show="! imageError"
                            x-on:error="imageError = true"
                            class="size-16 rounded-full object-cover sm:size-[4.5rem]"
                        />
                        <flux:avatar
                            :name="$specialist['name']"
                            circle
                            class="size-16 sm:size-[4.5rem] [&_[data-slot=avatar]]:size-16 sm:[&_[data-slot=avatar]]:size-[4.5rem]"
                            x-cloak
                            x-show="imageError"
                        />
                    @else
                        <flux:avatar
                            :name="$specialist['name']"
                            circle
                            class="size-16 sm:size-[4.5rem] [&_[data-slot=avatar]]:size-16 sm:[&_[data-slot=avatar]]:size-[4.5rem]"
                        />
                    @endif
                </div>

                @if ($isOnline)
                    <span class="absolute -bottom-0.5 -end-0.5 flex size-4 items-center justify-center rounded-full border-2 border-white bg-[#10B981]">
                        <span class="size-2 animate-pulse rounded-full bg-white/90"></span>
                    </span>
                @endif
            </div>

            <div class="min-w-0 flex-1 pt-0.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <flux:heading size="lg" class="truncate text-zinc-900">{{ $specialist['name'] }}</flux:heading>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-[#047857]">
                                {{ $roleLabel }}
                            </span>

                            @if ($isOnline)
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#10B981]/10 px-2.5 py-1 text-[11px] font-semibold text-[#059669]">
                                    <span class="size-1.5 rounded-full bg-[#10B981]"></span>
                                    {{ __('patient.menu.favorites_online') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="toggleLike('{{ $id }}')"
                        wire:loading.attr="disabled"
                        wire:target="toggleLike('{{ $id }}')"
                        title="{{ __('patient.menu.favorites_remove') }}"
                        class="group/heart flex shrink-0 flex-col items-center gap-1 rounded-2xl border border-emerald-100/80 bg-gradient-to-b from-emerald-50/90 to-white px-2.5 py-2 text-[#10B981] shadow-sm transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/30 disabled:opacity-60"
                        aria-label="{{ __('patient.menu.favorites_remove') }}"
                        aria-pressed="true"
                    >
                        <span wire:loading.remove wire:target="toggleLike('{{ $id }}')">
                            <flux:icon name="heart" variant="solid" class="size-6 text-[#10B981] transition group-hover/heart:scale-110" />
                        </span>
                        <span wire:loading wire:target="toggleLike('{{ $id }}')">
                            <flux:icon name="arrow-path" variant="outline" class="size-6 animate-spin text-[#10B981]" />
                        </span>
                        <span class="text-[11px] font-bold tabular-nums text-zinc-600">{{ $likes }}</span>
                    </button>
                </div>

                @if ($visibleTags !== [])
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($visibleTags as $tag)
                            <span class="inline-flex max-w-[9rem] truncate rounded-full border border-zinc-200/80 bg-zinc-50 px-2.5 py-0.5 text-[10px] font-medium text-zinc-600">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr,minmax(0,8.5rem)]">
            <div class="grid grid-cols-3 gap-2">
                @foreach (['chat' => 'chat-bubble-left-right', 'video' => 'video-camera', 'voice' => 'microphone'] as $channel => $icon)
                    <div
                        @class([
                            'flex flex-col items-center justify-center gap-1 rounded-2xl px-2 py-3 text-center text-[10px] font-semibold sm:text-[11px]',
                            ($channels[$channel] ?? false)
                                ? 'border border-transparent bg-zinc-100 text-zinc-800 shadow-inner shadow-zinc-200/40'
                                : 'border border-zinc-200/70 bg-white text-zinc-400 line-through decoration-zinc-300',
                        ])
                    >
                        <flux:icon
                            name="{{ $icon }}"
                            variant="{{ ($channels[$channel] ?? false) ? 'mini' : 'outline' }}"
                            class="size-5 shrink-0"
                        />
                        {{ __('specialist_results.channel_'.$channel) }}
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col items-center justify-center rounded-2xl bg-gradient-to-br from-zinc-100 via-white to-emerald-50/40 px-4 py-3 text-center ring-1 ring-zinc-200/70">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('specialist_results.sar') }}</p>
                <p class="text-2xl font-extrabold tabular-nums leading-none text-zinc-900">{{ $specialist['price_sar'] }}</p>
                <p class="mt-1 text-[11px] font-medium text-zinc-500">
                    {{ __('specialist_results.duration_minutes', ['minutes' => $specialist['session_minutes']]) }}
                </p>
            </div>
        </div>
    </div>

    <div class="border-t border-zinc-100 bg-zinc-50/50 px-5 py-4">
        <a
            href="{{ $bookUrl }}"
            wire:navigate
            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#10B981] to-[#059669] px-5 py-3 text-sm font-bold text-white shadow-md shadow-emerald-900/15 transition hover:brightness-[0.98] active:scale-[0.99]"
        >
            <flux:icon name="calendar-days" variant="mini" class="size-4" />
            {{ __('patient.menu.favorites_book') }}
            <flux:icon name="arrow-right" variant="mini" class="size-4 rtl:rotate-180" />
        </a>
    </div>
</article>
