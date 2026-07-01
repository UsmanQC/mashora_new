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
        'hyperpay.entity_mode' => 'b2b',
        'hyperpay.entity_id_b2b' => 'entity-b2b-test',
    ]);

    expect(PaymentGateway::driver())->toBe('hyperpay')
        ->and(PaymentGateway::isHyperPay())->toBeTrue()
        ->and(PaymentGateway::isStripe())->toBeFalse()
        ->and(PaymentGateway::isMyFatoorah())->toBeFalse()
        ->and(PaymentGateway::isConfigured())->toBeTrue();
});

test('hyperpay resolves b2b entity id by default', function () {
    config([
        'hyperpay.entity_mode' => 'b2b',
        'hyperpay.entity_id_b2b' => 'b2b-entity-id',
        'hyperpay.entity_id_b2c' => 'b2c-entity-id',
    ]);

    expect(HyperpayCheckoutService::configuredEntityId())->toBe('b2b-entity-id');
});

test('hyperpay resolves b2c entity id when mode is b2c', function () {
    config([
        'hyperpay.entity_mode' => 'b2c',
        'hyperpay.entity_id_b2b' => 'b2b-entity-id',
        'hyperpay.entity_id_b2c' => 'b2c-entity-id',
    ]);

    expect(HyperpayCheckoutService::configuredEntityId())->toBe('b2c-entity-id');
});

test('payment gateway falls back to hyperpay for unknown driver', function () {
    config([
        'payment.driver' => 'unknown',
        'hyperpay.token' => 'test-token',
        'hyperpay.entity_mode' => 'b2b',
        'hyperpay.entity_id_b2b' => 'entity-b2b-test',
    ]);

    expect(PaymentGateway::driver())->toBe('hyperpay')
        ->and(PaymentGateway::isHyperPay())->toBeTrue();
});

test('hyperpay payment status url uses checkout endpoint like zaaerlite', function () {
    config([
        'hyperpay.env' => 'test',
    ]);

    $service = app(HyperpayCheckoutService::class);

    $url = $service->paymentStatusUrl(
        checkoutId: 'checkout-abc',
        entityId: 'entity-test',
    );

    expect($url)->toBe('https://eu-test.oppwa.com/v1/checkouts/checkout-abc/payment?entityId=entity-test');
});

test('hyperpay merchant transaction id uses short zaaerlite-style reference', function () {
    $service = app(HyperpayCheckoutService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateMerchantTransactionId');
    $method->setAccessible(true);

    $transactionId = $method->invoke($service, 'BOOK', '7c3ce1a6-f3b0-439e-983b-39923272ea28');

    expect($transactionId)
        ->toStartWith('MSH_BOOK_')
        ->and($transactionId)->not->toContain('-')
        ->and(strlen($transactionId))->toBeLessThanOrEqual(64);
});

test('hyperpay payment status parser recognizes success and failure codes', function () {
    $service = app(HyperpayCheckoutService::class);

    expect($service->getPaymentStatus('000.000.000'))->toBe('success')
        ->and($service->getPaymentStatus('000.200.000'))->toBe('processing')
        ->and($service->getPaymentStatus('800.400.500'))->toBe('pending')
        ->and($service->getPaymentStatus('100.380.401'))->toBe('failed');
});
