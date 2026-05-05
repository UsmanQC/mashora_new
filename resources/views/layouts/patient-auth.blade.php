<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light h-svh max-h-svh overflow-hidden bg-white"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }}" />
        <meta name="theme-color" content="#ffffff" />
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
        <link rel="manifest" href="/manifest.webmanifest" />
        @include('partials.head')
    </head>
    <body class="h-svh max-h-svh overflow-hidden bg-[#F3F5F7] antialiased lg:bg-white">
        <div class="grid h-svh max-h-svh min-h-0 w-full grid-cols-1 overflow-hidden lg:grid-cols-2">
            <aside
                class="relative hidden min-h-0 flex-col overflow-hidden border-zinc-200 bg-white px-10 py-10 text-zinc-900 lg:flex lg:border-e"
            >
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <a
                        href="{{ route('patient.home') }}"
                        wire:navigate
                        class="inline-flex shrink-0 items-center"
                        title="{{ __('patient.brand') }}"
                    >
                        @include('partials.patient-brand-logo', [
                            'imgClass' => 'h-11 w-auto max-w-[min(100%,15rem)] object-contain object-start',
                            'whiteOnBlue' => false,
                        ])
                    </a>

                    @if (request()->routeIs('patient.phone'))
                        <div class="mt-8 flex min-h-0 flex-1 flex-col items-center justify-center overflow-hidden py-4">
                            <img
                                src="{{ asset('images/login-illustration.png') }}"
                                alt=""
                                width="560"
                                height="560"
                                class="h-full max-h-[min(52vh,28rem)] w-full max-w-lg object-contain object-center"
                                loading="eager"
                                decoding="async"
                            />
                        </div>
                    @else
                        <flux:heading size="xl" class="mt-8 max-w-sm text-balance text-zinc-900">
                            {{ __('patient_auth.promo_title') }}
                        </flux:heading>
                        <flux:text class="mt-4 max-w-md text-balance text-zinc-600">
                            {{ __('patient_auth.promo_body') }}
                        </flux:text>
                    @endif
                </div>
            </aside>

            <main
                class="flex min-h-0 flex-col justify-center overflow-hidden bg-[#F3F5F7] px-4 py-5 sm:px-8 sm:py-6 lg:py-8"
            >
                <div class="mx-auto w-full max-w-md shrink-0">
                    <div class="mb-5 flex items-center justify-between lg:mb-6 lg:hidden">
                        <a
                            href="{{ route('patient.home') }}"
                            wire:navigate
                            class="inline-flex min-w-0 items-center"
                            title="{{ __('patient.brand') }}"
                        >
                            @include('partials.patient-brand-logo', [
                                'imgClass' => 'h-9 w-auto max-w-[min(100%,13rem)] object-contain object-start',
                                'whiteOnBlue' => false,
                            ])
                        </a>
                        <flux:button :href="route('patient.home')" variant="ghost" size="sm" wire:navigate>
                            {{ __('patient_auth.back_home') }}
                        </flux:button>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
