<?php

use App\Support\PatientMenu;
use Tests\TestCase;

uses(TestCase::class);

test('patient menu includes account wallet and notifications in account group', function () {
    $sections = PatientMenu::sections();

    $account = collect($sections)->firstWhere('heading', __('patient.sidebar.group_account'));

    expect($account)->not->toBeNull();

    $routes = collect($account['items'])->pluck('route')->all();

    expect($routes)->toContain('patient.notifications')
        ->and($routes)->toContain('patient.wallet')
        ->and($routes)->toContain('profile.edit');
});

test('patient menu primary items include only home', function () {
    $routes = collect(PatientMenu::primaryItems())->pluck('route')->all();

    expect($routes)->toBe(['patient.home']);
});

test('patient menu main group includes appointments and important numbers', function () {
    $sections = PatientMenu::sections();

    $main = collect($sections)->firstWhere('heading', __('patient.sidebar.group_main'));

    expect($main)->not->toBeNull();

    $routes = collect($main['items'])->pluck('route')->all();

    expect($routes)->toContain('patient.appointments')
        ->and($routes)->toContain('patient.important-numbers');
});
