<x-layouts::patient>
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex items-center gap-3">
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
            class="mt-12 flex flex-col items-center pb-16 text-center sm:mt-16"
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
</x-layouts::patient>
