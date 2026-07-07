<div
    class="doctor-luxury-privacy-policy relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-privacy-policy"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.menu') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('Back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-xl font-bold tracking-tight text-slate-900">
                {{ __('Privacy policy') }}
            </h1>
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col overflow-y-auto overscroll-contain px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-1">
        <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            @if ($this->policy)
                <h2 class="mb-3 text-base font-bold text-slate-900">{{ $this->policy->title }}</h2>
                <div class="prose prose-sm max-w-none text-slate-700 prose-headings:text-slate-900 prose-a:text-[#047857]">
                    {!! $this->policy->content_local !!}
                </div>
            @else
                <div class="px-2 py-10 text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <flux:icon name="shield-check" variant="outline" class="size-7" />
                    </div>
                    <p class="text-sm leading-relaxed text-slate-500">
                        {{ __('Privacy policy content is not available yet.') }}
                    </p>
                </div>
            @endif
        </div>
    </main>
</div>
