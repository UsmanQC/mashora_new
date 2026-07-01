<?php

test('patient layout exposes patient pwa install metadata', function () {
    $patientConfig = config('pwa.apps.patient');

    $this->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee(route('manifest'), false)
        ->assertDontSee(route('manifest.doctor'), false)
        ->assertSee(asset('images/pwa/icon-192.png'), false)
        ->assertSee('mobile-web-app-capable', false)
        ->assertSee($patientConfig['description'], false)
        ->assertSee('id="awaan-pwa-install"', false);

    expect(file_exists(public_path('sw.js')))->toBeTrue();
    expect(file_exists(public_path('offline.html')))->toBeTrue();
    expect(file_exists(public_path('images/pwa/icon-192.png')))->toBeTrue();
    expect(file_exists(public_path('images/pwa/icon-512.png')))->toBeTrue();
});

test('doctor layout exposes doctor pwa install metadata', function () {
    $doctorConfig = config('pwa.apps.doctor');

    $this->get(route('doctor.welcome'))
        ->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee(route('manifest.doctor'), false)
        ->assertDontSee(route('manifest'), false)
        ->assertSee($doctorConfig['description'], false)
        ->assertSee('id="awaan-pwa-install"', false);
});

test('patient web manifest is valid and includes description', function () {
    $this->get(route('manifest'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/manifest+json; charset=utf-8')
        ->assertJson([
            'name' => config('pwa.apps.patient.name'),
            'start_url' => '/patient',
            'scope' => '/patient',
            'display' => config('pwa.display'),
        ]);

    $description = $this->get(route('manifest'))->json('description');

    expect($description)->toBeString()
        ->and(trim($description))->not->toBe('');
});

test('doctor web manifest is valid and includes description', function () {
    $this->get(route('manifest.doctor'))
        ->assertSuccessful()
        ->assertJson([
            'name' => config('pwa.apps.doctor.name'),
            'start_url' => '/doctor',
            'scope' => '/doctor',
        ]);

    $description = $this->get(route('manifest.doctor'))->json('description');

    expect($description)->toBeString()
        ->and(trim($description))->not->toBe('');
});

test('service worker registers on patient and doctor scopes', function () {
    $sw = file_get_contents(public_path('sw.js'));

    expect($sw)->toContain('awaan-v2')
        ->and($sw)->toContain('/manifest.webmanifest')
        ->and($sw)->toContain('/doctor/manifest.webmanifest');
});
