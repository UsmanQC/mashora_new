@props([
    'title',
    'subtitle' => null,
    'profileUrl' => null,
    'profilePhotoUrl' => null,
    'userName' => null,
    'testId' => null,
    'progressStep' => null,
    'progressTotal' => null,
    'backUrl' => null,
    'backLabel' => null,
])

@php
    $profileHref = $profileUrl ?? (auth()->check() ? route('profile.edit') : route('patient.phone'));
    $resolvedUserName = $userName ?? auth()->user()?->name ?? '';
@endphp

<header
    class="patient-luxury-page-header sticky top-0 z-40 border-b border-slate-200/50 bg-slate-50/80 px-6 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))] backdrop-blur-xl"
    @if ($testId) data-test="{{ $testId }}" @endif
>
    <div class="flex items-center justify-between gap-4">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            @if (filled($backUrl))
                <a
                    href="{{ $backUrl }}"
                    wire:navigate
                    class="active-scale inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                    aria-label="{{ $backLabel ?? __('patient.appointments.back_aria') }}"
                >
                    <flux:icon name="chevron-left" variant="outline" class="size-5 rtl:rotate-180" />
                </a>
            @endif
            <div class="min-w-0 flex-1">
                <h1
                    class="truncate text-2xl font-bold tracking-tight text-slate-900"
                    @if ($testId) data-test="{{ $testId }}-title" @endif
                >{{ $title }}</h1>
                @if (filled($subtitle))
                    <p
                        class="mt-0.5 truncate text-xs font-medium text-slate-500"
                        @if ($testId) data-test="{{ $testId }}-subtitle" @endif
                    >{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @include('partials.patient-language-switch', ['variant' => 'luxury'])

            <a
                href="{{ $profileHref }}"
                wire:navigate
                class="patient-luxury-page-header__avatar active-scale shrink-0 overflow-hidden rounded-full bg-white shadow-sm ring-2 ring-white"
                aria-label="{{ __('patient.nav.my_account') }}"
                data-test="patient-luxury-page-header-avatar"
            >
                @if (filled($profilePhotoUrl))
                    <img src="{{ $profilePhotoUrl }}" alt="" class="size-10 object-cover" />
                @else
                    <flux:avatar :name="$resolvedUserName" circle class="size-10" />
                @endif
            </a>
        </div>
    </div>

    @if (is_int($progressStep) && is_int($progressTotal) && $progressTotal > 0)
        <div class="mt-4 flex items-center gap-1.5" aria-hidden="true">
            @for ($stepIndex = 1; $stepIndex <= $progressTotal; $stepIndex++)
                <span @class([
                    'h-1.5 flex-1 rounded-full transition-all duration-300',
                    'bg-[#10B981] shadow-sm shadow-[#10B981]/30' => $stepIndex <= $progressStep,
                    'bg-zinc-200/90' => $stepIndex > $progressStep,
                ])></span>
            @endfor
        </div>
    @endif
</header>
