<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light bg-[#F3F5F9]"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }} — {{ __('doctor.portal_name') }}" />
        @include('partials.pwa', ['themeColor' => '#F3F5F9'])
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        @include('partials.head')
    </head>
    <body class="min-h-svh antialiased">
        <aside
            class="portal-chrome-sidebar fixed inset-y-0 start-0 z-50 hidden w-64 min-h-0 flex-col overflow-hidden bg-[#10B981] text-white shadow-lg lg:flex"
            aria-label="{{ __('doctor.sidebar_label') }}"
        >
            <div class="shrink-0 border-b border-white/10 px-4 pb-5 pt-5">
                @include('partials.doctor-brand-strip', ['density' => 'sidebar'])
            </div>
            <nav
                class="portal-sidebar-scroll min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain px-2 py-4"
                aria-label="{{ __('doctor.sidebar_label') }}"
            >
                @include('partials.doctor-sidebar-nav')
            </nav>
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
                        ])
                    </a>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <div class="hidden sm:block">
                        @include('partials.doctor-language-switch', ['variant' => 'chrome'])
                    </div>
                    @include('partials.doctor-user-account-menu', ['density' => 'chrome'])
                </div>
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
                        @include('partials.doctor-language-switch', ['variant' => 'chrome'])
                        <livewire:doctor.components.notifications />
                        @include('partials.doctor-user-account-menu', ['density' => 'chrome'])
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
                <div class="grid w-full grid-cols-3 gap-0.5">
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
                        class="min-h-11 px-1 py-1.5 text-[0.65rem] leading-tight sm:text-xs {{ request()->routeIs('doctor.appointments', 'doctor.appointments.*') ? '!bg-[#047857] !text-white' : 'text-zinc-600' }}"
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
