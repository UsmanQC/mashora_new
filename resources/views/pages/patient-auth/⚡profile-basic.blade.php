<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient-auth')] #[Title('A few more details')] class extends Component
{
    public ?string $email = null;

    public ?string $gender = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->redirect(route('patient.auth.sign-in'));

            return;
        }

        if ($user->profile_completed) {
            $this->redirect(route('patient.home'));

            return;
        }

        $this->email = $user->email;
        $this->gender = $user->gender;
    }

    public function saveBasics(): void
    {
        $patient = Auth::user();

        if (! $patient instanceof User) {
            $this->redirect(route('patient.auth.sign-in'));

            return;
        }

        $validated = $this->validate([
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($patient->id),
            ],
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

        $this->redirect(route('patient.register.done'));
    }
}; ?>

<div>
    <flux:heading size="xl" class="patient-auth-heading">{{ __('patient_auth.profile_basic_title') }}</flux:heading>
    <flux:text class="mt-2">{{ __('patient_auth.profile_basic_sub') }}</flux:text>

    <form wire:submit="saveBasics" class="mt-8 space-y-6">
        <flux:input
            wire:model.blur="email"
            type="email"
            autocomplete="email"
            :label="__('patient_auth.email_optional')"
        />

        <flux:field>
            <flux:label>{{ __('patient_auth.gender') }}</flux:label>
            <div class="patient-gender-fields">
                <flux:radio.group wire:model="gender" class="grid gap-3 sm:grid-cols-3 sm:gap-x-6">
                    <flux:radio value="male" :label="__('patient_auth.gender_male')" />
                    <flux:radio value="female" :label="__('patient_auth.gender_female')" />
                    <flux:radio value="other" :label="__('patient_auth.gender_other')" />
                </flux:radio.group>
            </div>
            <flux:error name="gender" />
        </flux:field>

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
            {{ __('patient_auth.continue') }}
        </flux:button>
    </form>
</div>
