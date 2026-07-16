<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Services\MyFatoorahRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('it sends make refund request and returns refund invoice id', function () {
    config([
        'myfatoorah.api_key' => 'test-key',
        'myfatoorah.is_test' => true,
    ]);

    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'payment_invoice_id' => '123456',
        'total' => 150.00,
    ]);

    Http::fake([
        'https://apitest.myfatoorah.com/v2/MakeRefund' => Http::response([
            'IsSuccess' => true,
            'Message' => 'Success',
            'Data' => [
                'RefundInvoiceId' => 'RF-1001',
            ],
        ], 200),
    ]);

    $result = app(MyFatoorahRefundService::class)->refundAppointment($appointment, 75.00, 'Admin test');

    expect($result['refund_invoice_id'])->toBe('RF-1001')
        ->and($result['response'])->toBeArray();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->url() === 'https://apitest.myfatoorah.com/v2/MakeRefund'
            && $data['KeyType'] === 'InvoiceId'
            && $data['Key'] === '123456'
            && (float) $data['Amount'] === 75.00;
    });
});

test('it throws validation error when appointment does not have payment invoice id', function () {
    config([
        'myfatoorah.api_key' => 'test-key',
        'myfatoorah.is_test' => true,
    ]);

    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'payment_invoice_id' => null,
    ]);

    expect(fn () => app(MyFatoorahRefundService::class)->refundAppointment($appointment, 50))
        ->toThrow(ValidationException::class);
});
