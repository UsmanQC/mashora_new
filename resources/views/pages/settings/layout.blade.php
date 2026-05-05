@props(['heading' => '', 'subheading' => ''])

<div class="mx-auto max-w-3xl pb-28 sm:pb-10">
    <div class="px-4 pt-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] sm:p-8"
        >
            @if ($heading !== '')
                <flux:heading>{{ $heading }}</flux:heading>
                <flux:subheading class="mt-1">{{ $subheading }}</flux:subheading>
            @endif

            <div @class(['w-full max-w-xl', $heading !== '' ? 'mt-6' : ''])>
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
