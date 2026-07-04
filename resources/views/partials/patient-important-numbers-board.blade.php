@php
    use App\Support\ImportantNumbers;

    $entries = ImportantNumbers::entries();
    $boardImage = asset('images/important-numbers.svg');
@endphp

<div
    class="space-y-4"
    x-data="{
        chartOpen: false,
        chartScale: 1,
        openChart() {
            this.chartScale = 1;
            this.chartOpen = true;
            document.documentElement.classList.add('overflow-hidden');
        },
        closeChart() {
            this.chartOpen = false;
            document.documentElement.classList.remove('overflow-hidden');
        },
        zoomIn() {
            this.chartScale = Math.min(this.chartScale + 0.25, 3);
        },
        zoomOut() {
            this.chartScale = Math.max(this.chartScale - 0.25, 1);
        },
    }"
    data-test="patient-important-numbers-board"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:text class="text-sm text-zinc-600">
            {{ __('patient.numbers_tap_hint') }}
        </flux:text>
        <flux:button
            type="button"
            variant="ghost"
            size="sm"
            icon="arrows-pointing-out"
            class="shrink-0 text-[#059669]"
            x-on:click="openChart()"
            data-test="patient-important-numbers-zoom-open"
        >
            {{ __('patient.numbers_zoom_chart') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 sm:gap-4" data-test="patient-important-numbers-list">
        @foreach ($entries as $entry)
            <a
                href="{{ $entry['tel_href'] }}"
                class="group flex items-center gap-3 rounded-2xl border border-[#10B981]/25 bg-white p-4 shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] transition hover:border-[#10B981]/50 hover:bg-emerald-50/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/30 sm:rounded-3xl sm:p-5"
                data-test="patient-important-number-{{ $entry['id'] }}"
            >
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-[#059669] transition group-hover:bg-[#10B981] group-hover:text-white">
                    <flux:icon name="phone" variant="outline" class="size-5" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold leading-snug text-zinc-900 group-hover:text-[#047857]">
                        {{ $entry['label'] }}
                    </span>
                    <span
                        class="mt-1 block font-mono text-lg font-bold tracking-wide text-[#10B981] sm:text-xl"
                        dir="ltr"
                    >
                        {{ $entry['phone'] }}
                    </span>
                    <span class="mt-0.5 block text-xs text-zinc-500">
                        {{ __('patient.numbers_tap_to_call') }}
                    </span>
                </span>

                <flux:icon
                    name="chevron-right"
                    variant="outline"
                    class="size-5 shrink-0 text-zinc-300 transition group-hover:text-[#10B981] rtl:rotate-180"
                />
            </a>
        @endforeach
    </div>

    <div
        x-cloak
        x-show="chartOpen"
        x-transition.opacity
        class="fixed inset-0 z-[80] flex flex-col bg-black/90"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('patient.numbers_zoom_dialog_label') }}"
        data-test="patient-important-numbers-zoom-dialog"
        x-on:keydown.escape.window="closeChart()"
    >
        <div class="flex shrink-0 items-center justify-between gap-3 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
            <flux:heading size="sm" class="font-semibold text-white">
                {{ __('patient.numbers_zoom_dialog_label') }}
            </flux:heading>
            <div class="flex items-center gap-2">
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="minus"
                    class="!text-white hover:!bg-white/10"
                    x-on:click="zoomOut()"
                    data-test="patient-important-numbers-zoom-out"
                />
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="plus"
                    class="!text-white hover:!bg-white/10"
                    x-on:click="zoomIn()"
                    data-test="patient-important-numbers-zoom-in"
                />
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="x-mark"
                    class="!text-white hover:!bg-white/10"
                    x-on:click="closeChart()"
                    data-test="patient-important-numbers-zoom-close"
                />
            </div>
        </div>

        <div
            class="min-h-0 flex-1 overflow-auto overscroll-contain px-4 pb-[max(1rem,env(safe-area-inset-bottom))]"
            x-on:click.self="closeChart()"
        >
            <div class="mx-auto flex min-h-full w-full max-w-5xl items-center justify-center">
                <img
                    src="{{ $boardImage }}"
                    alt="{{ __('patient.numbers_board_alt') }}"
                    class="h-auto max-w-none origin-center transition-transform duration-200"
                    :style="`transform: scale(${chartScale}); width: ${chartScale > 1 ? chartScale * 100 : 100}%;`"
                    width="1200"
                    height="800"
                    decoding="async"
                    data-test="patient-important-numbers-zoom-image"
                />
            </div>
        </div>

        <p class="shrink-0 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] text-center text-xs text-white/70">
            {{ __('patient.numbers_zoom_hint') }}
        </p>
    </div>
</div>
