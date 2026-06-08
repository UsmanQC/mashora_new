<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createCheckoutTemporaryAppointment(User $user, Doctor $doctor, float $total = 200.0): TemporaryAppointment
{
    return TemporaryAppointment::query()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'duration' => 30,
        'extend_at' => now()->addDay()->addMinutes(30)->format('Y-m-d H:i:s'),
        'appointment_for' => 'self',
        'patient_name' => $user->name,
        'patient_phone' => '966500000000',
        'communications' => ['chat'],
        'amount' => $total,
        'discount' => 0,
        'tax' => 0,
        'total' => $total,
        'wallet_amount' => 0,
        'payment_status' => 'unpaid',
    ]);
}

test('checkout auto applies wallet when patient has balance', function (): void {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $user->depositFloat(150.00);

    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $doctor->durations()->attach(30, ['price' => 200.0]);

    $temp = createCheckoutTemporaryAppointment($user, $doctor, 200.0);

    Livewire::actingAs($user)
        ->test('pages::patient.checkout', ['temporaryAppointment' => $temp])
        ->assertSet('useWallet', true)
        ->assertSee(__('patient_booking.use_wallet'), false)
        ->assertSee('150.00', false)
        ->assertSee('50.00', false);
});

test('patient can complete checkout paying fully from wallet', function (): void {
    $user = User::factory()->create(['profile_completed' => true]);
    $user->depositFloat(300.00);

    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved', 'commission' => 20]);
    $doctor->durations()->attach(30, ['price' => 200.0]);

    $temp = createCheckoutTemporaryAppointment($user, $doctor, 200.0);

    Livewire::actingAs($user)
        ->test('pages::patient.checkout', ['temporaryAppointment' => $temp])
        ->call('payWithWalletOnly')
        ->assertRedirect(route('patient.payment.success', ['temporaryAppointment' => $temp->id]));

    $temp->refresh();

    expect($temp->payment_status)->toBe('paid')
        ->and($temp->appointment_id)->not->toBeNull()
        ->and((float) $user->fresh()->balanceFloat)->toBe(100.0);
});

test('patient can toggle wallet off on checkout', function (): void {
    $user = User::factory()->create(['profile_completed' => true]);
    $user->depositFloat(80.00);

    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $doctor->durations()->attach(30, ['price' => 200.0]);

    $temp = createCheckoutTemporaryAppointment($user, $doctor, 200.0);

    Livewire::actingAs($user)
        ->test('pages::patient.checkout', ['temporaryAppointment' => $temp])
        ->set('useWallet', false)
        ->assertSet('useWallet', false);

    expect((float) $temp->fresh()->wallet_amount)->toBe(0.0);
});
