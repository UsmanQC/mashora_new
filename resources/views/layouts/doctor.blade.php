<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light bg-[#F3F5F9]"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }} — {{ __('doctor.portal_name') }}" />
        <meta name="theme-color" content="#F3F5F9" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        @include('partials.head')
    </head>
    <body class="min-h-svh antialiased">
        <aside
            class="fixed inset-y-0 start-0 z-50 hidden w-64 flex-col bg-[#10B981] text-white shadow-lg lg:flex"
            aria-label="{{ __('doctor.sidebar_label') }}"
        >
            <div class="border-b border-white/10 px-4 pb-5 pt-5">
                @include('partials.doctor-brand-strip', ['density' => 'sidebar'])
            </div>
            <nav class="flex flex-1 flex-col gap-1 px-2 py-4">
                @include('partials.doctor-sidebar-nav')
            </nav>
            <div class="border-t border-white/10 p-3"></div>
        </aside>

        <div class="flex min-h-svh min-w-0 flex-col lg:ps-64">
            <header
                class="sticky top-0 z-40 flex items-center justify-between gap-3 border-b border-zinc-200/80 bg-white/95 px-4 py-3 backdrop-blur lg:hidden"
            >
                <div class="min-w-0 flex-1">
                    <a
                        href="{{ route('doctor.dashboard') }}"
                        wire:navigate
                        class="inline-flex min-w-0 items-center"
                        title="{{ __('patient.brand') }}"
                    >
                        @include('partials.patient-brand-logo', [
                            'imgClass' => 'h-9 w-auto max-w-[min(100%,11rem)] object-contain object-start',
                            'whiteOnBlue' => false,
                        ])
                    </a>
                </div>
                <form method="POST" action="{{ route('doctor.logout') }}" class="shrink-0">
                    @csrf
                    <flux:button type="submit" variant="ghost" size="sm">{{ __('doctor.auth.sign_out') }}</flux:button>
                </form>
            </header>

            <header
                class="sticky top-0 z-30 hidden border-b border-zinc-200/80 bg-white/95 px-6 py-4 backdrop-blur lg:block"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">
                            {{ __('doctor.header.welcome', ['name' => auth('doctor')->user()?->displayName() ?? '']) }}
                        </p>
                        <time class="text-sm text-zinc-500" datetime="{{ now()->toIso8601String() }}">
                            {{ now()->locale(app()->getLocale())->isoFormat('LL') }}
                        </time>
                    </div>
                    <div class="flex items-center gap-2">
                        <livewire:doctor.components.notifications />
                        <details class="relative">
                            <summary
                                class="inline-flex h-9 min-w-[2.25rem] cursor-pointer list-none items-center justify-center rounded-full border border-zinc-200/90 bg-zinc-50 px-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100"
                                title="{{ auth('doctor')->user()?->displayName() }}"
                            >
                                <flux:icon name="user" variant="outline" class="size-4" />
                            </summary>

                            <div class="absolute end-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-zinc-200 bg-white p-2 shadow-xl">
                                <div class="px-2 pb-2 text-xs text-zinc-500">
                                    {{ auth('doctor')->user()?->displayName() }}
                                </div>
                                <form method="POST" action="{{ route('doctor.logout') }}">
                                    @csrf
                                    <flux:button
                                        class="w-full justify-start"
                                        type="submit"
                                        variant="ghost"
                                        size="sm"
                                        icon="arrow-right-start-on-rectangle"
                                    >
                                        {{ __('doctor.auth.sign_out') }}
                                    </flux:button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 pb-28 sm:px-6 lg:pb-8">
                {{ $slot }}
            </main>

            <nav
                class="fixed inset-x-0 bottom-0 z-40 flex border-t border-zinc-200 bg-white px-1 py-1.5 pb-[max(0.5rem,env(safe-area-inset-bottom))] lg:hidden"
                aria-label="{{ __('doctor.mobile_nav_label') }}"
            >
                <div class="grid w-full grid-cols-4 gap-0.5">
                    <flux:button
                        :href="route('doctor.dashboard')"
                        wire:navigate
                        variant="ghost"
                        size="sm"
                        class="min-h-11 px-1 py-1.5 text-[0.65rem] leading-tight sm:text-xs {{ request()->routeIs('doctor.dashboard') ? '!bg-[#047857] !text-white' : 'text-zinc-600' }}"
                        icon="home"
                    >
                        {{ __('doctor.nav.dashboard') }}
                    </flux:button>
                    <flux:button
                        :href="route('doctor.appointments')"
                        wire:navigate
                        variant="ghost"
                        size="sm"
                        class="min-h-11 px-1 py-1.5 text-[0.65rem] leading-tight sm:text-xs {{ request()->routeIs('doctor.appointments') ? '!bg-[#047857] !text-white' : 'text-zinc-600' }}"
                        icon="calendar-days"
                    >
                        {{ __('doctor.nav.appointments') }}
                    </flux:button>
                    <flux:button
                        :href="route('doctor.ratings')"
                        wire:navigate
                        variant="ghost"
                        size="sm"
                        class="min-h-11 px-1 py-1.5 text-[0.65rem] leading-tight sm:text-xs {{ request()->routeIs('doctor.ratings') ? '!bg-[#047857] !text-white' : 'text-zinc-600' }}"
                        icon="star"
                    >
                        {{ __('doctor.nav.ratings') }}
                    </flux:button>
                    <flux:button
                        :href="route('doctor.settings')"
                        wire:navigate
                        variant="ghost"
                        size="sm"
                        class="min-h-11 px-1 py-1.5 text-[0.65rem] leading-tight sm:text-xs {{ request()->routeIs('doctor.settings') ? '!bg-[#047857] !text-white' : 'text-zinc-600' }}"
                        icon="cog-6-tooth"
                    >
                        {{ __('doctor.nav.menu') }}
                    </flux:button>
                </div>
            </nav>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        @stack('scripts')
        @fluxScripts
    </body>
</html>
