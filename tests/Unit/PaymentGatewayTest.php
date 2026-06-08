<?php

use App\Support\PaymentGateway;
use Tests\TestCase;

uses(TestCase::class);

test('payment gateway resolves stripe driver', function () {
    config([
        'payment.driver' => 'stripe',
        'stripe.key' => 'pk_test_example',
        'stripe.secret' => 'sk_test_example',
    ]);

    expect(PaymentGateway::driver())->toBe('stripe')
        ->and(PaymentGateway::isStripe())->toBeTrue()
        ->and(PaymentGateway::isMyFatoorah())->toBeFalse()
        ->and(PaymentGateway::isConfigured())->toBeTrue();
});

test('payment gateway falls back to myfatoorah for unknown driver', function () {
    config([
        'payment.driver' => 'unknown',
        'myfatoorah.api_key' => 'test-key',
    ]);

    expect(PaymentGateway::driver())->toBe('myfatoorah')
        ->and(PaymentGateway::isMyFatoorah())->toBeTrue();
});
