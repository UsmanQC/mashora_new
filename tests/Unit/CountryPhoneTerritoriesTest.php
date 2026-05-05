<?php

use Tests\TestCase;

uses(TestCase::class);

use App\Support\CountryPhoneTerritories;

test('dialForIso resolves territory dial digits', function () {
    expect(CountryPhoneTerritories::dialForIso('SA'))->toBe('966')
        ->and(CountryPhoneTerritories::dialForIso('MY'))->toBe('60');
});

test('all returns many territories including common Gulf and US rows', function () {
    $isos = array_column(CountryPhoneTerritories::all(), 'iso');

    expect($isos)->toContain('SA', 'AE', 'US')->and(count($isos))->toBeGreaterThan(200);
});
