<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('creates a new organization for the user with valid data', function () {
    $user = User::factory()->create();
    actingAs($user);

    $orgName = 'Acme Inc';
    $response = Livewire::test('organizations.create')
        ->set('name', $orgName)
        ->call('create');

    $response->assertHasNoErrors();
    $user->refresh();
    $organization = $user->organizations()->where('name', $orgName)->first();
    expect($organization)->not->toBeNull();
    expect($user->currentOrganization->is($organization))->toBeTrue();
    $response->assertRedirect(route('dashboard'));
});

it('shows validation errors for missing or invalid organization name', function () {
    $user = User::factory()->create();
    actingAs($user);

    $response = Livewire::test('organizations.create')
        ->set('name', '')
        ->call('create');

    $response->assertHasErrors(['name']);
});

it('guests cannot create organizations', function () {
    $orgName = 'Gamma Ltd';
    $response = Livewire::test('organizations.create')
        ->set('name', $orgName)
        ->call('create');

    $response->assertForbidden();
});
