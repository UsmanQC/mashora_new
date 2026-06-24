<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light h-svh max-h-svh overflow-hidden bg-[#F6FFFC]"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }}" />
        <meta name="color-scheme" content="light" />
        @include('partials.pwa', ['themeColor' => '#F6FFFC'])
        @include('partials.head')
        @include('partials.intl-tel-input-patient-styles')
        @stack('styles')
    </head>
    <body class="h-svh max-h-svh overflow-hidden bg-[#F6FFFC] antialiased">
        @php
            $isCompactAuth = request()->routeIs(
                'patient.auth.sign-up',
                'patient.profile.basic',
            );
        @endphp
        <div class="flex h-svh max-h-svh min-h-0 w-full flex-col overflow-hidden bg-[#F6FFFC]">
            <header class="flex shrink-0 items-center justify-between gap-3 px-4 py-3 sm:px-8 sm:py-4 lg:px-10">
                <a
                    href="{{ route('patient.home') }}"
                    wire:navigate
                    class="inline-flex min-w-0 items-center"
                    title="{{ __('patient.brand') }}"
                >
                    @include('partials.patient-brand-logo', [
                        'imgClass' => 'h-9 w-auto max-w-[min(100%,13rem)] object-contain object-start',
                    ])
                </a>
                @include('partials.patient-language-switch', ['variant' => 'guest'])
            </header>

            <div class="grid min-h-0 flex-1 w-full grid-cols-1 overflow-hidden lg:grid-cols-2">
            <aside
                class="relative hidden min-h-0 flex-col overflow-hidden bg-[#F6FFFC] px-10 py-10 text-zinc-900 lg:flex"
            >
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    @php
                        $authIllustration = match (true) {
                            request()->routeIs('patient.auth.verify-phone') => 'images/sign_up-2.svg',
                            request()->routeIs('patient.auth.sign-up') => 'images/sign_up-3.svg',
                            default => 'images/patient_illustration.svg',
                        };
                    @endphp
                    <div class="mt-8 flex min-h-0 flex-1 flex-col items-center justify-center overflow-hidden py-4">
                        <div class="relative w-full max-w-lg overflow-visible">
                            <img
                                src="{{ asset($authIllustration) }}"
                                alt=""
                                width="560"
                                height="560"
                                class="relative mx-auto h-full max-h-[min(58vh,32rem)] w-full origin-center scale-110 object-contain object-center"
                                loading="eager"
                                decoding="async"
                            />
                        </div>
                    </div>
                </div>
            </aside>

            <main @class([
                'flex min-h-0 flex-1 flex-col overflow-hidden bg-[#F6FFFC] px-4 sm:px-8 lg:px-10',
                'patient-auth-density-compact' => $isCompactAuth,
            ])>
                <div @class([
                    'patient-auth-content mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col justify-center py-2 sm:py-4 lg:max-w-lg lg:py-6',
                    'patient-auth-register' => request()->routeIs('patient.auth.sign-up'),
                ])>
                    {{ $slot }}
                </div>
            </main>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @include('partials.intl-tel-input-patient-scripts')
        @stack('scripts')
        @fluxScripts
    </body>
</html>
