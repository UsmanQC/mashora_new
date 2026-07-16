<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Services\MyFatoorahInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('confirm and pay redirects to myfatoorah and skips checkout page', function () {
    config([
        'payment.driver' => 'myfatoorah',
        'myfatoorah.api_key' => 'test-api-key',
    ]);

    $this->mock(MyFatoorahInvoiceService::class, function ($mock): void {
        $mock->shouldReceive('createBookingInvoice')
            ->once()
            ->andReturn([
                'invoice_url' => 'https://demo.myfatoorah.com/pay/test-invoice',
                'invoice_id' => '9911',
            ]);
    });

    $user = User::factory()->create([
        'profile_completed' => true,
        'name' => 'Patient Test',
        'phone' => '966500111222',
    ]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $doctor->durations()->attach(15, ['price' => 200.0]);

    Livewire::actingAs($user)
        ->withQueryParams([
            'date' => '2026-05-05',
            'time' => '12:15',
            'duration' => 15,
        ])
        ->test('pages::patient.book-appointments', ['doctor' => $doctor])
        ->call('submitBooking')
        ->assertRedirect('https://demo.myfatoorah.com/pay/test-invoice');

    expect(TemporaryAppointment::query()->where('user_id', $user->id)->value('payment_invoice_url'))
        ->toBe('https://demo.myfatoorah.com/pay/test-invoice');
});

test('confirm and pay still goes to checkout when gateway is not myfatoorah', function () {
    config(['payment.driver' => 'hyperpay']);

    $user = User::factory()->create([
        'profile_completed' => true,
        'name' => 'Patient Test',
        'phone' => '966500111222',
    ]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $doctor->durations()->attach(15, ['price' => 200.0]);

    $component = Livewire::actingAs($user)
        ->withQueryParams([
            'date' => '2026-05-05',
            'time' => '12:15',
            'duration' => 15,
        ])
        ->test('pages::patient.book-appointments', ['doctor' => $doctor])
        ->call('submitBooking');

    $temp = TemporaryAppointment::query()->where('user_id', $user->id)->first();

    expect($temp)->not->toBeNull();

    $component->assertRedirect(route('patient.checkout', $temp));
});
