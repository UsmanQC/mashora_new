<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light min-h-svh"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }} — {{ __('doctor.portal_name') }}" />
        <meta name="theme-color" content="#3C5CF7" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        @include('partials.head')
        @stack('styles')
    </head>
    <body class="min-h-svh antialiased">
        <div class="flex min-h-svh flex-col lg:flex-row">
            <div
                class="relative flex min-h-[32svh] flex-col justify-start overflow-hidden bg-gradient-to-br from-[#3C5CF7] via-[#3558e6] to-[#2848d4] px-6 pb-8 pt-10 lg:min-h-svh lg:w-1/2 lg:justify-center lg:px-10 lg:pb-0"
            >
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="absolute -start-[10%] -top-[20%] h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute -bottom-[15%] -end-[5%] h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute start-[40%] top-1/3 h-48 w-48 rounded-full bg-[#132A6E]/20 blur-2xl"></div>
                </div>
                <div class="relative z-10 max-w-md text-white lg:mx-auto">
                    @include('partials.doctor-brand-strip', [
                        'density' => 'sidebar',
                        'href' => route('doctor.welcome'),
                    ])
                    <p class="mt-6 text-sm font-medium text-white/80">{{ __('doctor.guest.kicker') }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight lg:text-4xl">{{ __('doctor.guest.headline') }}</h1>
                    <p class="mt-3 text-base text-white/85">{{ __('doctor.guest.subhead') }}</p>
                    <div class="mt-8 hidden lg:block" aria-hidden="true">
                        <svg
                            class="h-auto w-full max-w-sm opacity-90"
                            viewBox="0 0 400 260"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <rect x="40" y="48" width="200" height="140" rx="12" fill="white" fill-opacity="0.12" />
                            <rect x="68" y="76" width="144" height="12" rx="4" fill="white" fill-opacity="0.35" />
                            <rect x="68" y="100" width="96" height="12" rx="4" fill="white" fill-opacity="0.2" />
                            <rect x="68" y="124" width="120" height="12" rx="4" fill="white" fill-opacity="0.2" />
                            <circle cx="320" cy="72" r="48" fill="white" fill-opacity="0.18" />
                            <path
                                d="M300 120c20-24 52-24 72 0s20 64 0 88-52 24-72 0-20-64 0-88Z"
                                fill="white"
                                fill-opacity="0.1"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 flex-col bg-white lg:w-1/2">
                <div
                    class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 sm:px-6 lg:border-b-0 lg:px-10 lg:pt-8"
                >
                    <a
                        href="{{ route('doctor.welcome') }}"
                        wire:navigate
                        class="inline-flex min-w-0 items-center"
                        title="{{ __('patient.brand') }}"
                    >
                        @include('partials.patient-brand-logo', [
                            'imgClass' => 'h-9 w-auto max-w-[min(100%,13rem)] object-contain object-start',
                            'whiteOnBlue' => false,
                        ])
                    </a>
                    <div class="flex items-center gap-2 text-sm">
                        <a
                            href="{{ route('doctor.locale', ['locale' => 'en']) }}"
                            class="{{ app()->getLocale() === 'en' ? 'font-semibold text-zinc-900' : 'text-zinc-500 hover:text-zinc-800' }}"
                        >
                            EN
                        </a>
                        <span class="text-zinc-300" aria-hidden="true">|</span>
                        <a
                            href="{{ route('doctor.locale', ['locale' => 'ar']) }}"
                            class="{{ app()->getLocale() === 'ar' ? 'font-semibold text-zinc-900' : 'text-zinc-500 hover:text-zinc-800' }}"
                        >
                            AR
                        </a>
                    </div>
                </div>
                <div class="flex flex-1 flex-col px-4 py-8 sm:px-6 lg:justify-center lg:px-10 lg:py-12">
                    <div class="mx-auto w-full max-w-md flex-1">
                        {{ $slot }}
                    </div>
                </div>
            </div>
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
