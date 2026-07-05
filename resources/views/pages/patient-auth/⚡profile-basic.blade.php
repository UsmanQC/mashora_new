<?php

use App\Models\User;
use App\Support\PendingPatientBooking;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient-auth')] #[Title('A few more details')] class extends Component {
    public ?string $email = null;

    public ?string $gender = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            $this->redirect(route('patient.phone'));

            return;
        }

        if ($user->profile_completed) {
            $this->redirect(PendingPatientBooking::homeOrBookingUrl());

            return;
        }

        $this->email = $user->email;
        $this->gender = $user->gender;
    }

    public function saveBasics(): void
    {
        $patient = Auth::user();

        if (!$patient instanceof User) {
            $this->redirect(route('patient.phone'));

            return;
        }

        $validated = $this->validate([
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($patient->id)],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
        ]);

        $updates = [
            'gender' => $validated['gender'],
            'profile_completed' => true,
        ];

        if (array_key_exists('email', $validated) && filled($validated['email'])) {
            $updates['email'] = $validated['email'];
        }

        $patient->update($updates);

        Flux::toast(variant: 'success', text: __('patient_auth.congrats_title'));

        $this->redirect(PendingPatientBooking::url() ?? route('patient.register.done'));
    }
}; ?>

<div class="flex min-h-0 w-full flex-col">
    <flux:heading size="lg" class="patient-auth-heading sm:!text-2xl">{{ __('patient_auth.profile_basic_title') }}</flux:heading>
    <flux:text class="mt-1 text-sm sm:mt-2 sm:text-base">{{ __('patient_auth.profile_basic_sub') }}</flux:text>

    <form wire:submit="saveBasics" class="patient-auth-form mt-5 space-y-3 sm:mt-8 sm:space-y-4">
        <flux:input wire:model.blur="email" type="email" autocomplete="email"
            :label="__('patient_auth.email_optional')" />

        <flux:field>
            <flux:label>{{ __('patient_auth.gender') }} @include('partials.required-field-mark')</flux:label>
            <div class="patient-gender-segmented">
                <flux:radio.group variant="segmented" wire:model.live="gender" class="w-full">
                    <flux:radio value="male" :label="__('patient_auth.gender_male')" />
                    <flux:radio value="female" :label="__('patient_auth.gender_female')" />
                </flux:radio.group>
            </div>
            <flux:error name="gender" />
        </flux:field>

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
            {{ __('patient_auth.continue') }}
        </flux:button>
    </form>
</div>
