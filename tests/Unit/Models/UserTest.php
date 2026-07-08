<?php

use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Support\Facades\Hash;

it('has expected fillable attributes', function () {
    expect((new User())->getFillable())->toBe(['name', 'email', 'password']);
});

it('hides password and remember_token from serialization', function () {
    $user = User::factory()->create();

    $array = $user->toArray();

    expect($array)->not->toHaveKeys(['password', 'remember_token']);
});

it('casts attributes to their expected types', function () {
    $user = User::factory()->create();

    expect($user->email_verified_at)->toBeInstanceOf(Illuminate\Support\Carbon::class)
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('implements the filament user and avatar contracts', function () {
    $user = new User();

    expect($user)->toBeInstanceOf(FilamentUser::class)
        ->and($user)->toBeInstanceOf(HasAvatar::class);
});

it('allows panel access when the email is verified', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Panel::make()))->toBeTrue();
});

it('denies panel access when the email is unverified', function () {
    $user = User::factory()->unverified()->create();

    expect($user->canAccessPanel(Panel::make()))->toBeFalse();
});

it('returns an inline svg data uri as avatar url', function () {
    $user = User::factory()->create();

    expect($user->getFilamentAvatarUrl())->toStartWith('data:image/svg+xml;utf8,')
        ->and($user->getFilamentAvatarUrl())->toContain('<svg');
});
