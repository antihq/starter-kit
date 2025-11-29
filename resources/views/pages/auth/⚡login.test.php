<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it('renders the login screen', function () {
    $response = get('/login');

    $response->assertStatus(200);
});

it('authenticates users with valid credentials', function () {
    $user = User::factory()->create();

    $response = Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();
});

it('rejects authentication with an invalid password', function () {
    $user = User::factory()->create();

    $response = Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login');

    $response->assertHasErrors('email');

    assertGuest();
});

it('logs out authenticated users', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post('/logout');

    $response->assertRedirect('/');

    assertGuest();
});
