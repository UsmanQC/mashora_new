@php
    $profileInitials = collect(explode(' ', trim((string) (auth('doctor')->user()?->displayName() ?? ''))))
        ->filter()
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<div
    class="doctor-luxury-profile relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-profile"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.menu') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.auth.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-xl font-bold tracking-tight text-slate-900">
                {{ __('Personal account') }}
            </h1>
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-5 overflow-y-auto overscroll-contain px-5 pb-4 pt-1">
        <form id="doctor-profile-form" wire:submit="saveProfile" class="contents">
            <section class="flex flex-col items-center gap-3 rounded-3xl border border-slate-100 bg-white p-5 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <label class="relative inline-flex cursor-pointer">
                    <input type="file" wire:model="profile_photo" accept="image/*" class="hidden" />
                    <span class="flex size-20 items-center justify-center overflow-hidden rounded-full bg-[#10B981]/10 text-xl font-bold text-[#047857]">
                        @if (auth('doctor')->user()?->profilePhotoUrl())
                            <img src="{{ auth('doctor')->user()->profilePhotoUrl() }}" alt="" class="size-full object-cover" />
                        @else
                            {{ $profileInitials ?: '?' }}
                        @endif
                    </span>
                    <span class="absolute -bottom-1 -end-1 flex size-7 items-center justify-center rounded-full border-2 border-white bg-[#047857] text-white shadow">
                        <flux:icon name="camera" variant="mini" class="size-3.5" />
                    </span>
                </label>

                <div>
                    <p class="text-base font-bold text-slate-900">{{ auth('doctor')->user()?->displayName() }}</p>
                    <span class="mt-1.5 inline-flex items-center rounded-full bg-[#10B981]/10 px-2.5 py-1 text-xs font-bold text-[#047857]">
                        {{ __('Profile completeness') }}: {{ $profileCompletion }}%
                    </span>
                </div>

                <div wire:loading wire:target="profile_photo" class="text-xs font-medium text-emerald-700">
                    {{ __('doctor.auth.bank_attachment_uploading') }}
                </div>
                <flux:error name="profile_photo" />
            </section>

            <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('Basic information') }}
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('Name') }}</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]" />
                        <flux:error name="name" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('Name (Arabic)') }}</label>
                        <input type="text" dir="rtl" wire:model="name_ar" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]" />
                        <flux:error name="name_ar" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('Email') }}</label>
                        <input type="email" dir="ltr" wire:model="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]" />
                        <flux:error name="email" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('Phone number') }}</label>
                        <input type="text" dir="ltr" wire:model="phone" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]" />
                        <flux:error name="phone" />
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <h2 class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('doctor.settings.license_section_title') }}
                </h2>
                <p class="mt-1 mb-3 text-xs leading-relaxed text-slate-500">{{ __('doctor.settings.license_section_hint') }}</p>

                <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('doctor.auth.registration_number') }}</label>
                <input
                    type="text"
                    dir="ltr"
                    autocomplete="off"
                    wire:model="registration_number"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]"
                />
                <flux:error name="registration_number" />

                @if ($doctor?->profileDetailUrl())
                    <a
                        href="{{ $doctor->profileDetailUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800"
                    >
                        <flux:icon name="document-text" variant="mini" class="size-3.5" />
                        {{ __('doctor.settings.view_certificate') }}
                    </a>
                @else
                    <p class="mt-3 text-xs text-slate-500">{{ __('doctor.settings.no_certificate_on_file') }}</p>
                @endif
            </section>

            <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <h2 class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('doctor.auth.specialities') }}
                </h2>
                <p class="mt-1 mb-3 text-xs leading-relaxed text-slate-500">{{ __('doctor.settings.profile_specialities_hint') }}</p>

                @if ($this->specialities->isEmpty())
                    <p class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                        {{ __('doctor.auth.catalog_missing_hint') }}
                    </p>
                @else
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($this->specialities as $speciality)
                            <label class="relative block" wire:key="doctor-speciality-{{ $speciality->id }}">
                                <input
                                    type="checkbox"
                                    wire:model.live="speciality_ids"
                                    value="{{ $speciality->id }}"
                                    class="peer sr-only"
                                />
                                <span class="flex min-h-11 items-center justify-center rounded-2xl border border-slate-200 px-2 py-2 text-center text-xs font-bold text-slate-600 transition peer-checked:border-transparent peer-checked:bg-[#047857] peer-checked:text-white peer-checked:shadow-[0_4px_14px_-2px_rgba(4,120,87,0.4)]">
                                    {{ $localeIsAr && filled($speciality->title_ar) ? $speciality->title_ar : $speciality->title }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="speciality_ids" />
                @endif
            </section>

            <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('Biography') }}
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('About') }}</label>
                        <textarea wire:model="about" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]"></textarea>
                        <flux:error name="about" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('About (Arabic)') }}</label>
                        <textarea wire:model="about_ar" rows="4" dir="rtl" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]"></textarea>
                        <flux:error name="about_ar" />
                    </div>
                </div>
            </section>

            @if (session('profile_saved'))
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-700">
                    {{ session('profile_saved') }}
                </p>
            @endif
        </form>

        <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('Change password') }}
                </h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.625rem] font-bold text-slate-500">
                    {{ __('Security') }}
                </span>
            </div>

            <form wire:submit="changePassword" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('Current password') }}</label>
                    <input type="password" wire:model="current_password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]" />
                    <flux:error name="current_password" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('New password') }}</label>
                    <input type="password" wire:model="new_password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]" />
                    <flux:error name="new_password" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('Confirm new password') }}</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]" />
                    <flux:error name="new_password_confirmation" />
                </div>

                <button
                    type="submit"
                    class="flex min-h-11 w-full items-center justify-center gap-2 rounded-full border border-[#047857]/30 bg-[#10B981]/10 text-sm font-bold text-[#047857] transition active:scale-[0.98]"
                >
                    {{ __('Update password') }}
                </button>

                @if (session('password_saved'))
                    <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-700">
                        {{ session('password_saved') }}
                    </p>
                @endif
            </form>
        </section>
    </main>

    <div class="shrink-0 border-t border-slate-100 bg-white px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-3">
        <button
            type="submit"
            form="doctor-profile-form"
            class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-[#047857] text-sm font-bold text-white shadow-sm transition active:scale-[0.98]"
        >
            {{ __('Save profile') }}
        </button>
    </div>
</div>
