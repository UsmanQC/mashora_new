<?php

namespace App\Support;

use Bavix\Wallet\Internal\Service\MathServiceInterface;
use Bavix\Wallet\Models\Transaction;

/**
 * Safe transaction amount reads when the related wallet row is missing or soft-deleted.
 */
final class WalletTransactionAmount
{
    public static function absoluteFloat(Transaction $transaction, int $fallbackDecimalPlaces = 2): float
    {
        if (! $transaction->relationLoaded('wallet')) {
            $transaction->load([
                'wallet' => static fn ($query) => $query->withTrashed(),
            ]);
        }

        $wallet = $transaction->wallet;

        if ($wallet !== null) {
            return abs((float) $transaction->amountFloat);
        }

        $decimalPlaces = max(0, $fallbackDecimalPlaces);
        $math = app(MathServiceInterface::class);
        $divisor = $math->powTen($decimalPlaces);

        return abs((float) $math->div((string) $transaction->amount, $divisor, $decimalPlaces));
    }
}
