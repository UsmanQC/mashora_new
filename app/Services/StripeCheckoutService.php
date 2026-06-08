<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TemporaryAppointment;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Throwable;

class StripeCheckoutService
{
    public function __construct()
    {
        Stripe::setApiKey((string) config('stripe.secret'));
    }

    public function isConfigured(): bool
    {
        return filled(config('stripe.secret')) && filled(config('stripe.key'));
    }

    public function amountInMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function createBookingSession(TemporaryAppointment $temporaryAppointment, float $amountDue): Session
    {
        return Session::create([
            'mode' => 'payment',
            'success_url' => route('patient.payment.success', ['temporaryAppointment' => $temporaryAppointment->id]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('patient.payment.failed', ['temporaryAppointment' => $temporaryAppointment->id]),
            'client_reference_id' => (string) $temporaryAppointment->id,
            'metadata' => [
                'type' => 'booking',
                'temporary_appointment_id' => (string) $temporaryAppointment->id,
                'user_id' => (string) $temporaryAppointment->user_id,
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower((string) config('stripe.currency', 'sar')),
                    'product_data' => [
                        'name' => __('patient_booking.stripe_product_booking'),
                    ],
                    'unit_amount' => $this->amountInMinorUnits($amountDue),
                ],
                'quantity' => 1,
            ]],
        ]);
    }

    public function createFollowUpSession(Appointment $appointment, float $amountDue): Session
    {
        return Session::create([
            'mode' => 'payment',
            'success_url' => route('patient.follow-up.payment.success', $appointment).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('patient.follow-up.payment.failed', $appointment),
            'client_reference_id' => 'FOLLOWUP-'.$appointment->id,
            'metadata' => [
                'type' => 'follow_up',
                'appointment_id' => (string) $appointment->id,
                'user_id' => (string) $appointment->user_id,
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower((string) config('stripe.currency', 'sar')),
                    'product_data' => [
                        'name' => __('patient_booking.stripe_product_follow_up'),
                    ],
                    'unit_amount' => $this->amountInMinorUnits($amountDue),
                ],
                'quantity' => 1,
            ]],
        ]);
    }

    public function retrieveSession(string $sessionId): ?Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            report($e);

            return null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function isSessionPaid(Session $session): bool
    {
        if (in_array($session->payment_status, ['paid', 'no_payment_required'], true)) {
            return true;
        }

        return isset($session->status)
            && $session->status === 'complete'
            && $session->payment_status !== 'unpaid';
    }

    public function paymentReferenceId(Session $session): string
    {
        $intent = $session->payment_intent ?? null;

        if (is_string($intent) && $intent !== '') {
            return $intent;
        }

        if (is_object($intent) && isset($intent->id)) {
            return (string) $intent->id;
        }

        return (string) $session->id;
    }

    public function sessionBelongsToBooking(Session $session, TemporaryAppointment $temporaryAppointment): bool
    {
        $metadata = $session->metadata ?? null;
        $metaType = is_object($metadata) ? ($metadata->type ?? '') : '';
        $metaTempId = is_object($metadata) ? ($metadata->temporary_appointment_id ?? '') : '';
        $tempId = (string) $temporaryAppointment->id;

        if ($metaType !== 'booking') {
            return false;
        }

        return $metaTempId === $tempId
            || (string) ($session->client_reference_id ?? '') === $tempId;
    }

    public function sessionBelongsToFollowUp(Session $session, Appointment $appointment): bool
    {
        $metadata = $session->metadata ?? null;
        $metaType = is_object($metadata) ? ($metadata->type ?? '') : '';
        $metaAppointmentId = is_object($metadata) ? ($metadata->appointment_id ?? '') : '';
        $appointmentId = (string) $appointment->id;

        if ($metaType !== 'follow_up') {
            return false;
        }

        return $metaAppointmentId === $appointmentId
            || (string) ($session->client_reference_id ?? '') === 'FOLLOWUP-'.$appointmentId;
    }
}
