<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light min-h-svh"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }} — {{ __('doctor.portal_name') }}" />
        <meta name="theme-color" content="#10B981" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        @include('partials.head')
        @stack('styles')
    </head>
    <body class="min-h-svh antialiased">
        <div class="flex min-h-svh flex-col lg:flex-row">
            <div
                class="relative flex min-h-[32svh] flex-col justify-start overflow-hidden bg-gradient-to-br from-[#10B981] via-[#3558e6] to-[#2848d4] px-6 pb-8 pt-10 lg:min-h-svh lg:w-1/2 lg:justify-center lg:px-10 lg:pb-0"
            >
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="absolute -start-[10%] -top-[20%] h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute -bottom-[15%] -end-[5%] h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute start-[40%] top-1/3 h-48 w-48 rounded-full bg-[#047857]/20 blur-2xl"></div>
                </div>
                <div class="relative z-10 flex h-full w-full max-w-md flex-col text-white lg:mx-auto">
                    <div class="hidden flex-1 flex-col items-center justify-center gap-4 lg:flex">
                        <img
                            src="{{ asset('images/login-illustration.png') }}"
                            alt=""
                            class="h-auto w-full max-w-lg object-contain"
                            loading="eager"
                            decoding="async"
                        />
                        <div class="text-center">
                            <p class="text-xl font-semibold text-white">
                                {{ __('doctor.guest.illustration_caption') }}
                            </p>
                        </div>
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
                        ])
                    </a>
                    @include('partials.doctor-language-switch', ['variant' => 'guest'])
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
