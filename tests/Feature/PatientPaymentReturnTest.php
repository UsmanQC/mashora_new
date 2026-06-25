<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('payment success redirects to checkout when hyperpay is not configured', function () {
    config([
        'payment.driver' => 'hyperpay',
        'hyperpay.token' => '',
        'hyperpay.entity_id_b2c' => '',
        'stripe.key' => '',
        'stripe.secret' => '',
    ]);

    $user = User::factory()->create(['profile_completed' => true]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Dr Test',
        'name_ar' => 'د',
        'status' => 'approved',
        'spoken_languages' => 'ar',
        'gender' => 'male',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    $scheduledAt = Carbon::parse('2026-05-05 12:15:00')->format('Y-m-d H:i:s');
    $extendAt = Carbon::parse('2026-05-05 12:15:00')->addMinutes(15)->format('Y-m-d H:i:s');

    $temp = TemporaryAppointment::create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => $scheduledAt,
        'appointment_date' => '2026-05-05',
        'start_time' => '12:15:00',
        'end_time' => '12:30:00',
        'duration' => 15,
        'extend_at' => $extendAt,
        'appointment_for' => 'self',
        'patient_name' => 'Patient',
        'patient_phone' => '966500000000',
        'communications' => ['chat'],
        'amount' => 100,
        'discount' => 0,
        'tax' => 0,
        'total' => 100,
        'appointment_type' => 'regular',
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($user)
        ->get(route('patient.payment.success', ['temporaryAppointment' => $temp->id]))
        ->assertRedirect(route('patient.checkout', $temp));
});
