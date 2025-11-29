<?php

use App\Models\User;
use Livewire\Livewire;

it('renders the login screen', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

it('authenticates users with valid credentials', function () {
    $user = User::factory()->create();

    $response = Livewire::test('pages::auth.⚡login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

it('rejects authentication with an invalid password', function () {
    $user = User::factory()->create();

    $response = Livewire::test('pages::auth.⚡login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login');

    $response->assertHasErrors('email');

    $this->assertGuest();
});

it('logs out authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');

    $this->assertGuest();
});
