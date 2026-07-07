<?php

use App\Models\Doctor;
use App\Models\Speciality;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::doctor')] #[Title('Personal account')] class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $name_ar = '';
    public string $email = '';
    public string $phone = '';
    public string $about = '';
    public string $about_ar = '';
    public string $registration_number = '';

    /** @var list<int> */
    public array $speciality_ids = [];

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public mixed $profile_photo = null;

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();

        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Speciality>
     */
    #[Computed]
    public function specialities()
    {
        return Speciality::query()->where('status', true)->orderBy('title')->get();
    }

    public function mount(): void
    {
        $doctor = $this->doctor();
        $doctor->loadMissing('specialities');

        $this->name = (string) ($doctor->name ?? '');
        $this->name_ar = (string) ($doctor->name_ar ?? '');
        $this->email = (string) ($doctor->email ?? '');
        $this->phone = (string) ($doctor->phone ?? '');
        $this->about = (string) ($doctor->about ?? '');
        $this->about_ar = (string) ($doctor->about_ar ?? '');
        $this->registration_number = (string) ($doctor->registration_number ?? '');
        $this->speciality_ids = $doctor->specialities
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function updatedSpecialityIds(): void
    {
        $this->resetValidation('speciality_ids');
    }

    public function saveProfile(): void
    {
        $doctor = $this->doctor();

        $this->speciality_ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $this->speciality_ids,
        )));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:doctors,email,'.$doctor->id],
            'phone' => ['required', 'string', 'max:20', 'unique:doctors,phone,'.$doctor->id],
            'about' => ['nullable', 'string', 'max:2000'],
            'about_ar' => ['nullable', 'string', 'max:2000'],
            'registration_number' => ['required', 'string', 'max:120'],
            'speciality_ids' => ['required', 'array', 'min:1'],
            'speciality_ids.*' => ['integer', Rule::exists(Speciality::class, 'id')],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->profile_photo) {
            $oldPath = $doctor->profile_photo_path;
            $newPath = $this->profile_photo->store('doctors', 'public');
            $doctor->profile_photo_path = $newPath;

            if (filled($oldPath) && Storage::disk('public')->exists((string) $oldPath)) {
                Storage::disk('public')->delete((string) $oldPath);
            }
        }

        $doctor->fill([
            'name' => $validated['name'],
            'name_ar' => $validated['name_ar'] ?: null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'about' => $validated['about'] ?: null,
            'about_ar' => $validated['about_ar'] ?: null,
            'registration_number' => $validated['registration_number'],
            'speciality_id' => $this->speciality_ids[0] ?? null,
        ])->save();

        $doctor->specialities()->sync($this->speciality_ids);

        $this->profile_photo = null;

        session()->flash('profile_saved', __('Profile updated successfully.'));
    }

    public function changePassword(): void
    {
        $doctor = $this->doctor();

        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed:new_password_confirmation'],
            'new_password_confirmation' => ['required', 'string', 'min:6'],
        ]);

        if (! Hash::check($validated['current_password'], (string) $doctor->password)) {
            $this->addError('current_password', __('Current password is incorrect.'));

            return;
        }

        $doctor->password = $validated['new_password'];
        $doctor->save();

        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        session()->flash('password_saved', __('Password changed successfully.'));
    }
}; ?>

@php
    $doctor = auth('doctor')->user();
    $localeIsAr = app()->getLocale() === 'ar';
    $profileFields = collect([
        filled($name),
        filled($email),
        filled($phone),
        filled($about),
        filled($about_ar),
        filled($doctor?->profile_photo_path),
        count($speciality_ids) > 0,
        filled($registration_number),
    ]);
    $profileCompletion = (int) round(($profileFields->filter()->count() / max(1, $profileFields->count())) * 100);
@endphp

