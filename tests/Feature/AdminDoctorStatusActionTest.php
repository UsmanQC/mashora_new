<?php

use App\Filament\Resources\Doctors\Pages\ListDoctors;
use App\Models\Admin;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can change doctor status from doctors table action', function () {
    $admin = Admin::factory()->create();

    $doctor = Doctor::factory()->create([
        'status' => 'pending',
        'rejection_reason' => null,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListDoctors::class)
        ->callTableAction('changeStatus', $doctor, [
            'status' => 'approved',
        ])
        ->assertHasNoErrors();

    $doctor->refresh();

    expect($doctor->status)->toBe('approved')
        ->and($doctor->rejection_reason)->toBeNull();
});

test('admin can reject doctor with reason from doctors table action', function () {
    $admin = Admin::factory()->create();

    $doctor = Doctor::factory()->create([
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListDoctors::class)
        ->callTableAction('changeStatus', $doctor, [
            'status' => 'rejected',
            'rejection_reason' => 'Incomplete license documentation.',
        ])
        ->assertHasNoErrors();

    $doctor->refresh();

    expect($doctor->status)->toBe('rejected')
        ->and($doctor->rejection_reason)->toBe('Incomplete license documentation.');
});

test('rejecting doctor requires a rejection reason in status action', function () {
    $admin = Admin::factory()->create();

    $doctor = Doctor::factory()->create([
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListDoctors::class)
        ->callTableAction('changeStatus', $doctor, [
            'status' => 'rejected',
            'rejection_reason' => '',
        ])
        ->assertHasTableActionErrors(['rejection_reason' => 'required']);
});
