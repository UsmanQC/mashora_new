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
     * @return array{earned: float, paid_out: float, balance: float}
     */
    public function monthlySummary(Doctor $doctor, ?CarbonInterface $month = null): array
    {
        $month ??= now(config('app.timezone'));
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $earned = $this->sumTransactions($doctor, TransactionType::Deposit, $start, $end);
        $paidOut = $this->sumTransactions($doctor, TransactionType::Withdraw, $start, $end);

        return [
            'earned' => $earned,
            'paid_out' => $paidOut,
            'balance' => $this->balance($doctor->fresh() ?? $doctor),
        ];
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

            if ($balance > 0) {
                $doctor->withdrawFloat($balance, [
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
