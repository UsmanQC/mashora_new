<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('new users receive default avatar when column exists', function () {
    config(['chatify.user_avatar.default' => 'avatar.png']);

    $user = User::factory()->create(['avatar' => null]);

    expect($user->avatar)->toBe('avatar.png');
});
