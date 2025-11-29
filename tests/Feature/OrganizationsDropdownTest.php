<?php

use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('allows user to switch organizations from the dropdown', function () {
    $user = User::factory()->has(Organization::factory()->count(2))->create();
    $orgA = $user->organizations->first();
    $orgB = $user->organizations->skip(1)->first();

    $user->switchOrganization($orgA);
    expect($user->fresh()->currentOrganization->is($orgA))->toBeTrue();

    Livewire::actingAs($user)
        ->test('organizations-dropdown')
        ->call('switchOrganization', $orgB->id)
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->currentOrganization->is($orgB))->toBeTrue();
});

it('allows user to switch to an organization they are a member of', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $org = Organization::factory()->for($owner)->create();
    $org->addMember($member);

    $member->switchOrganization($org);
    expect($member->fresh()->currentOrganization->is($org))->toBeTrue();

    Livewire::actingAs($member)
        ->test('organizations-dropdown')
        ->call('switchOrganization', $org->id)
        ->assertRedirect(route('dashboard'));

    expect($member->fresh()->currentOrganization->is($org))->toBeTrue();
});

it('prevents user from switching to an organization they do not own', function () {
    $user = User::factory()->has(Organization::factory()->count(2))->create();
    $orgA = $user->organizations->first();
    $otherOrg = Organization::factory()->create();

    $user->switchOrganization($orgA);
    expect($user->fresh()->currentOrganization->is($orgA))->toBeTrue();

    Livewire::actingAs($user)
        ->test('organizations-dropdown')
        ->call('switchOrganization', $otherOrg->id)
        ->assertForbidden();

    expect($user->fresh()->currentOrganization->is($otherOrg))->toBeFalse();
});
