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
    class="video-call-overlay fixed inset-0 z-[200] hidden h-dvh w-dvw"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
>
    <div class="absolute inset-0 bg-zinc-950/95 backdrop-blur-md sm:bg-zinc-950/90" aria-hidden="true"></div>

    <div class="relative flex h-dvh min-h-0 w-full flex-col sm:h-full sm:items-center sm:justify-center sm:p-6 lg:p-8">
        <div class="video-call-shell flex h-full min-h-0 w-full max-w-none flex-col overflow-hidden bg-zinc-900 text-white sm:h-auto sm:max-w-6xl sm:rounded-3xl sm:border sm:border-white/10 sm:shadow-2xl sm:shadow-black/60 sm:ring-1 sm:ring-white/10">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 bg-gradient-to-r from-zinc-900 via-zinc-900 to-zinc-800/95 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="relative flex size-3 shrink-0">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#10B981] opacity-50"></span>
                        <span class="relative inline-flex size-3 rounded-full bg-[#10B981] ring-2 ring-[#10B981]/30"></span>
                    </span>
                    <div class="min-w-0">
                        <p id="{{ $titleId }}" class="truncate text-sm font-semibold tracking-tight sm:text-lg">{{ $title }}</p>
                        @if ($durationId && $durationLabel)
                            <p class="mt-0.5 text-[11px] font-medium text-zinc-400 sm:text-sm">
                                {{ $durationLabel }}:
                                <span id="{{ $durationId }}" class="font-mono tabular-nums text-zinc-200">00:00</span>
                            </p>
                        @endif
                    </div>
                </div>
                <button
                    type="button"
                    data-video-call-leave="{{ $leaveBtnId }}"
                    class="video-call-leave-header inline-flex shrink-0 items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-lg shadow-rose-900/35 transition hover:bg-rose-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 sm:hidden"
                >
                    <flux:icon name="x-mark" variant="mini" class="size-4" />
                    {{ $endCallLabel }}
                </button>
            </div>

            <div class="relative flex min-h-0 flex-1 flex-col bg-black">
                <div class="video-call-stage relative min-h-0 w-full flex-1 bg-zinc-950 sm:aspect-video sm:min-h-[min(52vh,720px)] sm:flex-none">
                    <div id="{{ $remoteId }}" class="video-call-remote absolute inset-0 z-0 h-full w-full"></div>

                    <div class="video-call-local-preview absolute end-3 top-3 z-10 w-28 overflow-hidden rounded-2xl border border-white/20 bg-zinc-900/90 shadow-2xl shadow-black/50 ring-1 ring-white/10 sm:end-6 sm:top-6 sm:w-44 lg:w-52">
                        <div id="{{ $localId }}" class="aspect-video w-full bg-zinc-800"></div>
                        <p class="px-2 py-1 text-center text-[10px] font-semibold tracking-wide text-zinc-300 sm:px-3 sm:py-1.5 sm:text-[11px]">{{ $youLabel }}</p>
                    </div>

                    <div class="video-call-controls pointer-events-none absolute inset-x-0 bottom-0 z-20 flex justify-center px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-16 sm:px-6 sm:pb-6 sm:pt-10">
                        <div class="pointer-events-auto flex items-end gap-2 rounded-2xl border border-white/10 bg-zinc-900/90 px-3 py-2.5 shadow-xl shadow-black/40 backdrop-blur-md sm:gap-4 sm:px-6 sm:py-4">
                            <button
                                type="button"
                                id="{{ $toggleMicId }}"
                                data-label-on="{{ $micLabel }}"
                                data-label-off="{{ $micMutedLabel ?? __('doctor.conversation.mic_muted') }}"
                                aria-pressed="false"
                                disabled
                                class="video-call-control group hidden flex flex-col items-center gap-1 rounded-xl px-1.5 py-1 transition hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/60 disabled:cursor-not-allowed disabled:opacity-50 sm:gap-1.5 sm:px-2"
                                title="{{ $micLabel }}"
                            >
                                <span data-control-ring class="flex size-11 items-center justify-center rounded-full border border-white/15 bg-zinc-800/90 text-white transition group-hover:border-[#10B981]/40 group-hover:bg-zinc-700/90 sm:size-14">
                                    <flux:icon name="microphone" variant="mini" class="size-5 sm:size-6" />
                                </span>
                                <span data-control-label class="hidden text-[10px] font-medium text-zinc-300 sm:block sm:text-xs">{{ $micLabel }}</span>
                            </button>
                            <button
                                type="button"
                                id="{{ $leaveBtnId }}"
                                class="video-call-leave-main inline-flex size-14 shrink-0 items-center justify-center rounded-full bg-rose-600 text-white shadow-lg shadow-rose-900/40 transition hover:bg-rose-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900 sm:hidden"
                                aria-label="{{ $endCallLabel }}"
                            >
                                <flux:icon name="phone-x-mark" variant="mini" class="size-6" />
                            </button>
                            <button
                                type="button"
                                id="{{ $toggleVideoId }}"
                                data-label-on="{{ $cameraLabel }}"
                                data-label-off="{{ $cameraOffLabel ?? __('doctor.conversation.camera_off') }}"
                                aria-pressed="false"
                                disabled
                                class="video-call-control group hidden flex flex-col items-center gap-1 rounded-xl px-1.5 py-1 transition hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/60 disabled:cursor-not-allowed disabled:opacity-50 sm:gap-1.5 sm:px-2"
                                title="{{ $cameraLabel }}"
                            >
                                <span data-control-ring class="flex size-11 items-center justify-center rounded-full border border-white/15 bg-zinc-800/90 text-white transition group-hover:border-[#10B981]/40 group-hover:bg-zinc-700/90 sm:size-14">
                                    <flux:icon name="video-camera" variant="mini" class="size-5 sm:size-6" />
                                </span>
                                <span data-control-label class="hidden text-[10px] font-medium text-zinc-300 sm:block sm:text-xs">{{ $cameraLabel }}</span>
                            </button>
                            <button
                                type="button"
                                data-video-call-leave="{{ $leaveBtnId }}"
                                class="video-call-leave-desktop hidden items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-rose-900/35 transition hover:bg-rose-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 sm:inline-flex"
                            >
                                <flux:icon name="x-mark" variant="mini" class="size-4" />
                                {{ $endCallLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
