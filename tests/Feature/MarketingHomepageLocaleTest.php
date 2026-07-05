<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('marketing homepage shows language switch in header', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-navbar-language-switch"', false)
        ->assertSee(route('patient.locale', ['locale' => 'en']), false)
        ->assertSee('dir="rtl"', false)
        ->assertSee(__('marketing.nav.ai_assistant', [], 'ar'), false)
        ->assertSee(asset('images/store/apple-icon.svg'), false)
        ->assertSee('fill="#00F076"', false)
        ->assertSee(__('marketing.cta.play_store'), false)
        ->assertSee(__('marketing.footer.copyright'), false);
});

test('marketing homepage switches to english locale', function () {
    $this->get(route('patient.locale', ['locale' => 'en']))
        ->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('dir="ltr"', false)
        ->assertSee(__('marketing.hero.title_line1', [], 'en'), false)
        ->assertSee('data-lucide="chevron-right"', false)
        ->assertDontSee('data-lucide="chevron-left"', false)
        ->assertSee(route('patient.locale', ['locale' => 'ar']), false);
});
