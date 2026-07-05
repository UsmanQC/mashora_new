<x-layouts::patient>
@php
    $user = auth()->user();
    $profilePhotoUrl = $user !== null && filled($user->profile_photo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $user->profile_photo_path)
        : null;
    $subtitleKey = $subtitleKey ?? (
        str_starts_with($titleKey, 'patient.menu.')
            ? $titleKey.'_sub'
            : null
    );
    $pageSlug = str_replace('.', '-', str_replace('patient.menu.', '', $titleKey));
@endphp

<div class="patient-luxury-section-empty bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-section-empty-{{ $pageSlug }}">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __($titleKey),
            'subtitle' => filled($subtitleKey) ? __($subtitleKey) : null,
            'profilePhotoUrl' => $profilePhotoUrl,
            'userName' => $user?->name,
            'backUrl' => route('patient.menu'),
            'backLabel' => __('patient.nav.menu'),
            'testId' => 'patient-section-empty-header-'.$pageSlug,
        ])
    </div>

    <div class="mx-auto max-w-3xl px-6 pt-5 sm:px-6 sm:py-6 lg:px-8">
        <header class="hidden items-center gap-3 sm:flex">
            <a
                href="{{ route('patient.menu') }}"
                wire:navigate
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-200/90 bg-white text-[#10B981] shadow-sm transition hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/30"
                aria-label="{{ __('patient.empty_state.back_aria') }}"
            >
                <flux:icon name="chevron-left" variant="outline" class="size-6 rtl:rotate-180" />
            </a>

            <flux:breadcrumbs class="min-w-0 flex-wrap">
                <flux:breadcrumbs.item
                    href="{{ route('patient.menu') }}"
                    separator="slash"
                    class="[&_a]:!text-[#10B981] [&_a]:decoration-[#10B981]/25 [&_a]:hover:!text-[#059669]"
                    wire:navigate
                >
                    {{ __('patient.empty_state.menu_crumb') }}
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item>
                    {{ __($titleKey) }}
                </flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </header>

        <section
            class="flex flex-col items-center rounded-3xl border border-slate-100/80 bg-white px-6 py-14 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:mt-12 sm:rounded-none sm:border-0 sm:bg-transparent sm:py-0 sm:shadow-none sm:pb-16"
            aria-labelledby="patient-empty-record-heading"
        >
            @include('partials.patient-empty-record-illustration')

            <flux:heading id="patient-empty-record-heading" level="2" size="lg" class="sr-only">
                {{ __($titleKey) }}
            </flux:heading>

            <p class="mt-8 text-base font-medium text-zinc-400 sm:text-lg">
                {{ __('patient.menu.no_record_found') }}
            </p>
        </section>
    </div>
</div>
</x-layouts::patient>
