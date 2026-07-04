<?php

use App\Support\ImportantNumbers;
use Tests\TestCase;

uses(TestCase::class);

test('important numbers entries include tap to call links', function () {
    $entries = ImportantNumbers::entries();

    expect($entries)->toHaveCount(16)
        ->and(collect($entries)->pluck('tel_href')->all())->each->toStartWith('tel:');
});

test('important numbers list national hotlines before regional committees', function () {
    $entries = ImportantNumbers::entries();

    expect($entries[0]['id'])->toBe('moh')
        ->and($entries[1]['id'])->toBe('violence-reports')
        ->and($entries[2]['id'])->toBe('child-support')
        ->and($entries[3]['id'])->toBe('riyadh')
        ->and($entries[4]['id'])->toBe('makkah')
        ->and($entries[5]['id'])->toBe('eastern');
});

test('important numbers grouped entries separate national and regional sections', function () {
    $groups = ImportantNumbers::groupedEntries();

    expect($groups['national'])->toHaveCount(3)
        ->and($groups['regional'])->toHaveCount(13)
        ->and(collect($groups['national'])->pluck('id')->all())->toBe(['moh', 'violence-reports', 'child-support']);
});

test('important numbers tel href handles short codes and landlines', function () {
    expect(ImportantNumbers::telHref('1919'))->toBe('tel:1919')
        ->and(ImportantNumbers::telHref('937'))->toBe('tel:937')
        ->and(ImportantNumbers::telHref('0112075242'))->toBe('tel:+966112075242')
        ->and(ImportantNumbers::telHref('163853730'))->toBe('tel:+966163853730');
});
