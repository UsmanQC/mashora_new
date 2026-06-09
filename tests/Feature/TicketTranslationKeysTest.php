<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin tickets index loads for authenticated admin', function (): void {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get('/admin/tickets')
        ->assertSuccessful();
});

test('admin ticket categories index loads for authenticated admin', function (): void {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get('/admin/ticket-categories')
        ->assertSuccessful();
});
