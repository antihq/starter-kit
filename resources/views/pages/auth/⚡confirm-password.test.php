<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('renders the confirm password screen', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->get('/confirm-password');

    $response->assertStatus(200);
});

it('confirms the password with valid credentials', function () {
    /** @var User $user */
    $user = User::factory()->create();

    actingAs($user);

    $response = Livewire::test('pages::auth.confirm-password')
        ->set('password', 'password')
        ->call('confirmPassword');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));
});

it('rejects confirmation with an invalid password', function () {
    /** @var User $user */
    $user = User::factory()->create();

    actingAs($user);

    $response = Livewire::test('pages::auth.confirm-password')
        ->set('password', 'wrong-password')
        ->call('confirmPassword');

    $response->assertHasErrors(['password']);
});
