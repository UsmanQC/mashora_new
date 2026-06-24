<?php

test('patient and doctor layouts expose pwa install metadata', function () {
    $this->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee(asset('manifest.webmanifest'), false)
        ->assertSee(asset('images/pwa/icon-192.png'), false)
        ->assertSee('mobile-web-app-capable', false);

    expect(file_exists(public_path('manifest.webmanifest')))->toBeTrue();
    expect(file_exists(public_path('sw.js')))->toBeTrue();
    expect(file_exists(public_path('offline.html')))->toBeTrue();
    expect(file_exists(public_path('images/pwa/icon-192.png')))->toBeTrue();
    expect(file_exists(public_path('images/pwa/icon-512.png')))->toBeTrue();

    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

    expect($manifest)->toMatchArray([
        'name' => config('pwa.name'),
        'start_url' => config('pwa.start_url'),
        'display' => config('pwa.display'),
    ]);
});

test('pwa config defines patient and doctor scopes', function () {
    expect(config('pwa.scopes'))->toContain('/patient', '/doctor');
});
