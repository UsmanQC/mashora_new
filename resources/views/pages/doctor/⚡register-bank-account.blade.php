<?php

use App\Models\BankAccount;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Bank account')] class extends Component
{
    public string $account_holder_name = '';

    public string $account_number = '';

    public string $iban_number = '';

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
        $this->iban_number = (string) ($account?->iban_number ?? '');
    }

    public function goBack(): void
    {
        $this->redirect(route('doctor.register.basic.info', ['step' => 3]), navigate: true);
    }

    public function save(): void
    {
        $this->iban_number = strtoupper((string) preg_replace('/\s+/', '', $this->iban_number));

        $validated = $this->validate([
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'iban_number' => [
                'required',
                'string',
                'size:24',
                'regex:/^SA[0-9A-Z]{22}$/',
            ],
        ], [
            'account_number.required' => __('doctor.auth.bank_account_number_required'),
            'iban_number.required' => __('doctor.auth.bank_iban_required'),
            'iban_number.size' => __('doctor.auth.bank_iban_invalid'),
            'iban_number.regex' => __('doctor.auth.bank_iban_invalid'),
        ]);

        $doctor = $this->doctor();

        BankAccount::query()->updateOrCreate(
            ['doctor_id' => $doctor->id],
            [
                'doctor_id' => $doctor->id,
                'account_holder_name' => $validated['account_holder_name'] !== '' ? $validated['account_holder_name'] : null,
                'account_number' => $validated['account_number'],
                'iban_number' => $validated['iban_number'],
            ],
        );

        $this->redirect(route('doctor.register.duration'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-xl space-y-8">
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <flux:text class="text-xs font-semibold uppercase tracking-wider text-[#047857]">
                {{ __('doctor.auth.onboarding_progress', ['current' => 4, 'total' => 6]) }}
            </flux:text>
            <flux:text class="text-xs font-medium tabular-nums text-zinc-500">
                {{ round((4 / 6) * 100) }}%
            </flux:text>
        </div>
        <div class="flex gap-1.5" aria-hidden="true">
            @for ($progressStep = 1; $progressStep <= 6; $progressStep++)
                <div
                    @class([
                        'h-1.5 flex-1 rounded-full transition-colors duration-300',
                        'bg-[#10B981]' => $progressStep <= 4,
                        'bg-zinc-200/90' => $progressStep > 4,
                    ])
                ></div>
            @endfor
        </div>
        <div>
            <flux:heading size="xl" class="font-semibold tracking-tight text-zinc-900">
                {{ __('doctor.auth.bank_account_title') }}
            </flux:heading>
            <flux:text class="mt-1.5 text-sm leading-relaxed text-zinc-600">
                {{ __('doctor.auth.bank_account_subtitle') }}
            </flux:text>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm ring-1 ring-zinc-900/[0.04] sm:p-6">
        <form wire:submit="save" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('doctor.auth.bank_account_holder') }}</flux:label>
                <flux:input wire:model="account_holder_name" autocomplete="name" class="rounded-xl!" />
                <flux:error name="account_holder_name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('doctor.auth.bank_account_number') }} @include('partials.required-field-mark')</flux:label>
                <flux:input wire:model="account_number" inputmode="numeric" autocomplete="off" dir="ltr" class="rounded-xl!" />
                <flux:error name="account_number" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('doctor.auth.bank_iban_number') }} @include('partials.required-field-mark')</flux:label>
                <flux:input wire:model="iban_number" placeholder="SA00 0000 0000 0000 0000 0000" autocomplete="off" dir="ltr" class="rounded-xl! uppercase" />
                <flux:text class="text-xs text-zinc-500">{{ __('doctor.auth.bank_iban_hint') }}</flux:text>
                <flux:error name="iban_number" />
            </flux:field>

            <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:justify-between">
                <flux:button type="button" variant="ghost" wire:click="goBack" class="order-2 sm:order-1">
                    {{ __('doctor.auth.back') }}
                </flux:button>
                <flux:button
                    class="order-1 w-full !bg-[#10B981] !text-white hover:!brightness-95 sm:order-2 sm:w-auto"
                    type="submit"
                    variant="primary"
                >
                    {{ __('doctor.auth.save_continue') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
