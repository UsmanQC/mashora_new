<x-layouts::patient>
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-8 pb-28 sm:px-6 sm:pb-10 lg:px-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('patient.nav.menu') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600">{{ __('patient.menu.page_subtitle') }}</flux:text>
            </div>
            <flux:button
                :href="route('patient.home')"
                wire:navigate
                variant="ghost"
                size="sm"
                icon="arrow-left"
                :aria-label="__('patient.appointments.back_aria')"
            />
        </div>

        @auth
            <div class="rounded-2xl border border-[#10B981]/20 bg-gradient-to-r from-[#10B981]/8 via-white to-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <flux:avatar :name="auth()->user()->name" circle size="xl" class="shrink-0 ring-2 ring-[#10B981]/15" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-lg font-semibold text-zinc-900">{{ auth()->user()->name }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500">
                            @if (filled(auth()->user()->email))
                                <span class="truncate">{{ auth()->user()->email }}</span>
                            @endif
                            @if (filled(auth()->user()->phone))
                                <span class="truncate" dir="ltr">{{ auth()->user()->phone }}</span>
                            @endif
                        </div>
                    </div>
                    <flux:button
                        :href="route('profile.edit')"
                        wire:navigate
                        variant="primary"
                        size="sm"
                        icon="cog-6-tooth"
                        class="w-full shrink-0 !bg-[#10B981] !text-white hover:!brightness-95 sm:w-auto"
                    >
                        {{ __('patient.menu.account_settings') }}
                    </flux:button>
                </div>
            </div>
        @endauth

        @include('partials.patient-menu-sections')
    </div>
</x-layouts::patient>
