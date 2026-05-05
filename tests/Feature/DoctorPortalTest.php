<?php

use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('doctor guest is redirected to doctor login when accessing dashboard', function () {
    $this->get(route('doctor.dashboard'))
        ->assertRedirect(route('doctor.login'));
});

test('authenticated doctor can view dashboard', function () {
    $doctor = Doctor::factory()->create([
        'phone' => '966511122233',
        'profile_completed' => true,
    ]);

    $response = $this->actingAs($doctor, 'doctor')->get(route('doctor.dashboard'));

    $response->assertOk();
});

test('doctor pending approval sees verification on dashboard when profile is complete', function () {
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'status' => 'pending',
        'phone' => '966511122255',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.dashboard'))
        ->assertOk()
        ->assertSee(__('doctor.dashboard.verification_pending_title'));
});

test('approved doctor dashboard includes formatted revenue total for revenue-eligible statuses', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'status' => 'approved',
        'phone' => '966511122266',
    ]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'total' => 2500,
        'created_at' => now(),
        'updated_at' => now(),
        'appointment_date' => now()->toDateString(),
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.dashboard', ['period' => 'today']))
        ->assertOk()
        ->assertSee('2,500');
});

test('authenticated doctor can view ratings and settings', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $this->actingAs($doctor, 'doctor')->get(route('doctor.ratings'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings'))->assertOk();
});

test('doctor locale route updates session and redirects back', function () {
    $this->from(route('doctor.login'))
        ->get(route('doctor.locale', ['locale' => 'ar']))
        ->assertRedirect(route('doctor.login'));

    expect(session('patient_locale'))->toBe('ar');
});

test('doctor with incomplete profile is redirected to basic info from dashboard', function () {
    $doctor = Doctor::factory()->pendingOnboarding()->create([
        'phone' => '966511122244',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.dashboard'))
        ->assertRedirect(route('doctor.register.basic.info'));
});

test('doctor login page renders', function () {
    $this->get(route('doctor.login'))->assertOk();
});

test('doctor can logout', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $this->actingAs($doctor, 'doctor')
        ->post(route('doctor.logout'))
        ->assertRedirect(route('doctor.welcome'));

    $this->assertGuest('doctor');
});

test('doctor cannot access another doctors appointment workspace', function () {
    $user = User::factory()->create();
    $owner = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122277',
    ]);
    $intruder = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122288',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $owner->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
    ]);

    $this->actingAs($intruder, 'doctor')
        ->get(route('doctor.appointments.medical-history', $appointment))
        ->assertForbidden();
});

test('doctor can open own appointment workspace', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122299',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.medical-history', $appointment))
        ->assertOk();
});

test('dashboard can mark in process appointment completed when diagnosis and prescription exist', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122300',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'prescription_not_needed' => false,
        'appointment_date' => now()->toDateString(),
        'start_time' => '13:45:00',
        'scheduled_at' => now(),
    ]);

    Diagnosis::create([
        'appointment_id' => $appointment->id,
        'diagnosis_name' => 'Example diagnosis',
    ]);
    Medication::create([
        'appointment_id' => $appointment->id,
        'name' => 'Example medication',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.dashboard')
        ->call('requestCompleteAppointment', $appointment->id)
        ->assertSet('showCompleteAppointmentModal', true)
        ->assertSet('appointmentPendingCompleteId', $appointment->id)
        ->call('confirmCompleteAppointment')
        ->assertRedirect(route('doctor.dashboard'));

    $fresh = $appointment->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->actual_end_at)->not->toBeNull();
});

test('dashboard complete flow shows diagnosis modal when no diagnosis record', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122301',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.dashboard')
        ->call('requestCompleteAppointment', $appointment->id)
        ->call('confirmCompleteAppointment')
        ->assertSet('showDiagnosisRequiredModal', true)
        ->assertSet('showCompleteAppointmentModal', false);

    expect($appointment->fresh()->status)->toBe('in_process');
});

test('dashboard complete flow shows prescription modal when prescription is required and empty', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122302',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'prescription_not_needed' => false,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Diagnosis::create([
        'appointment_id' => $appointment->id,
        'diagnosis_name' => 'Example diagnosis',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.dashboard')
        ->call('requestCompleteAppointment', $appointment->id)
        ->call('confirmCompleteAppointment')
        ->assertSet('showPrescriptionRequiredModal', true)
        ->assertSet('showCompleteAppointmentModal', false);

    expect($appointment->fresh()->status)->toBe('in_process');
});

