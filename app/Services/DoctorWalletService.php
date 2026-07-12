<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Support\WalletTransactionAmount;
use Bavix\Wallet\Enums\TransactionType;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Services\CastServiceInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Doctor earnings wallet (bavix/laravel-wallet): session credits, refund reversals,
 * and zeroing balance when Awaan marks a monthly invoice as paid.
 */
final class DoctorWalletService
{
    public function balance(Doctor $doctor): float
    {
        $this->ensureWallet($doctor);

        return round((float) $doctor->balanceFloat, 2);
    }

    /**
     * @return array{
     *     income: float,
     *     refunded: float,
     *     refunded_count: int,
     *     paid_sessions: int,
     *     paid_out: float,
     *     balance: float
     * }
     */
    public function monthlySummary(Doctor $doctor, ?CarbonInterface $month = null): array
    {
        $month ??= now(config('app.timezone'));
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $earnings = $this->periodTransactionsByMetaType($doctor, TransactionType::Deposit, 'appointment_earning', $start, $end);
        $reversals = $this->periodTransactionsByMetaType($doctor, TransactionType::Withdraw, 'appointment_refund_reversal', $start, $end);

        $earned = round($this->sumTransactionAmounts($earnings, $doctor), 2);
        $refunded = round($this->sumTransactionAmounts($reversals, $doctor), 2);
        $paidOut = $this->sumTransactionsByMetaType($doctor, TransactionType::Withdraw, 'invoice_payout', $start, $end);

        return [
            'income' => round($earned - $refunded, 2),
            'refunded' => $refunded,
            'refunded_count' => $reversals->count(),
            'paid_sessions' => $earnings->count(),
            'paid_out' => $paidOut,
            'balance' => $this->balance($doctor->fresh() ?? $doctor),
        ];
    }

    public function pendingPayoutBalance(Doctor $doctor): float
    {
        return round((float) $doctor->invoices()
            ->where('payment_status', '!=', 'paid')
            ->sum('doctor_share'), 2);
    }

    public function totalBalance(Doctor $doctor): float
    {
        return round($this->balance($doctor) + $this->pendingPayoutBalance($doctor), 2);
    }

