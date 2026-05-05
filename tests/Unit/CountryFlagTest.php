<?php

use App\Support\CountryFlag;

test('emoji maps ISO codes to regional indicator pairs', function () {
    expect(CountryFlag::emoji('SA'))->toBe('🇸🇦')
        ->and(CountryFlag::emoji('MY'))->toBe('🇲🇾')
        ->and(CountryFlag::emoji('us'))->toBe('🇺🇸');
});

test('emoji returns empty for invalid input', function () {
    expect(CountryFlag::emoji(''))->toBe('')
        ->and(CountryFlag::emoji('S'))->toBe('')
        ->and(CountryFlag::emoji('SAA'))->toBe('');
});
