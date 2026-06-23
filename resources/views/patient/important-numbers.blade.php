<x-layouts::patient>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 pb-28 sm:px-6 sm:pb-10">
        <div>
            <flux:heading size="lg">{{ __('patient.nav.important_numbers') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-600">{{ __('patient.numbers_intro') }}</flux:text>
        </div>

        <figure class="overflow-hidden rounded-2xl border border-[#10B981]/25 bg-white p-4 shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] sm:p-6">
            <img
                src="{{ asset('images/important-numbers.svg') }}"
                alt="{{ __('patient.numbers_board_alt') }}"
                class="mx-auto block h-auto w-full max-w-4xl"
                width="1200"
                height="800"
                decoding="async"
                fetchpriority="high"
            />
            <figcaption class="sr-only">{{ __('patient.numbers_board_alt') }}</figcaption>
        </figure>
    </div>
</x-layouts::patient>
