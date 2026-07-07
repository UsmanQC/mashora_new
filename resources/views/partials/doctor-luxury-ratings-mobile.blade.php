<div
    class="doctor-luxury-ratings relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-ratings"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.menu') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.auth.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-xl font-bold tracking-tight text-slate-900">
                {{ __('doctor.ratings.title') }}
            </h1>
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col overflow-y-auto overscroll-contain px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-1">
        <p class="mb-4 text-sm leading-relaxed text-slate-500">
            {{ __('doctor.ratings.subtitle') }}
        </p>

        <div class="rounded-3xl border border-slate-100 bg-white px-6 py-14 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-[#10B981]/10 text-[#047857]">
                <flux:icon name="star" variant="outline" class="size-7" />
            </div>
            <p class="text-sm leading-relaxed text-slate-500">{{ __('doctor.ratings.empty') }}</p>
        </div>
    </main>
</div>
