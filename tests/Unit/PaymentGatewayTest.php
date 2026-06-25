<?php

use App\Services\HyperpayCheckoutService;
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
        ->and(PaymentGateway::isHyperPay())->toBeFalse()
        ->and(PaymentGateway::isMyFatoorah())->toBeFalse()
        ->and(PaymentGateway::isConfigured())->toBeTrue();
});

test('payment gateway resolves hyperpay driver', function () {
    config([
        'payment.driver' => 'hyperpay',
        'hyperpay.token' => 'test-token',
        'hyperpay.entity_id_b2c' => 'entity-test',
    ]);

    expect(PaymentGateway::driver())->toBe('hyperpay')
        ->and(PaymentGateway::isHyperPay())->toBeTrue()
        ->and(PaymentGateway::isStripe())->toBeFalse()
        ->and(PaymentGateway::isMyFatoorah())->toBeFalse()
        ->and(PaymentGateway::isConfigured())->toBeTrue();
});

test('payment gateway falls back to hyperpay for unknown driver', function () {
    config([
        'payment.driver' => 'unknown',
        'hyperpay.token' => 'test-token',
        'hyperpay.entity_id_b2c' => 'entity-test',
    ]);

    expect(PaymentGateway::driver())->toBe('hyperpay')
        ->and(PaymentGateway::isHyperPay())->toBeTrue();
});

test('hyperpay payment status parser recognizes success and failure codes', function () {
    $service = app(HyperpayCheckoutService::class);

    expect($service->getPaymentStatus('000.000.000'))->toBe('success')
        ->and($service->getPaymentStatus('000.200.000'))->toBe('processing')
        ->and($service->getPaymentStatus('800.400.500'))->toBe('pending')
        ->and($service->getPaymentStatus('100.380.401'))->toBe('failed');
});
