<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Personal profile')] class extends Component {
    use PasswordValidationRules;
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('patient.home', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('A new verification link has been sent to your email address.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

@php
    $user = Auth::user();
    $profileFields = collect([filled($name), filled($email)]);
    $profileCompletion = (int) round(($profileFields->filter()->count() / max(1, $profileFields->count())) * 100);
    $passwordRequirementsTooltip = implode("\n", [
        __('patient.settings_page.password_tooltip_intro'),
        '• '.__('patient.settings_page.password_req_uppercase'),
        '• '.__('patient.settings_page.password_req_lowercase'),
        '• '.__('patient.settings_page.password_req_number'),
        '• '.__('patient.settings_page.password_req_special'),
    ]);
@endphp

<div class="mx-auto max-w-2xl space-y-6 px-4 py-8 pb-28 sm:pb-10">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-semibold text-[#1565c0]">{{ __('patient.settings_page.page_title') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('patient.settings_page.page_subtitle') }}</flux:text>
        </div>
        <flux:button :href="route('patient.menu')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
            {{ __('patient.empty_state.menu_crumb') }}
        </flux:button>
    </div>

    <div class="rounded-2xl border border-[#1565c0]/20 bg-gradient-to-br from-[#1565c0]/8 via-white to-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <flux:avatar :name="$user->name" circle size="2xl" class="ring-2 ring-[#1565c0]/15" />
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-zinc-900">{{ $user->name }}</p>
                    <p class="truncate text-sm text-zinc-500">{{ $user->email }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($this->hasUnverifiedEmail)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                {{ __('patient.settings_page.unverified') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                {{ __('patient.settings_page.verified') }}
                            </span>
                        @endif
                        @if ($user->created_at)
                            <span class="text-xs text-zinc-500">
                                {{ __('patient.settings_page.member_since', ['date' => $user->created_at->locale(app()->getLocale())->translatedFormat('M Y')]) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="shrink-0 rounded-xl border border-[#1565c0]/15 bg-white/80 px-4 py-3 text-center shadow-sm">
                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient.settings_page.completeness') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-[#1565c0]">{{ $profileCompletion }}%</p>
            </div>
        </div>
        <flux:text class="mt-4 text-sm text-zinc-600">{{ __('patient.settings_page.hero_hint') }}</flux:text>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 border-b border-zinc-100 pb-4">
            <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('patient.settings_page.account_heading') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">{{ __('patient.settings_page.account_sub') }}</flux:text>
        </div>

        <form wire:submit="updateProfileInformation" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="name" type="text" required autofocus autocomplete="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input wire:model="email" type="email" required autocomplete="email" />
                    <flux:error name="email" />
                </flux:field>

                @if (filled($user->phone))
                    <flux:field class="sm:col-span-2">
                        <flux:label>{{ __('patient.settings_page.phone_label') }}</flux:label>
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5">
                            <p class="text-sm font-medium text-zinc-900" dir="ltr">{{ $user->phone }}</p>
                        </div>
                        <flux:text class="text-xs text-zinc-500">{{ __('patient.settings_page.phone_readonly_hint') }}</flux:text>
                    </flux:field>
                @endif
            </div>

            @if ($this->hasUnverifiedEmail)
                <flux:callout variant="warning" icon="exclamation-circle">
                    <div class="space-y-2">
                        <p class="font-medium">{{ __('Your email address is unverified.') }}</p>
                        <flux:link class="cursor-pointer text-sm font-semibold" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </flux:link>
                    </div>
                </flux:callout>
            @endif

            <div class="flex flex-wrap items-center gap-3 border-t border-zinc-100 pt-4">
                <flux:button
                    variant="primary"
                    type="submit"
                    data-test="update-profile-button"
                    class="!bg-[#1565c0] !text-white hover:!brightness-95"
                >
                    {{ __('patient.settings_page.save_profile') }}
                </flux:button>
                <span class="text-xs text-zinc-500">{{ __('patient.settings_page.save_hint') }}</span>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-3 border-b border-zinc-100 pb-4">
            <div>
                <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('patient.settings_page.security_heading') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">{{ __('patient.settings_page.security_sub') }}</flux:text>
            </div>
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600">
                <flux:icon name="shield-check" variant="mini" class="size-3.5" />
                {{ __('patient.settings_page.security_badge') }}
            </span>
        </div>

        <form wire:submit="updatePassword" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Current password') }}</flux:label>
                <flux:input
                    wire:model="current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />
                <flux:error name="current_password" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <div class="flex items-center gap-1.5">
                        <flux:label>{{ __('New password') }}</flux:label>
                        <flux:tooltip :content="$passwordRequirementsTooltip" position="top">
                            <button
                                type="button"
                                class="inline-flex shrink-0 rounded-full text-zinc-400 transition hover:text-[#1565c0] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/40"
                                aria-label="{{ __('patient.settings_page.password_tooltip_aria') }}"
                            >
                                <flux:icon name="information-circle" variant="mini" class="size-4" />
                            </button>
                        </flux:tooltip>
                    </div>
                    <flux:input
                        wire:model="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Confirm password') }}</flux:label>
                    <flux:input
                        wire:model="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password_confirmation" />
                </flux:field>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-zinc-100 pt-4">
                <flux:button
                    variant="primary"
                    type="submit"
                    data-test="update-password-button"
                    class="!bg-[#1565c0] !text-white hover:!brightness-95"
                >
                    {{ __('patient.settings_page.save_password') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
