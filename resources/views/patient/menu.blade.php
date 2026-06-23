@php
    $menuSections = [
        [
            'heading' => __('patient.sidebar.group_account'),
            'items' => [
                [
                    'href' => route('patient.notifications'),
                    'icon' => 'bell',
                    'label' => __('patient.menu.notifications'),
                    'sub' => __('patient.menu.notifications_sub'),
                ],
                [
                    'href' => route('patient.wallet'),
                    'icon' => 'banknotes',
                    'label' => __('patient.menu.wallet'),
                    'sub' => __('patient.menu.wallet_sub'),
                ],
                [
                    'href' => route('profile.edit'),
                    'icon' => 'user-circle',
                    'label' => __('patient.menu.account_settings'),
                    'sub' => __('patient.menu.account_settings_sub'),
                ],
            ],
        ],
        [
            'heading' => __('patient.sidebar.group_health'),
            'items' => [
                [
                    'href' => route('patient.medications'),
                    'icon' => 'clipboard-document',
                    'label' => __('patient.menu.medications'),
                    'sub' => __('patient.menu.medications_sub'),
                ],
                [
                    'href' => route('patient.favorites'),
                    'icon' => 'heart',
                    'label' => __('patient.menu.favorites'),
                    'sub' => __('patient.menu.favorites_sub'),
                ],
            ],
        ],
        [
            'heading' => __('patient.sidebar.group_help'),
            'items' => [
                [
                    'href' => route('patient.support'),
                    'icon' => 'question-mark-circle',
                    'label' => __('patient.menu.support'),
                    'sub' => __('patient.menu.support_sub'),
                ],
                [
                    'href' => route('patient.privacy'),
                    'icon' => 'shield-check',
                    'label' => __('patient.menu.privacy'),
                    'sub' => __('patient.menu.privacy_sub'),
                ],
            ],
        ],
    ];
@endphp

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

        <nav class="space-y-8" aria-label="{{ __('patient.menu.grid_aria') }}">
            @foreach ($menuSections as $section)
                <section>
                    <div class="mb-3 px-0.5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                            {{ $section['heading'] }}
                        </p>
                    </div>

                    <div
                        class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-1 snap-x snap-mandatory scroll-smooth sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-4 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        @foreach ($section['items'] as $item)
                            <a
                                href="{{ $item['href'] }}"
                                wire:navigate
                                class="group flex w-[11.5rem] shrink-0 snap-start flex-col rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm transition hover:border-[#10B981]/35 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/30 sm:w-auto sm:min-h-[8.5rem]"
                            >
                                <span class="inline-flex size-11 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#10B981] transition group-hover:bg-[#10B981]/15">
                                    <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                                </span>
                                <span class="mt-3 block text-sm font-semibold leading-snug text-zinc-900 group-hover:text-[#10B981]">
                                    {{ $item['label'] }}
                                </span>
                                <span class="mt-1 line-clamp-2 text-xs leading-relaxed text-zinc-500">
                                    {{ $item['sub'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </nav>
    </div>
</x-layouts::patient>
