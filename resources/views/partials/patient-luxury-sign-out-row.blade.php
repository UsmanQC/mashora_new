@props([
    'testId' => 'patient-menu-sign-out',
])

<form method="POST" action="{{ route('logout') }}" class="w-full">
    @csrf
    <button
        type="submit"
        {{ $attributes->merge([
            'class' => 'patient-luxury-menu-row active-scale flex w-full items-center gap-4 rounded-2xl border border-red-100 bg-white p-4 text-start shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition hover:border-red-200 hover:bg-red-50/40',
            'data-test' => $testId,
            'aria-label' => __('patient.menu.sign_out'),
        ]) }}
    >
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
            <flux:icon name="arrow-right-start-on-rectangle" variant="outline" class="size-5" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-red-700">{{ __('patient.menu.sign_out') }}</span>
            <span class="mt-0.5 block text-xs text-red-600/80">{{ __('patient.menu.sign_out_sub') }}</span>
        </span>
    </button>
</form>
