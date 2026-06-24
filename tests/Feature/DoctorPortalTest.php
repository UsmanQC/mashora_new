<?php

use App\Livewire\Doctor\Components\Notifications;
use App\Models\Appointment;
use App\Models\BankAccount;
use App\Models\ChMessage;
use App\Models\Communication;
use App\Models\Degree;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\Medication;
use App\Models\Notification;
use App\Models\Speciality;
use App\Models\User;
use App\Models\WorkingDay;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

test('doctor pending approval is redirected to account status from portal pages', function () {
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'status' => 'pending',
        'phone' => '966511122255',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.dashboard'))
        ->assertRedirect(route('doctor.account-status'));

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments'))
        ->assertRedirect(route('doctor.account-status'));

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.account-status'))
        ->assertOk()
        ->assertSee(__('doctor.account_status.pending_title'));
});

test('rejected doctor is blocked from the portal and sees the rejected message', function () {
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'status' => 'rejected',
        'phone' => '966511122256',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings'))
        ->assertRedirect(route('doctor.account-status'));

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.account-status'))
        ->assertOk()
        ->assertSee(__('doctor.account_status.rejected_title'));
});

test('approved doctor visiting account status is redirected to the dashboard', function () {
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
        'status' => 'approved',
        'phone' => '966511122257',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.account-status'))
        ->assertRedirect(route('doctor.dashboard'));
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

test('authenticated doctor can view appointments ratings and settings', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $this->actingAs($doctor, 'doctor')->get(route('doctor.appointments'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.appointments', ['status' => 'completed']))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.ratings'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.profile'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.notifications'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.bank-account'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.support'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.privacy-policy'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.invoices'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.working-hours'))->assertOk();
    $this->actingAs($doctor, 'doctor')->get(route('doctor.settings.duration'))->assertOk();
});

test('doctor can save dynamic working hours', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.settings.working-hours')
        ->set('availabilities', ['sunday', 'monday'])
        ->set('workingHours.sunday', [
            ['start_time' => '09:00:00', 'end_time' => '12:00:00'],
            ['start_time' => '13:00:00', 'end_time' => '15:00:00'],
        ])
        ->set('workingHours.monday', [
            ['start_time' => '10:00:00', 'end_time' => '14:00:00'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $sunday = WorkingDay::query()
        ->where('doctor_id', $doctor->id)
        ->where('day_of_week', 'sunday')
        ->first();

    expect($sunday)->not->toBeNull();
    expect(WorkingHour::query()->where('working_day_id', $sunday->id)->count())->toBe(2);
});

test('doctor can save duration prices from settings page', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);
    Duration::query()->create(['duration' => 30, 'title' => '30 min']);

    Communication::query()->create(['communication' => 'chat', 'title' => 'Chat']);
    Communication::query()->create(['communication' => 'voice_call', 'title' => 'Voice call']);
    Communication::query()->create(['communication' => 'video_call', 'title' => 'Video call']);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.settings.duration')
        ->set('doctorDurations', ['15', '30'])
        ->set('selectedCommunications', ['chat', 'video_call'])
        ->set('durationPrices.15', 120)
        ->set('durationPrices.30', 220)
        ->set('acceptInstantAppointment', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($doctor->fresh()->accept_instant_appointment)->toBeTrue();
    expect($doctor->durations()->count())->toBe(2);
    expect($doctor->communications()->pluck('communications.communication')->sort()->values()->all())->toBe(['chat', 'video_call']);
    expect((float) $doctor->durations()->where('durations.duration', 30)->first()->pivot->price)->toBe(220.0);
});

test('doctor can save bank account from settings page', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.settings.bank-account')
        ->set('account_holder_name', 'John Doe')
        ->set('account_number', '123456789')
        ->set('iban_number', 'SA4410000000123456789001')
        ->call('save')
        ->assertHasNoErrors();

    $account = BankAccount::query()->where('doctor_id', $doctor->id)->first();
    expect($account)->not->toBeNull()
        ->and($account->account_holder_name)->toBe('John Doe')
        ->and($account->iban_number)->toBe('SA4410000000123456789001');
});

test('doctor header notifications are dynamic and can be marked as read', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    Notification::query()->create([
        'type' => 'appointment',
        'title' => 'New appointment',
        'message' => 'You have a new appointment request.',
        'userable_type' => Doctor::class,
        'userable_id' => $doctor->id,
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test(Notifications::class)
        ->assertSee('New appointment')
        ->assertSet('unreadCount', 1)
        ->call('readNotification')
        ->assertSet('unreadCount', 0);
});

test('doctor locale route updates session and redirects back', function () {
    $this->from(route('doctor.login'))
        ->get(route('doctor.locale', ['locale' => 'ar']))
        ->assertRedirect(route('doctor.login'));

    expect(session('patient_locale'))->toBe('ar');
});

test('authenticated doctor navbar shows language switch', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-test="doctor-navbar-language-switch"', false)
        ->assertSee(route('doctor.locale', ['locale' => 'en']), false)
        ->assertSee(route('doctor.locale', ['locale' => 'ar']), false)
        ->assertSee(__('doctor.language.locale_en'), false)
        ->assertSee(__('doctor.language.locale_ar_short'), false);
});

test('authenticated doctor account menu shows personal profile link', function () {
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-test="doctor-account-menu-button"', false)
        ->assertSee('data-test="doctor-personal-profile-link"', false)
        ->assertSee(route('doctor.settings.profile'), false)
        ->assertSee(__('doctor.settings.personal_profile'), false);
});

test('doctor guest welcome page shows language switch', function () {
    $this->get(route('doctor.welcome'))
        ->assertSuccessful()
        ->assertSee(__('doctor.auth.phone_heading'), false)
        ->assertSee(route('doctor.locale', ['locale' => 'en']), false)
        ->assertSee(route('doctor.locale', ['locale' => 'ar']), false);
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
    $this->get(route('doctor.login'))->assertRedirect(route('doctor.welcome'));

    $this->get(route('doctor.login', ['phone' => '966511000999']))->assertOk();
});

test('doctor register page accepts phone email password in onboarding flow', function () {
    session(['doctor_otp_verified_phone' => '966511123456']);

    Livewire::withQueryParams(['phone' => '966511123456'])
        ->test('pages::doctor.register')
        ->set('email', 'new-doctor@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('doctor.register.basic.info'));

    $doctor = Doctor::query()->where('phone', '966511123456')->first();

    expect($doctor)->not->toBeNull()
        ->and($doctor->email)->toBe('new-doctor@example.com')
        ->and($doctor->profile_completed)->toBeFalse();
});

test('doctor completes multi-step onboarding with professional details and certificate', function () {
    Storage::fake('public');

    $degree = Degree::query()->create([
        'title' => 'Doctor (Specialist)',
        'title_ar' => 'طبيب أخصائي',
        'status' => true,
    ]);

    $speciality = Speciality::query()->create([
        'title' => 'Clinical psychology',
        'title_ar' => 'علم نفس إكلينيكي',
        'status' => true,
    ]);

    $doctor = Doctor::factory()->pendingOnboarding()->create([
        'phone' => '966511123499',
        'name' => null,
        'name_ar' => null,
        'about' => null,
    ]);

    $this->actingAs($doctor, 'doctor');

    $pdf = UploadedFile::fake()->create('certificate.pdf', 200, 'application/pdf');
    $headshot = UploadedFile::fake()->image('headshot.jpg', 400, 400);
    $signatureFile = UploadedFile::fake()->image('signature.png', 120, 60);
    $signatureDataUrl = 'data:image/png;base64,'.base64_encode($signatureFile->getContent());

    Livewire::test('pages::doctor.register-basic-info')
        ->assertSet('step', 1)
        ->set('name', 'Dr. Onboarding')
        ->set('name_ar', 'د. تجربة')
        ->set('about', 'Bio text')
        ->set('about_ar', 'نص النبذة')
        ->set('profile_photo', $headshot)
        ->call('nextFromBasic')
        ->assertHasNoErrors()
        ->assertSet('step', 2)
        ->set('gender', 'female')
        ->set('degree_id', $degree->id)
        ->set('speciality_ids', [$speciality->id])
        ->set('registration_number', 'SCH-999')
        ->set('experience', 4)
        ->set('spoken_languages', 'ar_en')
        ->call('nextFromProfessional')
        ->assertHasNoErrors()
        ->assertSet('step', 3)
        ->set('certificate', $pdf)
        ->call('finishDocuments', $signatureDataUrl)
        ->assertHasNoErrors()
        ->assertRedirect(route('doctor.register.duration'));

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);
    Duration::query()->create(['duration' => 30, 'title' => '30 min']);

    Livewire::test('pages::doctor.register-duration')
        ->set('doctorDurations', ['15', '30'])
        ->set('durationPrices.15', 100)
        ->set('durationPrices.30', 200)
        ->set('selectedCommunications', ['chat', 'video_call'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('doctor.register.working-hours'));

    Livewire::test('pages::doctor.register-working-hours')
        ->set('availabilities', ['sunday', 'monday'])
        ->set('workingHours.sunday.0.start_time', '10:00:00')
        ->set('workingHours.sunday.0.end_time', '14:00:00')
        ->set('workingHours.monday.0.start_time', '10:00:00')
        ->set('workingHours.monday.0.end_time', '14:00:00')
        ->call('finish')
        ->assertHasNoErrors()
        ->assertRedirect(route('doctor.account-status'));

    $doctor->refresh();

    expect($doctor->profile_completed)->toBeTrue()
        ->and($doctor->degree_id)->toBe($degree->id)
        ->and($doctor->registration_number)->toBe('SCH-999')
        ->and($doctor->about_ar)->toBe('نص النبذة')
        ->and($doctor->profile_photo_path)->not->toBeNull()
        ->and($doctor->profile_detail_path)->not->toBeNull()
        ->and($doctor->signature)->not->toBeNull()
        ->and($doctor->specialities->pluck('id')->all())->toBe([$speciality->id])
        ->and($doctor->durations()->count())->toBe(2)
        ->and($doctor->workingDays()->count())->toBe(2);

    Storage::disk('public')->assertExists((string) $doctor->profile_photo_path);
    Storage::disk('public')->assertExists((string) $doctor->profile_detail_path);
    Storage::disk('public')->assertExists((string) $doctor->signature);
});

test('doctor welcome phone step routes existing doctors to login', function () {
    Doctor::factory()->create([
        'phone' => '966511555111',
        'profile_completed' => true,
    ]);

    Livewire::test('pages::doctor.welcome')
        ->set('phone', '966511555111')
        ->call('proceed')
        ->assertRedirect(route('doctor.login', ['phone' => '966511555111']));
});

test('doctor welcome phone step routes new numbers to register', function () {
    Livewire::test('pages::doctor.welcome')
        ->set('phone', '966511777222')
        ->call('proceed')
        ->assertRedirect(route('doctor.verify-phone', ['phone' => '966511777222']));
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
