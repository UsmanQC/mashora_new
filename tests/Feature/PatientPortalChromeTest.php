<?php

use App\Livewire\PatientMoodPickerModal;
use App\Models\PatientMood;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest patient home is redirected to patient phone', function () {
    $this->get(route('patient.home'))
        ->assertRedirect(route('patient.phone'));
});

test('authenticated patient sees mood chrome on home', function () {
    app()->setLocale('en');

    $user = User::factory()->create([
        'name' => 'Jane Smith',
        'profile_completed' => true,
    ]);

    $this->actingAs($user)
        ->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee(__('patient.mood_feeling_cta'))
        ->assertSee(__('patient.portal_greeting', ['name' => 'Jane']), false);
});

test('patient can save mood with note and share preference', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test(PatientMoodPickerModal::class)
        ->set('showMoodModal', true)
        ->call('setMood', 'happy')
        ->set('moodNote', 'Feeling drained after work.')
        ->set('shareWithTherapist', true)
        ->call('saveMood')
        ->assertSet('showMoodModal', false);

    $this->assertDatabaseHas('patient_moods', [
        'user_id' => $user->getKey(),
        'mood' => 'happy',
        'comments' => 'Feeling drained after work.',
        'is_shared' => true,
    ]);
});

test('patient cannot save without selecting a mood', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test(PatientMoodPickerModal::class)
        ->set('showMoodModal', true)
        ->call('saveMood')
        ->assertHasErrors(['selectedMoodKey']);
});

test('invalid mood value fails validation when saving', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test(PatientMoodPickerModal::class)
        ->set('showMoodModal', true)
        ->set('selectedMoodKey', 'invalid-mood-key')
        ->call('saveMood')
        ->assertHasErrors(['selectedMoodKey']);

    expect(PatientMood::query()->count())->toBe(0);
});

test('set mood ignores unknown slugs until save', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test(PatientMoodPickerModal::class)
        ->set('showMoodModal', true)
        ->call('setMood', 'not-a-mood')
        ->assertSet('selectedMoodKey', null)
        ->call('saveMood')
        ->assertHasErrors(['selectedMoodKey']);

    expect(PatientMood::query()->count())->toBe(0);
});

test('mood detail panel appears after selecting a mood', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test(PatientMoodPickerModal::class)
        ->set('showMoodModal', true)
        ->assertSee(__('patient.mood_tracker_pick_hint'))
        ->call('setMood', 'happy')
        ->assertDontSee(__('patient.mood_tracker_pick_hint'))
        ->assertSee(__('patient.mood_tracker_note_label'), false)
        ->assertSee(__('patient.mood_tracker_save'), false);
});

test('mood modal opens when bar dispatches open event', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test(PatientMoodPickerModal::class)
        ->dispatch('open-patient-mood-picker')
        ->assertSet('showMoodModal', true);
});
