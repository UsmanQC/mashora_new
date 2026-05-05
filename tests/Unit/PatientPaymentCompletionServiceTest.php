<?php

use App\Models\Appointment;
use App\Services\PatientPaymentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('generate appointment number is unique and formatted', function () {
    $a = PatientPaymentCompletionService::generateAppointmentNumber();
    $b = PatientPaymentCompletionService::generateAppointmentNumber();

    expect($a)->toStartWith('APP')->not->toBe($b);
    expect(Appointment::query()->where('appointment_number', $a)->exists())->toBeFalse();
});
