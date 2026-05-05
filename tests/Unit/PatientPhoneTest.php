<?php

use App\Support\PatientPhone;

test('combineInternational strips non-digits and leading zeros from national part', function () {
    expect(PatientPhone::combineInternational('966', '05-1234-5678'))->toBe('966512345678');
});

test('combineInternational merges dial and national digits', function () {
    expect(PatientPhone::combineInternational('971', '501234567'))->toBe('971501234567');
});