<div class="relative w-full">
    @include('partials.doctor-luxury-profile-mobile')

    <div class="hidden space-y-6 lg:block">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-semibold tracking-tight text-zinc-900">{{ __('Personal account') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('Manage profile details, photo, phone, and password.') }}</flux:text>
        </div>
        <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
            {{ __('Back') }}
        </flux:button>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <form wire:submit="saveProfile" class="space-y-4">
            <div class="rounded-xl border border-zinc-200/80 bg-gradient-to-r from-zinc-50 to-[#eef2ff] p-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <flux:avatar
                            :name="auth('doctor')->user()?->displayName() ?? ''"
                            :src="auth('doctor')->user()?->profilePhotoUrl()"
                            circle
                            size="2xl"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-lg font-semibold text-zinc-900">{{ auth('doctor')->user()?->displayName() }}</p>
                            <p class="text-sm text-zinc-500">{{ __('Update your profile photo and account details') }}</p>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-[#047857]/10 px-2.5 py-1 text-xs font-semibold text-[#047857]">
                                    {{ __('Profile completeness') }}: {{ $profileCompletion }}%
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="w-full md:w-auto">
                        <flux:field>
                            <flux:label>{{ __('Profile photo') }}</flux:label>
                            <input type="file" wire:model="profile_photo" accept="image/*" class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm md:min-w-72" />
                            <flux:error name="profile_photo" />
                        </flux:field>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-white p-4">
                <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('Basic information') }}</flux:heading>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Name') }} @include('partials.required-field-mark')</flux:label>
                        <flux:input wire:model="name" />
                        <flux:error name="name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Name (Arabic)') }}</flux:label>
                        <flux:input wire:model="name_ar" dir="rtl" />
                        <flux:error name="name_ar" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Email') }} @include('partials.required-field-mark')</flux:label>
                        <flux:input wire:model="email" type="email" />
                        <flux:error name="email" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Phone number') }} @include('partials.required-field-mark')</flux:label>
                        <flux:input wire:model="phone" />
                        <flux:error name="phone" />
                    </flux:field>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-white p-4">
                <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('doctor.settings.license_section_title') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">{{ __('doctor.settings.license_section_hint') }}</flux:text>

                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('doctor.auth.registration_number') }} @include('partials.required-field-mark')</flux:label>
                        <flux:input wire:model="registration_number" autocomplete="off" />
                        <flux:error name="registration_number" />
                    </flux:field>

                    <div class="flex flex-col justify-end">
                        @if ($doctor?->profileDetailUrl())
                            <a
                                href="{{ $doctor->profileDetailUrl() }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100"
                            >
                                <flux:icon name="document-text" class="size-5 shrink-0" />
                                {{ __('doctor.settings.view_certificate') }}
                            </a>
                        @else
                            <flux:callout variant="secondary" icon="document-text" class="border-zinc-200">
                                {{ __('doctor.settings.no_certificate_on_file') }}
                            </flux:callout>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-white p-4">
                <flux:heading size="sm" class="font-semibold text-zinc-900">
                    {{ __('doctor.auth.specialities') }}
                    @include('partials.required-field-mark')
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">{{ __('doctor.settings.profile_specialities_hint') }}</flux:text>

                @if ($this->specialities->isEmpty())
                    <flux:callout variant="warning" icon="exclamation-circle" class="mt-3">
                        {{ __('doctor.auth.catalog_missing_hint') }}
                    </flux:callout>
                @else
                    <div class="mt-3">
                        <flux:field>
                            <flux:checkbox.group
                                wire:model.live="speciality_ids"
                                class="doctor-emerald-accent grid max-h-48 gap-2 overflow-y-auto rounded-xl border border-zinc-200 bg-zinc-50/80 p-3 sm:grid-cols-2"
                            >
                                @foreach ($this->specialities as $speciality)
                                    <label class="flex items-start gap-2.5 text-sm font-medium text-zinc-800">
                                        <flux:checkbox value="{{ $speciality->id }}" class="mt-0.5 shrink-0" />
                                        <span>
                                            {{ $localeIsAr && filled($speciality->title_ar) ? $speciality->title_ar : $speciality->title }}
                                        </span>
                                    </label>
                                @endforeach
                            </flux:checkbox.group>
                            <flux:error name="speciality_ids" />
                        </flux:field>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-white p-4">
                <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('Biography') }}</flux:heading>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('About') }}</flux:label>
                        <flux:textarea wire:model="about" rows="4" />
                        <flux:error name="about" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('About (Arabic)') }}</flux:label>
                        <flux:textarea wire:model="about_ar" rows="4" dir="rtl" />
                        <flux:error name="about_ar" />
                    </flux:field>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-zinc-100 pt-1">
                <flux:button type="submit" variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                    {{ __('Save profile') }}
                </flux:button>
                <span class="text-xs text-zinc-500">{{ __('Changes are applied immediately to your account.') }}</span>
            </div>

            @if (session('profile_saved'))
                <flux:text class="text-sm font-medium text-emerald-600">{{ session('profile_saved') }}</flux:text>
            @endif
        </form>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('Change password') }}</flux:heading>
            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600">{{ __('Security') }}</span>
        </div>

        <form wire:submit="changePassword" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Current password') }} @include('partials.required-field-mark')</flux:label>
                <flux:input wire:model="current_password" type="password" />
                <flux:error name="current_password" />
            </flux:field>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('New password') }} @include('partials.required-field-mark')</flux:label>
                    <flux:input wire:model="new_password" type="password" />
                    <flux:error name="new_password" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Confirm new password') }} @include('partials.required-field-mark')</flux:label>
                    <flux:input wire:model="new_password_confirmation" type="password" />
                    <flux:error name="new_password_confirmation" />
                </flux:field>
            </div>
            <div class="flex flex-wrap items-center gap-3 border-t border-zinc-100 pt-1">
                <flux:button type="submit" variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                    {{ __('Update password') }}
                </flux:button>
                <span class="text-xs text-zinc-500">{{ __('Use at least 6 characters for better protection.') }}</span>
            </div>

            @if (session('password_saved'))
                <flux:text class="text-sm font-medium text-emerald-600">{{ session('password_saved') }}</flux:text>
            @endif
        </form>
    </div>
    </div>
</div>
