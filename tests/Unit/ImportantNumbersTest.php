<?php

use App\Support\ImportantNumbers;
use Tests\TestCase;

uses(TestCase::class);

test('important numbers entries include tap to call links', function () {
    $entries = ImportantNumbers::entries();

    expect($entries)->toHaveCount(16)
        ->and(collect($entries)->pluck('tel_href')->all())->each->toStartWith('tel:');
});

test('important numbers tel href handles short codes and landlines', function () {
    expect(ImportantNumbers::telHref('1919'))->toBe('tel:1919')
        ->and(ImportantNumbers::telHref('937'))->toBe('tel:937')
        ->and(ImportantNumbers::telHref('0112075242'))->toBe('tel:+966112075242')
        ->and(ImportantNumbers::telHref('163853730'))->toBe('tel:+966163853730');
});
