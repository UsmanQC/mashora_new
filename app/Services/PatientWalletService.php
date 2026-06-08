<?php

namespace App\Services;

use App\Models\User;
use App\Support\WalletTransactionAmount;
use Bavix\Wallet\Enums\TransactionType;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Services\CastServiceInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Patient wallet (bavix/laravel-wallet): refunds, booking payments, and balance at checkout.
 */
final class PatientWalletService
{
    public function balance(User $patient): float
    {
        $this->ensureWallet($patient);

        return round((float) $patient->balanceFloat, 2);
    }

    /**
     * @return array{credited: float, spent: float, balance: float}
     */
    public function monthlySummary(User $patient, ?CarbonInterface $month = null): array
    {
        $month ??= now(config('app.timezone'));
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $credited = $this->sumTransactions($patient, TransactionType::Deposit, $start, $end);
        $spent = $this->sumTransactions($patient, TransactionType::Withdraw, $start, $end);

        return [
            'credited' => $credited,
            'spent' => $spent,
            'balance' => $this->balance($patient->fresh() ?? $patient),
        ];
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function recentTransactions(User $patient, int $limit = 30): Collection
    {
        $this->ensureWallet($patient);

        return $this->transactionsQuery($patient)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function transactionLabel(Transaction $transaction): string
    {
        $metaType = is_array($transaction->meta) ? ($transaction->meta['type'] ?? null) : null;

        return match ($metaType) {
            'appointment_refund' => __('patient.wallet.type_refund'),
            'appointment_payment' => __('patient.wallet.type_payment'),
            default => $transaction->type === TransactionType::Deposit
                ? __('patient.wallet.type_credit')
                : __('patient.wallet.type_debit'),
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

    public function ensureWallet(User $patient): void
    {
        app(CastServiceInterface::class)->getWallet($patient, save: true);
    }

    private function sumTransactions(
        User $patient,
        TransactionType $type,
        CarbonInterface $start,
        CarbonInterface $end,
    ): float {
        $this->ensureWallet($patient);

        $decimalPlaces = (int) ($patient->wallet?->decimal_places ?? 2);

        return round((float) $this->transactionsQuery($patient)
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
    private function transactionsQuery(User $patient): Builder
    {
        return Transaction::query()
            ->with(['wallet' => static fn ($query) => $query->withTrashed()])
            ->where('payable_type', $patient::class)
            ->where('payable_id', $patient->id);
    }

    private function decimalPlaces(Transaction $transaction): int
    {
        $transaction->loadMissing([
            'wallet' => static fn ($query) => $query->withTrashed(),
        ]);

        return (int) ($transaction->wallet?->decimal_places ?? 2);
    }
}
