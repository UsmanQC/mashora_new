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

    public function getPendingBalanceProperty(): float
    {
        return app(DoctorWalletService::class)->pendingPayoutBalance($this->doctor());
    }

    public function getTotalBalanceProperty(): float
    {
        return app(DoctorWalletService::class)->totalBalance($this->doctor());
    }

    public function getIncomeTrendPercentProperty(): ?float
    {
        return app(DoctorWalletService::class)->incomeTrendPercent($this->doctor());
    }

    /**
     * @return list<array{key: string, label: string, income: float, is_current: bool, height_percent: float}>
     */
    public function getMonthlyIncomeChartProperty(): array
    {
        $points = app(DoctorWalletService::class)->monthlyIncomeChart($this->doctor());
        $maxIncome = max(1.0, ...array_map(fn (array $point): float => max(0.0, $point['income']), $points));

        return array_map(function (array $point) use ($maxIncome): array {
            $normalized = max(0.0, $point['income']) / $maxIncome;

            return [
                ...$point,
                'height_percent' => max(10.0, round($normalized * 100, 1)),
            ];
        }, $points);
    }

    /**
     * @return array{income: float, refunded: float, refunded_count: int, paid_sessions: int, paid_out: float, balance: float}
     */
    public function getMonthlySummaryProperty(): array
    {
        return app(DoctorWalletService::class)->monthlySummary($this->doctor());
    }

    /**
     * @return array{income: float, refunded: float, refunded_count: int, paid_sessions: int, paid_out: float, balance: float}
     */
    public function getPreviousMonthSummaryProperty(): array
    {
        return app(DoctorWalletService::class)->monthlySummary(
            $this->doctor(),
            now(config('app.timezone'))->subMonth(),
        );
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

    public function getCurrentMonthLabelProperty(): string
    {
        return now(config('app.timezone'))
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    public function getPreviousMonthLabelProperty(): string
    {
        return now(config('app.timezone'))
            ->subMonth()
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    public function transactionDescription(Transaction $transaction): ?string
    {
        $meta = is_array($transaction->meta) ? $transaction->meta : [];
        $metaType = $meta['type'] ?? null;
        $appointmentNumber = $this->transactionAppointmentNumber($transaction);
        $appointmentReference = $appointmentNumber !== null
            ? __('doctor.wallet.appointment_reference', ['number' => $appointmentNumber])
            : null;

        return match ($metaType) {
            'appointment_earning' => $this->earningTransactionDescription($transaction, $meta, $appointmentReference),
            'appointment_refund_reversal' => $this->prefixAppointmentReference(
                $appointmentReference,
                __('doctor.wallet.desc_refund_reversal_detail', [
                    'share' => number_format((float) ($meta['doctor_share'] ?? abs($this->transactionAmount($transaction))), 2),
                ]),
            ),
            'invoice_payout' => __('doctor.wallet.desc_payout'),
            default => null,
        };
    }

    private function prefixAppointmentReference(?string $appointmentReference, string $description): string
    {
        if ($appointmentReference === null) {
            return $description;
        }

        return $appointmentReference.' — '.$description;
    }

    private function earningTransactionDescription(Transaction $transaction, array $meta, ?string $appointmentReference): string
    {
        $appointmentId = (int) ($meta['appointment_id'] ?? 0);
        $amount = number_format($this->transactionAmount($transaction), 2);

        $description = $appointmentId > 0
            && app(DoctorWalletService::class)->appointmentWasRefunded($this->doctor(), $appointmentId)
            ? __('doctor.wallet.desc_earning_later_refunded', ['amount' => $amount])
            : __('doctor.wallet.desc_earning_kept', ['amount' => $amount]);

        return $this->prefixAppointmentReference($appointmentReference, $description);
    }

    public function transactionAppointmentNumber(Transaction $transaction): ?string
    {
        return app(DoctorWalletService::class)->transactionAppointmentNumber($transaction);
    }

    /**
     * @return array{icon: string, icon_bg: string, amount_class: string}
     */
    public function transactionPresentation(Transaction $transaction): array
    {
        $amount = $this->transactionAmount($transaction);
        $meta = is_array($transaction->meta) ? $transaction->meta : [];
        $metaType = $meta['type'] ?? null;

        $amountClass = $amount >= 0 ? 'text-emerald-600' : 'text-rose-600';

        return match ($metaType) {
            'appointment_earning' => [
                'icon' => 'banknotes',
                'icon_bg' => 'bg-emerald-50 text-emerald-600',
                'amount_class' => $amountClass,
            ],
            'appointment_refund_reversal' => [
                'icon' => 'arrow-uturn-left',
                'icon_bg' => 'bg-rose-50 text-rose-600',
                'amount_class' => $amountClass,
            ],
            'invoice_payout' => [
                'icon' => 'arrow-down-tray',
                'icon_bg' => 'bg-sky-50 text-sky-600',
                'amount_class' => $amountClass,
            ],
            default => [
                'icon' => $amount >= 0 ? 'plus-circle' : 'minus-circle',
                'icon_bg' => $amount >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600',
                'amount_class' => $amountClass,
            ],
        };
    }
}; ?>

<div class="relative flex w-full min-h-0 flex-col max-lg:h-full max-lg:min-h-0 max-lg:flex-1 lg:block">
    @include('partials.doctor-luxury-wallet-mobile')

    <div class="hidden space-y-6 lg:block">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.wallet.title') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('doctor.wallet.subtitle') }}</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('doctor.settings.invoices')" wire:navigate variant="outline" size="sm">
                {{ __('doctor.wallet.view_invoices') }}
            </flux:button>
            <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('doctor.auth.back') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-2xl border border-[#10B981]/25 bg-gradient-to-br from-[#10B981]/10 to-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __('doctor.wallet.available_balance') }}</p>
        <p class="mt-2 text-4xl font-bold tabular-nums text-[#047857]">
            {{ number_format($this->balance, 2) }}
            <span class="text-2xl">{{ config('currency.sa_riyal_symbol') }}</span>
        </p>
        <flux:text class="mt-3 text-sm text-zinc-600">{{ __('doctor.wallet.balance_hint') }}</flux:text>
        <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ __('doctor.wallet.balance_formula') }}</p>
    </div>

    <div class="rounded-2xl border border-sky-200/80 bg-sky-50/60 p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700" aria-hidden="true">
                <flux:icon name="information-circle" variant="outline" class="size-5" />
            </span>
            <div class="min-w-0 space-y-2">
                <p class="text-sm font-semibold text-zinc-900">{{ __('doctor.wallet.how_it_works_title') }}</p>
                <ul class="space-y-1.5 text-xs leading-relaxed text-zinc-600">
                    <li>{{ __('doctor.wallet.how_it_works_earning') }}</li>
                    <li>{{ __('doctor.wallet.how_it_works_refund') }}</li>
                    <li>{{ __('doctor.wallet.how_it_works_payout') }}</li>
                    <li>{{ __('doctor.wallet.how_it_works_balance') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <div>
            <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('doctor.wallet.current_period', ['month' => $this->currentMonthLabel]) }}</flux:heading>
            <flux:text class="mt-0.5 text-xs text-zinc-500">{{ __('doctor.wallet.current_period_hint') }}</flux:text>
        </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-[#10B981]/20 bg-gradient-to-br from-[#eef2ff] via-white to-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.month_income') }}</p>
                    @php $monthIncome = $this->monthlySummary['income']; @endphp
                    <p @class([
                        'mt-2 text-3xl font-bold tabular-nums tracking-tight',
                        'text-emerald-700' => $monthIncome >= 0,
                        'text-rose-600' => $monthIncome < 0,
                    ])>
                        {{ $monthIncome >= 0 ? '+' : '' }}{{ number_format($monthIncome, 2) }}
                        <span class="text-base font-semibold text-[#10B981]">{{ config('currency.sa_riyal_symbol') }}</span>
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ __('doctor.wallet.month_income_hint') }}</p>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#10B981]" aria-hidden="true">
                    <flux:icon name="banknotes" variant="outline" class="size-6" />
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-rose-200/70 bg-gradient-to-br from-rose-50 via-white to-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.month_refunded_sessions') }}</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-rose-600">
                        @if ($this->monthlySummary['refunded'] > 0)
                            -{{ number_format($this->monthlySummary['refunded'], 2) }}
                        @else
                            {{ number_format(0, 2) }}
                        @endif
                        <span class="text-base font-semibold text-rose-500">{{ config('currency.sa_riyal_symbol') }}</span>
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500">
                        @if ($this->monthlySummary['refunded_count'] > 0)
                            {{ __('doctor.wallet.month_refunded_sessions_hint', ['count' => $this->monthlySummary['refunded_count']]) }}
                        @else
                            {{ __('doctor.wallet.month_refunded_sessions_empty') }}
                        @endif
                    </p>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-rose-600" aria-hidden="true">
                    <flux:icon name="arrow-uturn-left" variant="outline" class="size-6" />
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-sky-200/70 bg-gradient-to-br from-sky-50 via-white to-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.month_sessions') }}</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                        {{ $this->monthlySummary['paid_sessions'] }}
                        <span class="text-base font-semibold normal-case text-sky-600">{{ __('doctor.wallet.sessions_suffix') }}</span>
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ __('doctor.wallet.month_sessions_hint') }}</p>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600" aria-hidden="true">
                    <flux:icon name="calendar-days" variant="outline" class="size-6" />
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200/70 bg-gradient-to-br from-zinc-50 via-white to-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.month_paid_out') }}</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                        {{ number_format($this->monthlySummary['paid_out'], 2) }}
                        <span class="text-base font-semibold text-zinc-500">{{ config('currency.sa_riyal_symbol') }}</span>
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ __('doctor.wallet.month_paid_out_hint') }}</p>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600" aria-hidden="true">
                    <flux:icon name="arrow-down-tray" variant="outline" class="size-6" />
                </span>
            </div>
        </div>
    </div>
    </div>

    <div class="space-y-3">
        <div>
            <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('doctor.wallet.previous_period', ['month' => $this->previousMonthLabel]) }}</flux:heading>
            <flux:text class="mt-0.5 text-xs text-zinc-500">{{ __('doctor.wallet.previous_period_hint') }}</flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-violet-200/70 bg-gradient-to-br from-violet-50 via-white to-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.previous_month_income') }}</p>
                        @php $previousMonthIncome = $this->previousMonthSummary['income']; @endphp
                        <p @class([
                            'mt-2 text-3xl font-bold tabular-nums tracking-tight',
                            'text-emerald-700' => $previousMonthIncome >= 0,
                            'text-rose-600' => $previousMonthIncome < 0,
                        ])>
                            {{ $previousMonthIncome >= 0 ? '+' : '' }}{{ number_format($previousMonthIncome, 2) }}
                            <span class="text-base font-semibold text-violet-600">{{ config('currency.sa_riyal_symbol') }}</span>
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ __('doctor.wallet.previous_month_income_hint') }}</p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600" aria-hidden="true">
                        <flux:icon name="banknotes" variant="outline" class="size-6" />
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-rose-200/70 bg-gradient-to-br from-rose-50/80 via-white to-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.previous_month_refunded_sessions') }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-rose-600">
                            @if ($this->previousMonthSummary['refunded'] > 0)
                                -{{ number_format($this->previousMonthSummary['refunded'], 2) }}
                            @else
                                {{ number_format(0, 2) }}
                            @endif
                            <span class="text-base font-semibold text-rose-500">{{ config('currency.sa_riyal_symbol') }}</span>
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500">
                            @if ($this->previousMonthSummary['refunded_count'] > 0)
                                {{ __('doctor.wallet.previous_month_refunded_sessions_hint', ['count' => $this->previousMonthSummary['refunded_count']]) }}
                            @else
                                {{ __('doctor.wallet.previous_month_refunded_sessions_empty') }}
                            @endif
                        </p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-rose-600" aria-hidden="true">
                        <flux:icon name="arrow-uturn-left" variant="outline" class="size-6" />
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-sky-200/70 bg-gradient-to-br from-sky-50/80 via-white to-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.previous_month_sessions') }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                            {{ $this->previousMonthSummary['paid_sessions'] }}
                            <span class="text-base font-semibold normal-case text-sky-600">{{ __('doctor.wallet.sessions_suffix') }}</span>
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ __('doctor.wallet.previous_month_sessions_hint') }}</p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600" aria-hidden="true">
                        <flux:icon name="calendar-days" variant="outline" class="size-6" />
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200/70 bg-gradient-to-br from-zinc-50 via-white to-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.wallet.previous_month_paid_out') }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                            {{ number_format($this->previousMonthSummary['paid_out'], 2) }}
                            <span class="text-base font-semibold text-zinc-500">{{ config('currency.sa_riyal_symbol') }}</span>
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ __('doctor.wallet.previous_month_paid_out_hint') }}</p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600" aria-hidden="true">
                        <flux:icon name="arrow-down-tray" variant="outline" class="size-6" />
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('doctor.wallet.transactions_title') }}</flux:heading>
        <flux:text class="mt-1 text-xs text-zinc-500">{{ __('doctor.wallet.transactions_subtitle') }}</flux:text>

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
                            <p class="text-sm font-semibold text-zinc-900">
                                {{ $this->transactionLabel($transaction) }}
                                @if ($appointmentNumber = $this->transactionAppointmentNumber($transaction))
                                    <span class="font-medium text-zinc-500">· {{ $appointmentNumber }}</span>
                                @endif
                            </p>
                            @if ($description = $this->transactionDescription($transaction))
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $description }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-zinc-400">{{ $transaction->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y, g:i a') }}</p>
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
