<x-layouts::patient>
@php
    $user = auth()->user();
    $profilePhotoUrl = $user !== null && filled($user->profile_photo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $user->profile_photo_path)
        : null;
@endphp

<div class="patient-luxury-favorites bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-favorites">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.menu.favorites'),
            'subtitle' => __('patient.menu.favorites_sub'),
            'profilePhotoUrl' => $profilePhotoUrl,
            'userName' => $user?->name,
            'backUrl' => route('patient.menu'),
            'backLabel' => __('patient.nav.menu'),
            'testId' => 'patient-favorites-header',
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
                    {{ __('patient.menu.favorites') }}
                </flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </header>

        <section
            class="flex flex-col items-center rounded-3xl border border-slate-100/80 bg-white px-6 py-14 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:mt-12 sm:rounded-none sm:border-0 sm:bg-transparent sm:py-0 sm:shadow-none sm:pb-16"
            aria-labelledby="patient-favorites-empty-heading"
        >
            <div class="mx-auto mb-2 flex size-16 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                <flux:icon name="heart" variant="outline" class="size-8" />
            </div>

            <flux:heading id="patient-favorites-empty-heading" level="2" size="lg" class="font-semibold text-slate-900">
                {{ __('patient.menu.favorites_empty_title') }}
            </flux:heading>

            <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-500">
                {{ __('patient.menu.favorites_empty_hint') }}
            </p>

            <a
                href="{{ route('patient.schedule.specialists') }}"
                wire:navigate
                class="mt-6 inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-[#10B981] px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-emerald-900/15 transition hover:bg-[#059669]"
            >
                <flux:icon name="magnifying-glass" variant="mini" class="size-4" />
                {{ __('patient.menu.favorites_browse') }}
            </a>
        </section>
    </div>
</div>
</x-layouts::patient>
