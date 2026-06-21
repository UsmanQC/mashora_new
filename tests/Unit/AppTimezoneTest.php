<?php

use App\Support\AppTimezone;
use Tests\TestCase;

uses(TestCase::class);

test('app timezone falls back to saudi arabia when config is utc', function () {
    config(['app.timezone' => 'UTC']);

    expect(AppTimezone::name())->toBe('Asia/Riyadh');
});

test('app timezone uses configured value when not utc', function () {
    config(['app.timezone' => 'Europe/London']);

    expect(AppTimezone::name())->toBe('Europe/London');
});
