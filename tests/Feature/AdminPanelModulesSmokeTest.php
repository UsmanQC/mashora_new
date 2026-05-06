<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('all admin panel modules load for authenticated admin', function () {
    $admin = Admin::factory()->create();

    $moduleRouteNames = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => str_starts_with($name, 'filament.admin.'))
        ->reject(fn (string $name): bool => str_contains($name, '.auth.'))
        ->filter(function (string $name): bool {
            $route = Route::getRoutes()->getByName($name);

            if (! $route) {
                return false;
            }

            // Skip routes that require a bound {record}; those are covered separately.
            return ! str_contains($route->uri(), '{record}');
        })
        ->values();

    expect($moduleRouteNames)->not->toBeEmpty();

    foreach ($moduleRouteNames as $routeName) {
        $this->actingAs($admin, 'admin')
            ->get(route($routeName))
            ->assertSuccessful();
    }
});
