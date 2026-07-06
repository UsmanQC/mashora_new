<?php

use App\Support\DoctorMenu;
use Tests\TestCase;

uses(TestCase::class);

test('doctor menu includes finance group with wallet invoices and bank account', function () {
    $sections = DoctorMenu::sections();

    $finance = collect($sections)->firstWhere('heading', __('doctor.menu.group_finance'));

    expect($finance)->not->toBeNull();

    $routes = collect($finance['items'])->pluck('route')->all();

    expect($routes)->toContain('doctor.settings.wallet')
        ->and($routes)->toContain('doctor.settings.invoices')
        ->and($routes)->toContain('doctor.settings.bank-account');
});

test('doctor menu sections are ordered with practice before finance', function () {
    $headings = collect(DoctorMenu::sections())->pluck('heading')->all();

    $practiceIndex = array_search(__('doctor.menu.group_practice'), $headings, true);
    $financeIndex = array_search(__('doctor.menu.group_finance'), $headings, true);

    expect($practiceIndex)->toBeLessThan($financeIndex)
        ->and($headings)->toContain(__('doctor.menu.group_account'))
        ->and($headings)->toContain(__('doctor.menu.group_help'));
});

test('doctor menu includes mobile more sections with practice and finance groups', function () {
    $sections = DoctorMenu::mobileMoreSections();

    $headings = collect($sections)->pluck('heading')->all();

    expect($headings)->toContain(__('doctor.menu.group_practice'))
        ->and($headings)->toContain(__('doctor.mobile.more_work'))
        ->and($headings)->toContain(__('doctor.menu.group_finance'));
});
