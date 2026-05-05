<x-layouts::patient>
    @php
        $bookHref = auth()->check()
            ? route('patient.schedule.filter')
            : route('login');

        $tabActive =
            'rounded-lg border border-[#1565c0] bg-[#1565c0] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/40';
        $tabInactive =
            'rounded-lg border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#1565c0] shadow-sm transition outline-none hover:border-[#1565c0]/35 focus-visible:ring-2 focus-visible:ring-[#1565c0]/30';
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-6 pb-28 sm:px-6 sm:pb-10" x-data="{ tab: 'ongoing' }">
        <header class="flex items-center gap-3">
            <a
                href="{{ route('patient.home') }}"
                wire:navigate
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-200/90 bg-white text-[#1565c0] shadow-sm transition hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30"
                aria-label="{{ __('patient.appointments.back_aria') }}"
            >
                <flux:icon name="chevron-left" variant="outline" class="size-6 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 truncate text-xl font-bold text-[#1565c0] sm:text-2xl">
                {{ __('patient.appointments.title') }}
            </h1>
        </header>

        <div class="mt-8 flex flex-col items-stretch gap-3 sm:mt-10 sm:flex-row sm:flex-wrap sm:items-center sm:justify-center">
            <div
                class="flex w-full justify-center gap-2 sm:w-auto sm:max-w-full sm:flex-wrap"
                role="tablist"
                aria-label="{{ __('patient.appointments.tabs_aria') }}"
            >
                <button
                    type="button"
                    role="tab"
                    id="patient-appt-tab-ongoing"
                    :aria-selected="tab === 'ongoing'"
                    @@click="tab = 'ongoing'"
                    class="min-h-11 shrink-0"
                    :class="tab === 'ongoing' ? '{{ $tabActive }}' : '{{ $tabInactive }}'"
                >
                    {{ __('patient.appointments.tab_ongoing') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    id="patient-appt-tab-completed"
                    :aria-selected="tab === 'completed'"
                    @@click="tab = 'completed'"
                    class="min-h-11 shrink-0"
                    :class="tab === 'completed' ? '{{ $tabActive }}' : '{{ $tabInactive }}'"
                >
                    {{ __('patient.appointments.tab_completed') }}
                </button>
            </div>

            <a
                href="{{ $bookHref }}"
                wire:navigate
                role="button"
                class="{{ $tabInactive }} inline-flex min-h-11 w-full items-center justify-center text-center no-underline sm:w-auto sm:min-w-0"
            >
                {{ __('patient.appointments.book_new') }}
            </a>
        </div>

        <section
            class="mt-10 flex flex-col items-center pb-8 text-center sm:mt-12"
            role="tabpanel"
            :aria-labelledby="tab === 'ongoing' ? 'patient-appt-tab-ongoing' : 'patient-appt-tab-completed'"
            aria-live="polite"
        >
            @include('partials.patient-empty-record-illustration')
            <p class="mt-8 text-base font-medium text-zinc-400 sm:text-lg">
                {{ __('patient.menu.no_record_found') }}
            </p>
        </section>
    </div>
</x-layouts::patient>
