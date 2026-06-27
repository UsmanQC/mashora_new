@props([
    'overlayId',
    'titleId',
    'leaveBtnId',
    'remoteId',
    'localId',
    'toggleMicId',
    'toggleVideoId',
    'title',
    'youLabel',
    'endCallLabel',
    'micLabel',
    'cameraLabel',
    'micMutedLabel' => null,
    'cameraOffLabel' => null,
    'durationId' => null,
    'durationLabel' => null,
])

<div
    id="{{ $overlayId }}"
    wire:ignore
    class="video-call-overlay fixed inset-0 z-[200] hidden"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
>
    <div class="absolute inset-0 bg-zinc-950/90 backdrop-blur-md" aria-hidden="true"></div>

    <div class="relative flex h-full min-h-0 flex-col items-center justify-center p-3 sm:p-6 lg:p-8">
        <div class="video-call-shell flex w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-zinc-900 text-white shadow-2xl shadow-black/60 ring-1 ring-white/10">
            <div class="flex shrink-0 items-center justify-between gap-4 border-b border-white/10 bg-gradient-to-r from-zinc-900 via-zinc-900 to-zinc-800/95 px-4 py-3.5 sm:px-6 sm:py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="relative flex size-3 shrink-0">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#10B981] opacity-50"></span>
                        <span class="relative inline-flex size-3 rounded-full bg-[#10B981] ring-2 ring-[#10B981]/30"></span>
                    </span>
                    <div class="min-w-0">
                        <p id="{{ $titleId }}" class="truncate text-base font-semibold tracking-tight sm:text-lg">{{ $title }}</p>
                        @if ($durationId && $durationLabel)
                            <p class="mt-0.5 text-xs font-medium text-zinc-400 sm:text-sm">
                                {{ $durationLabel }}:
                                <span id="{{ $durationId }}" class="font-mono tabular-nums text-zinc-200">00:00</span>
                            </p>
                        @endif
                    </div>
                </div>
                <button
                    type="button"
                    id="{{ $leaveBtnId }}"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-rose-900/35 transition hover:bg-rose-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                >
                    <flux:icon name="x-mark" variant="mini" class="size-4" />
                    {{ $endCallLabel }}
                </button>
            </div>

            <div class="relative bg-black">
                <div class="video-call-stage relative aspect-video w-full min-h-[min(52vh,720px)] bg-zinc-950">
                    <div id="{{ $remoteId }}" class="absolute inset-0 h-full w-full"></div>

                    <div class="absolute inset-x-0 bottom-0 z-10 flex justify-center px-4 pb-4 pt-8 sm:px-6 sm:pb-6">
                        <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-zinc-900/85 px-4 py-3 shadow-xl shadow-black/40 backdrop-blur-md sm:gap-4 sm:px-6 sm:py-4">
                            <button
                                type="button"
                                id="{{ $toggleMicId }}"
                                data-label-on="{{ $micLabel }}"
                                data-label-off="{{ $micMutedLabel ?? __('doctor.conversation.mic_muted') }}"
                                aria-pressed="false"
                                class="video-call-control group flex flex-col items-center gap-1.5 rounded-xl px-2 py-1 transition hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/60"
                                title="{{ $micLabel }}"
                            >
                                <span data-control-ring class="flex size-12 items-center justify-center rounded-full border border-white/15 bg-zinc-800/90 text-white transition group-hover:border-[#10B981]/40 group-hover:bg-zinc-700/90 sm:size-14">
                                    <flux:icon name="microphone" variant="mini" class="size-5 sm:size-6" />
                                </span>
                                <span data-control-label class="text-[10px] font-medium text-zinc-300 sm:text-xs">{{ $micLabel }}</span>
                            </button>
                            <button
                                type="button"
                                id="{{ $toggleVideoId }}"
                                data-label-on="{{ $cameraLabel }}"
                                data-label-off="{{ $cameraOffLabel ?? __('doctor.conversation.camera_off') }}"
                                aria-pressed="false"
                                class="video-call-control group flex flex-col items-center gap-1.5 rounded-xl px-2 py-1 transition hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/60"
                                title="{{ $cameraLabel }}"
                            >
                                <span data-control-ring class="flex size-12 items-center justify-center rounded-full border border-white/15 bg-zinc-800/90 text-white transition group-hover:border-[#10B981]/40 group-hover:bg-zinc-700/90 sm:size-14">
                                    <flux:icon name="video-camera" variant="mini" class="size-5 sm:size-6" />
                                </span>
                                <span data-control-label class="text-[10px] font-medium text-zinc-300 sm:text-xs">{{ $cameraLabel }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="absolute end-4 top-4 z-10 w-36 overflow-hidden rounded-2xl border border-white/20 bg-zinc-900/90 shadow-2xl shadow-black/50 ring-1 ring-white/10 sm:end-6 sm:top-6 sm:w-44 lg:w-52">
                        <div id="{{ $localId }}" class="aspect-video w-full bg-zinc-800"></div>
                        <p class="px-3 py-1.5 text-center text-[11px] font-semibold tracking-wide text-zinc-300">{{ $youLabel }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
