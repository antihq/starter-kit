<?php

use App\Models\User;
use Livewire\Livewire;

it('renders the confirm password screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertStatus(200);
});

it('confirms the password with valid credentials', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('auth.confirm-password')
        ->set('password', 'password')
        ->call('confirmPassword');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));
});

it('rejects confirmation with an invalid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('auth.confirm-password')
        ->set('password', 'wrong-password')
        ->call('confirmPassword');

    $response->assertHasErrors(['password']);
});
