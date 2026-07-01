<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scheme-light h-svh max-h-svh overflow-hidden bg-[#F6FFFC]"
>
    <head>
        <meta name="application-name" content="{{ config('app.name') }}" />
        <meta name="color-scheme" content="light" />
        @include('partials.pwa', ['themeColor' => '#F6FFFC', 'pwaApp' => 'patient'])
        @include('partials.head')
        @include('partials.intl-tel-input-patient-styles')
        @stack('styles')
    </head>
    <body class="patient-auth-body h-svh max-h-svh overflow-hidden bg-[#F6FFFC] antialiased">
        @php
            $isCompactAuth = request()->routeIs(
                'patient.auth.sign-up',
                'patient.profile.basic',
            );

            $authBack = \App\Support\PatientAuthBackNavigation::resolve();

            $authIllustration = match (true) {
                request()->routeIs('patient.auth.verify-phone') => 'images/sign_up-2.svg',
                request()->routeIs('patient.auth.sign-up') => 'images/sign_up-3.svg',
                default => 'images/patient_illustration.svg',
            };
        @endphp
        <div class="flex h-svh max-h-svh min-h-0 w-full flex-col overflow-hidden bg-[#F6FFFC]">
            <header class="relative flex shrink-0 items-center justify-end gap-2 px-5 pb-3.5 pt-[max(0.875rem,env(safe-area-inset-top))] sm:px-8 sm:pb-4 lg:px-10">
                <div class="absolute inset-y-0 left-5 flex items-center gap-1 sm:left-8 sm:gap-2 lg:left-10" dir="ltr">
                    @if ($authBack !== null)
                        @include('partials.patient-auth-header-back', ['authBack' => $authBack])
                    @endif

                    <a
                        href="{{ route('patient.home') }}"
                        wire:navigate
                        class="inline-flex min-w-0 items-center"
                        title="{{ __('patient.brand') }}"
                    >
                        @include('partials.patient-brand-logo', [
                            'imgClass' => 'h-9 w-auto max-w-[min(100%,11rem)] object-contain object-start',
                        ])
                    </a>
                </div>

                @include('partials.patient-language-switch', ['variant' => 'guest'])
            </header>

            @if ($authBack !== null)
                <div
                    id="patient-auth-swipe-hint"
                    class="patient-auth-swipe-hint pointer-events-none fixed inset-y-0 start-0 z-50 flex w-14 items-center justify-center sm:hidden"
                    aria-hidden="true"
                >
                    <span class="flex size-9 items-center justify-center rounded-full bg-white/95 text-[#10B981] shadow-md ring-1 ring-[#10B981]/15">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 rtl:rotate-180" aria-hidden="true">
                            <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            @endif

            <div
                id="patient-auth-swipe-surface"
                @class([
                    'grid min-h-0 flex-1 w-full grid-cols-1 overflow-hidden lg:grid-cols-2',
                    'patient-auth-swipe-surface' => $authBack !== null,
                ])
                @if ($authBack !== null)
                    data-back-url="{{ $authBack['url'] }}"
                @endif
            >
            <aside
                class="relative hidden min-h-0 flex-col overflow-hidden bg-[#F6FFFC] px-10 py-10 text-zinc-900 lg:flex"
            >
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
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
                'flex min-h-0 flex-1 flex-col overflow-y-auto bg-[#F6FFFC] px-5 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-8 lg:justify-center lg:overflow-hidden lg:px-10',
                'patient-auth-density-compact' => $isCompactAuth,
            ])>
                <div class="mx-auto my-auto flex w-full max-w-md flex-col gap-3 py-4 sm:gap-4 lg:my-0 lg:max-w-lg lg:py-6">
                    <div class="shrink-0 lg:hidden">
                        <img
                            src="{{ asset($authIllustration) }}"
                            alt=""
                            width="320"
                            height="320"
                            @class([
                                'mx-auto w-auto object-contain object-center',
                                'max-h-[7.5rem]' => $isCompactAuth,
                                'max-h-[8.75rem] sm:max-h-[9.5rem]' => ! $isCompactAuth,
                            ])
                            loading="eager"
                            decoding="async"
                        />
                    </div>

                    <div @class([
                        'patient-auth-content w-full text-start',
                        'patient-auth-register' => request()->routeIs('patient.auth.sign-up'),
                    ])>
                        {{ $slot }}
                    </div>
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
