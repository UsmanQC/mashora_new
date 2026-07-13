@php
    $currency = config('currency.sa_riyal_symbol') ?: 'SAR';
    $monthIncome = $this->monthlySummary['income'];
    $trend = $this->incomeTrendPercent;
    $totalBalance = $this->totalBalance;
    $availableBalance = $this->balance;
    $pendingBalance = $this->pendingBalance;
@endphp

<div
    class="doctor-luxury-wallet relative flex h-svh max-h-svh min-h-0 flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-wallet"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="mb-1 text-[0.6875rem] font-medium text-slate-500">
                    {{ $this->currentMonthLabel }}
                </p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ __('doctor.wallet.title') }}
                </h1>
            </div>
            <a
                href="{{ route('doctor.settings.invoices') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-[#047857] shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition-colors hover:text-[#059669]"
                aria-label="{{ __('doctor.wallet.view_invoices') }}"
            >
                <flux:icon name="document-text" variant="outline" class="size-[1.125rem]" />
            </a>
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-5 overflow-y-auto overscroll-y-auto px-5 pb-[calc(7rem+env(safe-area-inset-bottom))] pt-1">
        <section
            class="doctor-wallet-balance-card overflow-hidden rounded-3xl bg-gradient-to-br from-[#047857] via-[#059669] to-[#10B981] p-5 shadow-[0_8px_30px_-4px_rgba(4,120,87,0.35)]"
            data-test="doctor-wallet-balance-card"
        >
            <p class="text-[0.6875rem] font-semibold uppercase tracking-wider text-white/80">
                {{ __('doctor.wallet.mobile_total_balance') }}
            </p>
            <p
                class="doctor-wallet-balance-card__amount mt-2 text-4xl font-bold tabular-nums tracking-tight text-white"
                data-test="doctor-wallet-total-balance"
                dir="ltr"
            >
                <span>{{ number_format($totalBalance, 2, '.', ',') }}</span>
                <span class="text-2xl font-semibold">{{ $currency }}</span>
            </p>

            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-white/15 pt-4">
                <div>
                    <dt class="text-[0.625rem] font-medium uppercase tracking-wide text-white/70">
                        {{ __('doctor.wallet.mobile_available') }}
                    </dt>
                    <dd
                        class="doctor-wallet-balance-card__amount mt-1 text-lg font-bold tabular-nums text-white"
                        data-test="doctor-wallet-available-balance"
                        dir="ltr"
                    >
                        <span>{{ number_format($availableBalance, 2, '.', ',') }}</span>
                        <span class="text-sm font-semibold">{{ $currency }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-[0.625rem] font-medium uppercase tracking-wide text-white/70">
                        {{ __('doctor.wallet.mobile_pending') }}
                    </dt>
                    <dd
                        class="doctor-wallet-balance-card__amount mt-1 text-lg font-bold tabular-nums text-white"
                        data-test="doctor-wallet-pending-balance"
                        dir="ltr"
                    >
                        <span>{{ number_format($pendingBalance, 2, '.', ',') }}</span>
                        <span class="text-sm font-semibold">{{ $currency }}</span>
                    </dd>
                </div>
            </dl>

            <a
                href="{{ route('doctor.settings.invoices') }}"
                wire:navigate
                class="mt-5 flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-white px-4 text-sm font-bold text-[#047857] shadow-sm transition hover:bg-white/95 active:scale-[0.98]"
            >
                <flux:icon name="arrow-up-tray" variant="mini" class="size-5" />
                {{ __('doctor.wallet.mobile_withdraw') }}
            </a>
        </section>

        <section data-test="doctor-wallet-income-chart">
            <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                {{ __('doctor.wallet.mobile_monthly_income') }}
            </h2>

            <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-2xl font-bold tabular-nums text-slate-900" dir="ltr">
                            {{ number_format($monthIncome, 2, '.', ',') }}
                            <span class="text-base font-semibold text-slate-500">{{ $currency }}</span>
                        </p>
                        <p class="mt-0.5 text-[0.6875rem] text-slate-500">
                            {{ __('doctor.wallet.current_period', ['month' => $this->currentMonthLabel]) }}
                        </p>
                    </div>
                    @if ($trend !== null)
                        <span @class([
                            'inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold tabular-nums',
                            'bg-emerald-50 text-emerald-700' => $trend >= 0,
                            'bg-rose-50 text-rose-700' => $trend < 0,
                        ])>
                            <flux:icon
                                :name="$trend >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'"
                                variant="mini"
                                class="size-3.5"
                            />
                            {{ $trend >= 0 ? '+' : '' }}{{ number_format($trend, 0) }}%
                        </span>
                    @endif
                </div>

                <div
                    class="doctor-wallet-income-chart flex items-end justify-between gap-2"
                    role="img"
                    aria-label="{{ __('doctor.wallet.mobile_chart_aria') }}"
                >
                    @foreach ($this->monthlyIncomeChart as $point)
                        @php
                            $barHeightPx = max(8, (int) round(($point['height_percent'] / 100) * 112));
                        @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                            <div class="relative h-28 w-full max-w-[2.25rem]">
                                <div
                                    class="doctor-wallet-income-chart__bar absolute inset-x-0 bottom-0 mx-auto w-full rounded-full transition-all"
                                    style="height: {{ $barHeightPx }}px"
                                    @class([
                                        'bg-[#047857]' => $point['is_current'],
                                        'bg-[#10B981]/35' => ! $point['is_current'],
                                    ])
                                    title="{{ $point['label'] }}: {{ number_format($point['income'], 2, '.', ',') }} {{ $currency }}"
                                ></div>
                            </div>
                            <span @class([
                                'text-[0.625rem] font-semibold tabular-nums',
                                'text-[#047857]' => $point['is_current'],
                                'text-slate-400' => ! $point['is_current'],
                            ])>
                                {{ $point['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                {{ __('doctor.wallet.mobile_month_snapshot') }}
            </h2>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                    <p class="text-xl font-bold tabular-nums text-slate-900">{{ $this->monthlySummary['paid_sessions'] }}</p>
                    <p class="mt-0.5 text-[0.6875rem] text-slate-500">{{ __('doctor.wallet.month_sessions') }}</p>
                </div>
                <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                    <p class="text-xl font-bold tabular-nums text-slate-900" dir="ltr">
                        {{ number_format($this->monthlySummary['paid_out'], 2, '.', ',') }}
                        <span class="text-sm font-semibold text-slate-500">{{ $currency }}</span>
                    </p>
                    <p class="mt-0.5 text-[0.6875rem] text-slate-500">{{ __('doctor.wallet.month_paid_out') }}</p>
                </div>
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                        {{ __('doctor.wallet.transactions_title') }}
                    </h2>
                </div>
            </div>

            @if ($this->transactions->isEmpty())
                <div class="rounded-3xl border border-slate-100 bg-white px-6 py-10 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                    <p class="text-sm text-slate-500">{{ __('doctor.wallet.transactions_empty') }}</p>
                </div>
            @else
                <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                    @foreach ($this->transactions->take(8) as $transaction)
                        @php
                            $amount = $this->transactionAmount($transaction);
                            $presentation = $this->transactionPresentation($transaction);
                        @endphp
                        <div
                            wire:key="doctor-wallet-tx-{{ $transaction->id }}"
                            @class([
                                'flex items-start gap-3 px-4 py-3.5',
                                'border-b border-slate-100' => ! $loop->last,
                            ])
                        >
                            <span @class(['flex size-10 shrink-0 items-center justify-center rounded-xl', $presentation['icon_bg']])>
                                <flux:icon :name="$presentation['icon']" variant="outline" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ $this->transactionLabel($transaction) }}
                                </p>
                                <time class="mt-1 block text-[0.625rem] font-medium text-slate-400">
                                    {{ $transaction->created_at?->timezone(config('app.timezone'))->diffForHumans(short: true) }}
                                </time>
                            </div>
                            <p @class(['shrink-0 text-sm font-bold tabular-nums', $presentation['amount_class']])>
                                {{ $amount >= 0 ? '+' : '' }}{{ number_format($amount, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</div>
