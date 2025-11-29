<?php

use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $user = User::factory()->withPersonalOrganization()->create();
    $this->actingAs($user);

    $this->get('/settings/profile')->assertOk();
});

it('updates the profile information', function () {
    $user = User::factory()->withPersonalOrganization()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.⚡profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->withPersonalOrganization()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.⚡profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('deletes the user account with the correct password', function () {
    $user = User::factory()->withPersonalOrganization()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->withPersonalOrganization()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
