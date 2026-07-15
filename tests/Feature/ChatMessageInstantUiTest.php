<?php

use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('patient send message updates state and emits instant client append script', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->set('draft', 'See me without refresh')
        ->call('sendMessage')
        ->assertSet('draft', '')
        ->assertSee('See me without refresh');

    expect(ChMessage::query()->where('appointment_id', $appointment->id)->value('body'))
        ->toBe('See me without refresh');

    $effects = $component->effects;
    $serialized = json_encode($effects);

    expect($serialized)
        ->toContain('mashora:chat-message-sent')
        ->toContain('See me without refresh');
});

test('doctor send message updates state and emits instant client append script', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    $component = Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->set('draft', 'Doctor live bubble')
        ->call('sendMessage')
        ->assertSet('draft', '')
        ->assertSee('Doctor live bubble');

    expect(ChMessage::query()->where('appointment_id', $appointment->id)->value('body'))
        ->toBe('Doctor live bubble');

    $serialized = json_encode($component->effects);

    expect($serialized)
        ->toContain('mashora:chat-message-sent')
        ->toContain('Doctor live bubble');
});
