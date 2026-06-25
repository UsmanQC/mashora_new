<?php

use App\Models\Doctor;
use App\Services\DoctorWalletService;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Wallet')] class extends Component
{
    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function getBalanceProperty(): float
    {
        return app(DoctorWalletService::class)->balance($this->doctor());
    }

    /**
     * @return array{earned: float, paid_out: float, balance: float}
     */
    public function getMonthlySummaryProperty(): array
    {
        return app(DoctorWalletService::class)->monthlySummary($this->doctor());
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionsProperty(): Collection
    {
        return app(DoctorWalletService::class)->recentTransactions($this->doctor(), 30);
    }

    public function transactionLabel(Transaction $transaction): string
    {
        return app(DoctorWalletService::class)->transactionLabel($transaction);
    }

    public function transactionAmount(Transaction $transaction): float
    {
        return app(DoctorWalletService::class)->transactionAmountSigned($transaction);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.wallet.title') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('doctor.wallet.subtitle') }}</flux:text>
        </div>
        <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
            {{ __('doctor.auth.back') }}
        </flux:button>
    </div>

    <div class="rounded-2xl border border-[#10B981]/25 bg-gradient-to-br from-[#10B981]/10 to-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __('doctor.wallet.available_balance') }}</p>
        <p class="mt-2 text-4xl font-bold tabular-nums text-[#047857]">
            {{ number_format($this->balance, 2) }}
            <span class="text-2xl">{{ config('currency.sa_riyal_symbol') }}</span>
        </p>
        <flux:text class="mt-3 text-sm text-zinc-600">{{ __('doctor.wallet.balance_hint') }}</flux:text>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-zinc-500">{{ __('doctor.wallet.month_earned') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-600">
                +{{ number_format($this->monthlySummary['earned'], 2) }} {{ config('currency.sa_riyal_symbol') }}
            </p>
        </div>
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-zinc-500">{{ __('doctor.wallet.month_paid_out') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-zinc-700">
                {{ number_format($this->monthlySummary['paid_out'], 2) }} {{ config('currency.sa_riyal_symbol') }}
            </p>
        </div>
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-zinc-500">{{ __('doctor.wallet.invoices_link') }}</p>
            <flux:button :href="route('doctor.settings.invoices')" wire:navigate variant="outline" size="sm" class="mt-2">
                {{ __('doctor.wallet.view_invoices') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('doctor.wallet.transactions_title') }}</flux:heading>

        @if ($this->transactions->isEmpty())
            <flux:text class="mt-4 text-sm text-zinc-500">{{ __('doctor.wallet.transactions_empty') }}</flux:text>
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
