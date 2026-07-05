<?php

use App\Models\User;
use App\Services\PatientWalletService;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Wallet')] class extends Component
{
    protected function patient(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    public function getBalanceProperty(): float
    {
        return app(PatientWalletService::class)->balance($this->patient());
    }

    /**
     * @return array{credited: float, spent: float, balance: float}
     */
    public function getMonthlySummaryProperty(): array
    {
        return app(PatientWalletService::class)->monthlySummary($this->patient());
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionsProperty(): Collection
    {
        return app(PatientWalletService::class)->recentTransactions($this->patient(), 30);
    }

    public function transactionLabel(Transaction $transaction): string
    {
        return app(PatientWalletService::class)->transactionLabel($transaction);
    }

    public function transactionAmount(Transaction $transaction): float
    {
        return app(PatientWalletService::class)->transactionAmountSigned($transaction);
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }
}; ?>

<div class="patient-luxury-wallet bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-wallet">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.wallet.title'),
            'subtitle' => __('patient.wallet.subtitle'),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'backUrl' => route('patient.menu'),
            'backLabel' => __('patient.nav.menu'),
            'testId' => 'patient-wallet-header',
        ])
    </div>

    <div class="mx-auto max-w-2xl space-y-5 px-6 pt-5 sm:space-y-6 sm:px-4 sm:py-8">
        <div class="hidden items-start justify-between gap-4 sm:flex">
            <div>
                <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('patient.wallet.title') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600">{{ __('patient.wallet.subtitle') }}</flux:text>
            </div>
            <flux:button :href="route('patient.menu')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('patient.empty_state.menu_crumb') }}
            </flux:button>
        </div>

        <div class="rounded-3xl border border-slate-100/80 bg-gradient-to-br from-[#10B981]/10 via-white to-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-[#10B981]/25 sm:p-6 sm:shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient.wallet.available_balance') }}</p>
        <p class="mt-2 text-4xl font-bold tabular-nums text-[#10B981]">
            {{ number_format($this->balance, 2) }}
            <span class="text-2xl">{{ config('currency.sa_riyal_symbol') }}</span>
        </p>
        <flux:text class="mt-3 text-sm text-zinc-600">{{ __('patient.wallet.balance_hint') }}</flux:text>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                    <flux:icon name="arrow-down" class="size-5 text-emerald-600" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase text-zinc-500">{{ __('patient.wallet.month_credited') }}</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-600">
                        +{{ number_format($this->monthlySummary['credited'], 2) }} {{ config('currency.sa_riyal_symbol') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-rose-100">
                    <flux:icon name="arrow-up" class="size-5 text-rose-600" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase text-zinc-500">{{ __('patient.wallet.month_spent') }}</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-rose-600">
                        -{{ number_format($this->monthlySummary['spent'], 2) }} {{ config('currency.sa_riyal_symbol') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-100/80 bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm">
        <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('patient.wallet.transactions_title') }}</flux:heading>

        @if ($this->transactions->isEmpty())
            <flux:text class="mt-4 text-sm text-zinc-500">{{ __('patient.wallet.transactions_empty') }}</flux:text>
        @else
            <div class="mt-4 divide-y divide-zinc-100">
                @foreach ($this->transactions as $transaction)
                    @php
                        $amount = $this->transactionAmount($transaction);
                        $isCredit = $amount >= 0;
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-900">{{ $this->transactionLabel($transaction) }}</p>
                            <p class="text-xs text-zinc-500">{{ $transaction->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y, g:i a') }}</p>
                        </div>
                        <p @class([
                            'shrink-0 text-sm font-bold tabular-nums',
                            'text-emerald-600' => $isCredit,
                            'text-rose-600' => ! $isCredit,
                        ])>
                            {{ $isCredit ? '+' : '' }}{{ number_format($amount, 2) }} {{ config('currency.sa_riyal_symbol') }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    </div>
</div>
