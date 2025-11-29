<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('updates the password with the correct current password', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    actingAs($user);

    $response = Livewire::test('pages::settings.password')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

it('requires the correct current password to update the password', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    actingAs($user);

    $response = Livewire::test('pages::settings.password')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});
