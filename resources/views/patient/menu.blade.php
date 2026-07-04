<x-layouts::patient>
@php
    $user = auth()->user();
    $profilePhotoUrl = $user !== null && filled($user->profile_photo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $user->profile_photo_path)
        : null;
@endphp

<div class="patient-luxury-menu bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-menu">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.nav.menu'),
            'subtitle' => __('patient.menu.page_subtitle'),
            'profilePhotoUrl' => $profilePhotoUrl,
            'userName' => $user?->name,
            'testId' => 'patient-menu-header',
        ])

        @auth
            <section class="px-6 pt-5 pb-2">
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                    <div class="flex items-center gap-4">
                        @if ($profilePhotoUrl !== null)
                            <img src="{{ $profilePhotoUrl }}" alt="" class="size-14 shrink-0 rounded-full object-cover ring-2 ring-white" />
                        @else
                            <flux:avatar :name="$user->name" circle class="size-14 shrink-0" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-bold text-slate-900">{{ $user->name }}</p>
                            <div class="mt-1 space-y-0.5 text-xs text-slate-500">
                                @if (filled($user->email))
                                    <p class="truncate">{{ $user->email }}</p>
                                @endif
                                @if (filled($user->phone))
                                    <p class="truncate" dir="ltr">{{ $user->phone }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl border border-[#10B981]/20 bg-emerald-50 py-3 text-sm font-semibold text-[#059669] transition hover:bg-emerald-100/80"
                    >
                        <flux:icon name="cog-6-tooth" variant="outline" class="size-4" />
                        {{ __('patient.menu.account_settings') }}
                    </a>
                </div>
            </section>
        @endauth

        @include('partials.patient-luxury-menu-sections')
    </div>

    <div class="mx-auto hidden w-full max-w-5xl space-y-8 px-6 py-4 sm:block sm:px-0 sm:py-0 lg:px-0">
        <header class="mb-8">
            <nav class="mb-3 text-sm text-zinc-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li>
                        <flux:link :href="route('patient.home')" wire:navigate class="font-medium text-[#10B981] hover:text-[#064e3b]">
                            {{ __('patient.nav.home') }}
                        </flux:link>
                    </li>
                    <li aria-hidden="true" class="text-zinc-400">/</li>
                    <li class="font-semibold text-zinc-900">{{ __('patient.nav.menu') }}</li>
                </ol>
            </nav>
            <flux:heading size="xl" class="font-semibold text-zinc-900">
                {{ __('patient.nav.menu') }}
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('patient.menu.page_subtitle') }}</flux:text>
        </header>

        @auth
            <div class="rounded-3xl border border-slate-100/80 bg-white p-6 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    @if ($profilePhotoUrl !== null)
                        <img src="{{ $profilePhotoUrl }}" alt="" class="size-16 shrink-0 rounded-full object-cover ring-2 ring-[#10B981]/15" />
                    @else
                        <flux:avatar :name="$user->name" circle size="xl" class="shrink-0 ring-2 ring-[#10B981]/15" />
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-lg font-semibold text-slate-900">{{ $user->name }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                            @if (filled($user->email))
                                <span class="truncate">{{ $user->email }}</span>
                            @endif
                            @if (filled($user->phone))
                                <span class="truncate" dir="ltr">{{ $user->phone }}</span>
                            @endif
                        </div>
                    </div>
                    <flux:button
                        :href="route('profile.edit')"
                        wire:navigate
                        variant="primary"
                        size="sm"
                        icon="cog-6-tooth"
                        class="w-full shrink-0 !border-[#10B981] !bg-[#10B981] !text-white hover:!brightness-[0.97] sm:w-auto"
                    >
                        {{ __('patient.menu.account_settings') }}
                    </flux:button>
                </div>
            </div>
        @endauth

        @include('partials.patient-menu-sections', ['variant' => 'desktop'])
    </div>
</div>
</x-layouts::patient>
