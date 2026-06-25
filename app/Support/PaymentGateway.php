<?php

namespace App\Support;

final class PaymentGateway
{
    public const DRIVER_STRIPE = 'stripe';

    public const DRIVER_MYFATOORAH = 'myfatoorah';

    public const DRIVER_HYPERPAY = 'hyperpay';

    public static function driver(): string
    {
        $driver = (string) config('payment.driver', self::DRIVER_HYPERPAY);

        if (! in_array($driver, [self::DRIVER_STRIPE, self::DRIVER_MYFATOORAH, self::DRIVER_HYPERPAY], true)) {
            return self::DRIVER_HYPERPAY;
        }

        return $driver;
    }

    public static function isStripe(): bool
    {
        return self::driver() === self::DRIVER_STRIPE;
    }

    public static function isMyFatoorah(): bool
    {
        return self::driver() === self::DRIVER_MYFATOORAH;
    }

    public static function isHyperPay(): bool
    {
        return self::driver() === self::DRIVER_HYPERPAY;
    }

    public static function isConfigured(): bool
    {
        if (self::isStripe()) {
            return filled(config('stripe.secret')) && filled(config('stripe.key'));
        }

        if (self::isHyperPay()) {
            return filled(config('hyperpay.token')) && filled(config('hyperpay.entity_id_b2c'));
        }

        return filled(config('myfatoorah.api_key'));
    }
}
