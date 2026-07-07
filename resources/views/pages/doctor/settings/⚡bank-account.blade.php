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

    public ?string $account_holder_name = null;

    public ?string $account_number = null;

    public ?string $iban_number = null;

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
        $account = $this->doctor()->bankAccount;

        $this->account_holder_name = $account?->account_holder_name;
        $this->account_number = $account?->account_number;
        $this->iban_number = $account?->iban_number;
        $this->mountBankAccountAttachment($account);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'iban_number' => ['nullable', 'string', 'max:255'],
            ...$this->bankAttachmentValidationRules(),
        ]);

        $doctor = $this->doctor();

        $account = BankAccount::query()->updateOrCreate(
            ['doctor_id' => $doctor->id],
            collect($validated)->except('attachment')->all() + ['doctor_id' => $doctor->id],
        );

        $this->syncBankAccountAttachment($account);

        session()->flash('bank_account_saved', __('doctor.auth.bank_account_saved'));
    }
}; ?>

<div class="relative w-full">
    @include('partials.doctor-luxury-bank-account-mobile')

    <div class="hidden space-y-6 lg:block">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.bank_account_title') }}</flux:heading>
        <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('doctor.auth.back') }}</flux:button>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <flux:text class="mb-5 text-sm text-zinc-600">{{ __('doctor.auth.bank_account_subtitle') }}</flux:text>

        <form wire:submit="save" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('doctor.auth.bank_account_holder') }}</flux:label>
                    <flux:input wire:model="account_holder_name" />
                    <flux:error name="account_holder_name" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('doctor.auth.bank_account_number') }}</flux:label>
                    <flux:input wire:model="account_number" dir="ltr" />
                    <flux:error name="account_number" />
                </flux:field>
            </div>
            <flux:field>
                <flux:label>{{ __('doctor.auth.bank_iban_number') }}</flux:label>
                <flux:input wire:model="iban_number" dir="ltr" class="uppercase" />
                <flux:error name="iban_number" />
            </flux:field>

            @include('partials.doctor-bank-attachment-field', [
                'existingPath' => $existingAttachmentPath,
                'existingUrl' => $this->existingAttachmentUrl(),
                'existingIsImage' => $this->existingAttachmentIsImage(),
                'existingFilename' => $this->existingAttachmentFilename(),
            ])

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                    {{ __('doctor.auth.bank_account_save') }}
                </flux:button>
                <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost">
                    {{ __('doctor.auth.cancel') }}
                </flux:button>
            </div>
            @if (session('bank_account_saved'))
                <flux:text class="text-sm font-medium text-emerald-600">{{ session('bank_account_saved') }}</flux:text>
            @endif
        </form>
    </div>
    </div>
</div>