test('dashboard can complete without medications when prescription is marked not needed', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122303',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'prescription_not_needed' => true,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Diagnosis::create([
        'appointment_id' => $appointment->id,
        'diagnosis_name' => 'Example diagnosis',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.dashboard')
        ->call('requestCompleteAppointment', $appointment->id)
        ->call('confirmCompleteAppointment')
        ->assertRedirect(route('doctor.dashboard'));

    expect($appointment->fresh()->status)->toBe('completed');
});

test('doctor can save diagnosis from workspace and lands on prescription page', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122310',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.diagnosis', ['appointment' => $appointment])
        ->set('marital_status', 'married')
        ->set('diagnosis_name', 'Hypertension')
        ->set('medical_history', 'Family history of hypertension.')
        ->set('treatment_plan', 'Daily monitoring + medication.')
        ->call('save')
        ->assertRedirect(route('doctor.appointments.prescription', $appointment));

    $diagnosis = $appointment->fresh()->diagnosis;
    expect($diagnosis)->not->toBeNull()
        ->and($diagnosis->marital_status)->toBe('married')
        ->and($diagnosis->diagnosis_name)->toBe('Hypertension')
        ->and($diagnosis->treatment_plan)->toBe('Daily monitoring + medication.');
});

test('diagnosis form requires diagnosis name', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122311',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.diagnosis', ['appointment' => $appointment])
        ->set('marital_status', 'unmarried')
        ->set('diagnosis_name', '')
        ->call('save')
        ->assertHasErrors(['diagnosis_name' => 'required']);

    expect($appointment->fresh()->diagnosis)->toBeNull();
});

test('prescription page lets doctor toggle prescription_not_needed', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122312',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'prescription_not_needed' => false,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.prescription', ['appointment' => $appointment])
        ->set('prescriptionNotNeeded', true);

    expect($appointment->fresh()->prescription_not_needed)->toBeTrue();
});

test('doctor can add and remove a medication via the prescription page', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122313',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    $component = Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.prescription', ['appointment' => $appointment])
        ->call('openCreateMedication')
        ->assertSet('showMedicationModal', true)
        ->set('name', 'Amoxicillin')
        ->set('dosage', '500mg')
        ->set('usage', 'Oral')
        ->set('frequency', 'Twice daily')
        ->set('duration', '7')
        ->set('duration_measurement', 'days')
        ->call('saveMedication')
        ->assertSet('showMedicationModal', false);

    $created = $appointment->medications()->first();
    expect($created)->not->toBeNull()
        ->and($created->name)->toBe('Amoxicillin')
        ->and($created->dosage)->toBe('500mg')
        ->and($created->duration_measurement)->toBe('days');

    $component->call('deleteMedication', $created->id);

    expect($appointment->medications()->count())->toBe(0);
});

test('medical history shows previous completed sessions for the same patient', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122314',
    ]);

    $current = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    $previous = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_number' => 9001,
        'appointment_date' => now()->subDays(7)->toDateString(),
        'scheduled_at' => now()->subDays(7),
    ]);

    Diagnosis::create([
        'appointment_id' => $previous->id,
        'diagnosis_name' => 'Migraine',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.medical-history', $current))
        ->assertOk()
        ->assertSee('Migraine')
        ->assertSee('9001');
});

test('doctor can start session from conversation tab', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122315',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    $fresh = $appointment->fresh();
    expect($fresh->status)->toBe('in_process')
        ->and($fresh->actual_start_at)->not->toBeNull()
        ->and($fresh->extend_at)->not->toBeNull();
});

test('doctor can send a session chat message after starting session', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122316',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 20,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession')
        ->set('draft', 'Hello from the doctor')
        ->call('sendMessage');

    $message = ChMessage::query()->where('appointment_id', $appointment->id)->first();
    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('Hello from the doctor')
        ->and($message->send_by)->toBe('doctor');
});

test('doctor can refresh agora token for an appointment', function () {
    config([
        'agora.AGORA_APP_ID' => 'test-app-id',
        'agora.AGORA_APP_CERTIFICATE' => str_repeat('a', 32),
    ]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'phone' => '966511122317',
    ]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    $this->actingAs($doctor, 'doctor')
        ->postJson(route('doctor.appointments.realtime.agora-token', $appointment))
        ->assertOk()
        ->assertJsonPath('agora_app_id', 'test-app-id')
        ->assertJsonStructure(['agora_token', 'agora_channel']);
});
