<?php

use App\Livewire\Concerns\HandlesDoctorBankAccountAttachment;
use App\Models\BankAccount;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Bank account')] class extends Component
{
    use HandlesDoctorBankAccountAttachment;

    public string $account_holder_name = '';

    public string $account_number = '';

    public string $iban_number = 'SA';

    public string $iban_rest = '';

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();

        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function mount(): void
    {
        $doctor = $this->doctor();
        $account = $doctor->bankAccount;

        $this->account_holder_name = (string) ($account?->account_holder_name ?? $doctor->name ?? '');
        $this->account_number = (string) ($account?->account_number ?? '');
        $this->applySaudiIban((string) ($account?->iban_number ?? 'SA'));
        $this->mountBankAccountAttachment($account);
    }

    public function updatedIbanNumber(string $value): void
    {
        $this->applySaudiIban($value);
    }

    public function updatedIbanRest(string $value): void
    {
        $this->applySaudiIban('SA'.$value);
    }

    public function goBack(): void
    {
        $this->redirect(route('doctor.register.basic.info', ['step' => 3]), navigate: true);
    }

    public function save(): void
    {
        $this->applySaudiIban($this->iban_number !== '' ? $this->iban_number : 'SA'.$this->iban_rest);

        $validated = $this->validate([
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'iban_number' => [
                'required',
                'string',
                'size:24',
                'regex:/^SA[0-9A-Z]{22}$/',
            ],
            ...$this->bankAttachmentValidationRules(),
        ], [
            'account_number.required' => __('doctor.auth.bank_account_number_required'),
            'iban_number.required' => __('doctor.auth.bank_iban_required'),
            'iban_number.size' => __('doctor.auth.bank_iban_invalid'),
            'iban_number.regex' => __('doctor.auth.bank_iban_invalid'),
        ]);

        $doctor = $this->doctor();

        $account = BankAccount::query()->updateOrCreate(
            ['doctor_id' => $doctor->id],
            [
                'doctor_id' => $doctor->id,
                'account_holder_name' => $validated['account_holder_name'] !== '' ? $validated['account_holder_name'] : null,
                'account_number' => $validated['account_number'],
                'iban_number' => $validated['iban_number'],
            ],
        );

        $this->syncBankAccountAttachment($account);

        $this->redirect(route('doctor.register.duration'), navigate: true);
    }

    private function applySaudiIban(string $value): void
    {
        $clean = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $value));

        if ($clean === '') {
            $clean = 'SA';
        } elseif (! str_starts_with($clean, 'SA')) {
            $clean = 'SA'.$clean;
        }

        $clean = substr($clean, 0, 24);

        $this->iban_number = $clean;
        $this->iban_rest = substr($clean, 2);
    }
}; ?>

<div class="doctor-onboarding-basic mx-auto max-w-lg space-y-5 pb-10 sm:max-w-xl sm:space-y-8 sm:pb-8">
    @include('partials.doctor-onboarding-header', [
        'current' => 4,
        'total' => 6,
        'title' => __('doctor.auth.bank_account_title'),
        'subtitle' => __('doctor.auth.bank_account_subtitle'),
    ])

    <form wire:submit="save" class="doctor-onboarding-form space-y-4">
        <div class="doctor-onboarding-card">
            <div class="space-y-5 px-4 py-4 sm:space-y-5 sm:px-6 sm:py-5">
                <flux:field>
                    <flux:label>{{ __('doctor.auth.bank_account_holder') }}</flux:label>
                    <flux:input wire:model="account_holder_name" autocomplete="name" class="rounded-2xl!" />
                    <flux:error name="account_holder_name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('doctor.auth.bank_account_number') }} @include('partials.required-field-mark')</flux:label>
                    <flux:input wire:model="account_number" inputmode="numeric" autocomplete="off" dir="ltr" class="rounded-2xl!" />
                    <flux:error name="account_number" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('doctor.auth.bank_iban_number') }} @include('partials.required-field-mark')</flux:label>
                    <div
                        class="flex overflow-hidden rounded-2xl border-2 border-zinc-300 bg-white focus-within:border-[#047857] focus-within:ring-2 focus-within:ring-[#047857]/20"
                        dir="ltr"
                        data-test="doctor-bank-iban-field"
                    >
                        <span class="inline-flex shrink-0 items-center border-e-2 border-zinc-300 bg-emerald-50 px-3 text-sm font-bold tracking-wide text-[#047857]">
                            SA
                        </span>
                        <input
                            type="text"
                            wire:model.live="iban_rest"
                            maxlength="22"
                            inputmode="text"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="00 0000 0000 0000 0000 00"
                            aria-label="{{ __('doctor.auth.bank_iban_number') }}"
                            class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-sm font-semibold uppercase tracking-wide text-zinc-900 outline-none placeholder:font-normal placeholder:normal-case placeholder:tracking-normal placeholder:text-zinc-400"
                        />
                    </div>
                    <flux:text class="mt-1.5 text-xs leading-relaxed text-zinc-600">{{ __('doctor.auth.bank_iban_hint') }}</flux:text>
                    <flux:error name="iban_number" />
                </flux:field>

                @include('partials.doctor-bank-attachment-field', [
                    'existingPath' => $existingAttachmentPath,
                    'existingUrl' => $this->existingAttachmentUrl(),
                    'existingIsImage' => $this->existingAttachmentIsImage(),
                    'existingFilename' => $this->existingAttachmentFilename(),
                ])
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
            <flux:button type="button" variant="ghost" wire:click="goBack" class="order-2 !rounded-full !text-zinc-700 sm:order-1">
                {{ __('doctor.auth.back') }}
            </flux:button>
            <flux:button
                class="doctor-onboarding-cta order-1 sm:order-2 sm:w-auto sm:min-w-44"
                type="submit"
                variant="primary"
            >
                {{ __('doctor.auth.save_continue') }}
            </flux:button>
        </div>
    </form>
</div>
