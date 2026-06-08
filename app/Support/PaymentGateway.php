<?php

namespace App\Support;

final class PaymentGateway
{
    public const DRIVER_STRIPE = 'stripe';

    public const DRIVER_MYFATOORAH = 'myfatoorah';

    public static function driver(): string
    {
        $driver = (string) config('payment.driver', self::DRIVER_MYFATOORAH);

        if (! in_array($driver, [self::DRIVER_STRIPE, self::DRIVER_MYFATOORAH], true)) {
            return self::DRIVER_MYFATOORAH;
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

    public static function isConfigured(): bool
    {
        if (self::isStripe()) {
            return filled(config('stripe.secret')) && filled(config('stripe.key'));
        }

        return filled(config('myfatoorah.api_key'));
    }
}
