@php
    $current = (int) ($current ?? 1);
    $total = max(1, (int) ($total ?? 6));
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
    $percent = (int) round(($current / $total) * 100);
@endphp

<header
    class="doctor-onboarding-header overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-[0_12px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-zinc-900/[0.04]"
    data-test="doctor-onboarding-header"
>
    <div class="border-b border-emerald-100/80 bg-gradient-to-br from-emerald-50 via-white to-white px-4 py-3.5 sm:px-5 sm:py-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-[#047857] sm:text-xs sm:tracking-wider">
                {{ __('doctor.auth.onboarding_progress', ['current' => $current, 'total' => $total]) }}
            </p>
            <span
                class="inline-flex shrink-0 items-center rounded-full bg-[#047857] px-2.5 py-1 text-[0.6875rem] font-bold tabular-nums text-white shadow-sm sm:text-xs"
                aria-hidden="true"
            >
                {{ $percent }}%
            </span>
        </div>

        <div
            class="mt-3 flex gap-1.5"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $percent }}"
            aria-label="{{ __('doctor.auth.onboarding_progress', ['current' => $current, 'total' => $total]) }}"
        >
            @for ($stepIndex = 1; $stepIndex <= $total; $stepIndex++)
                <span
                    @class([
                        'h-1.5 flex-1 rounded-full transition-colors duration-300',
                        'bg-[#047857]' => $stepIndex < $current,
                        'bg-[#10B981] ring-2 ring-[#10B981]/25' => $stepIndex === $current,
                        'bg-zinc-200' => $stepIndex > $current,
                    ])
                ></span>
            @endfor
        </div>
    </div>

    <div class="px-4 py-4 sm:px-5 sm:py-5">
        <h1 class="text-xl font-semibold tracking-tight text-zinc-900 sm:text-2xl">
            {{ $title }}
        </h1>
        @if (filled($subtitle))
            <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">
                {{ $subtitle }}
            </p>
        @endif
    </div>
</header>
