<?php

use App\Mail\DoctorAccountApprovedMail;
use App\Mail\DoctorAccountRejectedMail;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('approving a pending doctor sends an approval email', function () {
    Mail::fake();

    $doctor = Doctor::factory()->create([
        'status' => 'pending',
        'email' => 'doctor@example.com',
    ]);

    $doctor->update(['status' => 'approved']);

    Mail::assertSent(DoctorAccountApprovedMail::class, function (DoctorAccountApprovedMail $mail) use ($doctor): bool {
        return $mail->hasTo('doctor@example.com') && $mail->doctor->is($doctor);
    });
});

test('rejecting a pending doctor sends a rejection email with the reason', function () {
    Mail::fake();

    $doctor = Doctor::factory()->create([
        'status' => 'pending',
        'email' => 'doctor@example.com',
    ]);

    $doctor->update([
        'status' => 'rejected',
        'rejection_reason' => 'License number could not be verified.',
    ]);

    Mail::assertNotSent(DoctorAccountApprovedMail::class);
    Mail::assertSent(DoctorAccountRejectedMail::class, function (DoctorAccountRejectedMail $mail) use ($doctor): bool {
        return $mail->hasTo('doctor@example.com')
            && $mail->doctor->is($doctor)
            && $mail->reason === 'License number could not be verified.';
    });
});

test('saving an already approved doctor without status change sends no email', function () {
    Mail::fake();

    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $doctor->update(['about' => 'Updated bio']);

    Mail::assertNothingSent();
});
