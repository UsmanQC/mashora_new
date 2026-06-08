<?php

use App\Models\TemporaryAppointment;
use App\Services\StripeCheckoutService;
use Stripe\Checkout\Session as StripeSession;
use Tests\TestCase;

uses(TestCase::class);

test('stripe amount converts sar to minor units', function () {
    $service = new StripeCheckoutService;

    expect($service->amountInMinorUnits(100.0))->toBe(10000)
        ->and($service->amountInMinorUnits(99.99))->toBe(9999)
        ->and($service->amountInMinorUnits(0.5))->toBe(50);
});

test('stripe session paid detection accepts complete checkout sessions', function () {
    $service = new StripeCheckoutService;

    $paid = StripeSession::constructFrom(['payment_status' => 'paid', 'status' => 'complete']);
    $complete = StripeSession::constructFrom(['payment_status' => 'paid', 'status' => 'complete']);
    $unpaid = StripeSession::constructFrom(['payment_status' => 'unpaid', 'status' => 'open']);

    expect($service->isSessionPaid($paid))->toBeTrue()
        ->and($service->isSessionPaid($complete))->toBeTrue()
        ->and($service->isSessionPaid($unpaid))->toBeFalse();
});

test('stripe payment reference id uses payment intent when present', function () {
    $service = new StripeCheckoutService;

    $session = StripeSession::constructFrom([
        'id' => 'cs_test_abc',
        'payment_intent' => 'pi_test_abc',
    ]);

    expect($service->paymentReferenceId($session))->toBe('pi_test_abc');
});

test('stripe booking session ownership matches metadata or client reference', function () {
    $service = new StripeCheckoutService;

    $temp = new TemporaryAppointment;
    $temp->id = 'caaaf2f4-53f7-4d94-b096-9b5f52d7f8b3';

    $session = StripeSession::constructFrom([
        'client_reference_id' => 'caaaf2f4-53f7-4d94-b096-9b5f52d7f8b3',
        'metadata' => ['type' => 'booking', 'temporary_appointment_id' => 'caaaf2f4-53f7-4d94-b096-9b5f52d7f8b3'],
    ]);

    expect($service->sessionBelongsToBooking($session, $temp))->toBeTrue();
});