    public function incomeTrendPercent(Doctor $doctor, ?CarbonInterface $month = null): ?float
    {
        $month ??= now(config('app.timezone'));
        $current = $this->monthlySummary($doctor, $month)['income'];
        $previous = $this->monthlySummary($doctor, $month->copy()->subMonth())['income'];

        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 0);
    }

    /**
     * @return list<array{key: string, label: string, income: float, is_current: bool}>
     */
    public function monthlyIncomeChart(Doctor $doctor, int $months = 6): array
    {
        $timezone = config('app.timezone');
        $locale = app()->getLocale();
        $points = [];

        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $month = now($timezone)->copy()->subMonths($offset)->startOfMonth();
            $summary = $this->monthlySummary($doctor, $month);

            $points[] = [
                'key' => $month->format('Y-m'),
                'label' => $month->locale($locale)->translatedFormat('M'),
                'income' => $summary['income'],
                'is_current' => $offset === 0,
            ];
        }

        return $points;
    }

    public function completedAppointmentsCount(
        Doctor $doctor,
        ?CarbonInterface $start = null,
        ?CarbonInterface $end = null,
    ): int {
        $timezone = config('app.timezone');
        $start ??= now($timezone)->startOfMonth();
        $end ??= now($timezone)->endOfMonth();

        return $doctor->appointments()
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $end->toDateString())
            ->count();
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function recentTransactions(Doctor $doctor, int $limit = 25): Collection
    {
        $this->ensureWallet($doctor);

        return $this->transactionsQuery($doctor)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function transactionLabel(Transaction $transaction): string
    {
        $metaType = is_array($transaction->meta) ? ($transaction->meta['type'] ?? null) : null;

        return match ($metaType) {
            'appointment_earning' => __('doctor.wallet.type_earning'),
            'appointment_refund_reversal' => __('doctor.wallet.type_refund_reversal'),
            'invoice_payout' => __('doctor.wallet.type_payout'),
            default => $transaction->type === TransactionType::Deposit
                ? __('doctor.wallet.type_credit')
                : __('doctor.wallet.type_debit'),
        };
    }

    public function transactionAmountSigned(Transaction $transaction): float
    {
        $amount = round(
            WalletTransactionAmount::absoluteFloat($transaction, $this->decimalPlaces($transaction)),
            2,
        );

        return $transaction->type === TransactionType::Deposit ? $amount : -$amount;
    }

    public function transactionAppointmentNumber(Transaction $transaction): ?string
    {
        $meta = is_array($transaction->meta) ? $transaction->meta : [];

        $appointmentNumber = $meta['appointment_number'] ?? null;

        return is_string($appointmentNumber) && $appointmentNumber !== ''
            ? $appointmentNumber
            : null;
    }

    public function appointmentWasRefunded(Doctor $doctor, int $appointmentId): bool
    {
        $this->ensureWallet($doctor);

        return $this->transactionsQuery($doctor)
            ->where('type', TransactionType::Withdraw)
            ->where('confirmed', true)
            ->get()
            ->contains(function (Transaction $transaction) use ($appointmentId): bool {
                $meta = is_array($transaction->meta) ? $transaction->meta : [];

                return ($meta['type'] ?? null) === 'appointment_refund_reversal'
                    && (int) ($meta['appointment_id'] ?? 0) === $appointmentId;
            });
    }

    /**
     * Pay out the doctor's current wallet balance for this invoice (balance → 0).
     * Idempotent via {@see Invoice::$wallet_settled_at}.
     */
    public function settleInvoicePayout(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('doctor');
        $doctor = $invoice->doctor;

        if (! $doctor instanceof Doctor) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice, $doctor): Invoice {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->first();

            if ($invoice === null) {
                return $invoice;
            }

            if ($invoice->wallet_settled_at !== null) {
                return $invoice;
            }

            $doctor->refresh();
            $balance = $this->balance($doctor);
            $payoutAmount = min($balance, round((float) $invoice->doctor_share, 2));

            if ($payoutAmount > 0) {
                $doctor->withdrawFloat($payoutAmount, [
                    'type' => 'invoice_payout',
                    'invoice_id' => $invoice->id,
                    'invoice_reference' => $invoice->reference,
                ]);
            }

            $invoice->forceFill([
                'payment_status' => 'paid',
                'paid_at' => $invoice->paid_at ?? now(),
                'wallet_settled_at' => now(),
            ])->save();

            return $invoice->fresh();
        });
    }

    public function ensureWallet(Doctor $doctor): void
    {
        app(CastServiceInterface::class)->getWallet($doctor, save: true);
    }

    private function sumTransactionsByMetaType(
        Doctor $doctor,
        TransactionType $type,
        string $metaType,
        CarbonInterface $start,
        CarbonInterface $end,
    ): float {
        return round($this->sumTransactionAmounts(
            $this->periodTransactionsByMetaType($doctor, $type, $metaType, $start, $end),
            $doctor,
        ), 2);
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function periodTransactionsByMetaType(
        Doctor $doctor,
        TransactionType $type,
        string $metaType,
        CarbonInterface $start,
        CarbonInterface $end,
    ): Collection {
        $this->ensureWallet($doctor);

        return $this->transactionsQuery($doctor)
            ->where('type', $type)
            ->where('confirmed', true)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->filter(function (Transaction $transaction) use ($metaType): bool {
                $meta = is_array($transaction->meta) ? $transaction->meta : [];

                return ($meta['type'] ?? null) === $metaType;
            })
            ->values();
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function sumTransactionAmounts(Collection $transactions, Doctor $doctor): float
    {
        return (float) $transactions->sum(
            fn (Transaction $transaction): float => $this->transactionAbsoluteAmount($transaction, $doctor),
        );
    }

    private function transactionAbsoluteAmount(Transaction $transaction, Doctor $doctor): float
    {
        $decimalPlaces = (int) ($doctor->wallet?->decimal_places ?? $this->decimalPlaces($transaction));

        return WalletTransactionAmount::absoluteFloat($transaction, $decimalPlaces);
    }

    private function sumTransactions(
        Doctor $doctor,
        TransactionType $type,
        CarbonInterface $start,
        CarbonInterface $end,
    ): float {
        $this->ensureWallet($doctor);

        $decimalPlaces = (int) ($doctor->wallet?->decimal_places ?? 2);

        return round((float) $this->transactionsQuery($doctor)
            ->where('type', $type)
            ->where('confirmed', true)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->sum(fn (Transaction $transaction): float => WalletTransactionAmount::absoluteFloat(
                $transaction,
                $decimalPlaces,
            )), 2);
    }

    /**
     * @return Builder<Transaction>
     */
    private function transactionsQuery(Doctor $doctor): Builder
    {
        return Transaction::query()
            ->with(['wallet' => static fn ($query) => $query->withTrashed()])
            ->where('payable_type', $doctor::class)
            ->where('payable_id', $doctor->id);
    }

    private function decimalPlaces(Transaction $transaction): int
    {
        $transaction->loadMissing([
            'wallet' => static fn ($query) => $query->withTrashed(),
        ]);

        return (int) ($transaction->wallet?->decimal_places ?? 2);
    }
}
