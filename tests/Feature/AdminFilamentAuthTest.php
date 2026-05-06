<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest is redirected to filament admin login when accessing dashboard', function () {
    $this->get('/admin')
        ->assertRedirect(route('filament.admin.auth.login'));
});

test('authenticated admin can view filament dashboard', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get('/admin')
        ->assertSuccessful();
});

test('authenticated patient on web guard cannot access filament admin dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin')
        ->assertRedirect(route('filament.admin.auth.login'));
});
