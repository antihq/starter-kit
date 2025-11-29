<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('switching to a subscribed organization redirects to dashboard', function () {
    /** @var User $user */
    $user = User::factory()->withPersonalOrganization()->create();

    $subscribedOrg = Organization::factory()->for($user)->withSubscription()->create();

    $unsubscribedOrg = Organization::factory()->for($user)->create();
    $user->switchOrganization($unsubscribedOrg);

    Livewire::actingAs($user)->test('billing.subscription-required')
        ->call('switchOrganization', $subscribedOrg)
        ->assertRedirect(route('dashboard'));
});

test('switching to a non-subscribed organization stays on the page', function () {
    /** @var User $user */
    $user = User::factory()->withPersonalOrganization()->create();

    $org1 = Organization::factory()->for($user)->create();
    $org2 = Organization::factory()->for($user)->create();

    $user->switchOrganization($org1);

    Livewire::actingAs($user)->test('billing.subscription-required')
        ->call('switchOrganization', $org2)
        ->assertOk();
});

test('user can switch to an organization they are a member of', function () {
    /** @var User $user */
    $user = User::factory()->withPersonalOrganization()->create();

    $org = Organization::factory()->create();
    $org->addMember($user);

    $user->switchOrganization($user->organizations->first());

    Livewire::actingAs($user)->test('billing.subscription-required')
        ->call('switchOrganization', $org)
        ->assertOk();
});

test('user cannot switch to an organization they neither own nor are a member of', function () {
    /** @var User $user */
    $user = User::factory()->withPersonalOrganization()->create();

    $otherUser = User::factory()->withPersonalOrganization()->create();
    $otherOrg = $otherUser->organizations->first();

    $user->switchOrganization($user->organizations->first());

    Livewire::actingAs($user)->test('billing.subscription-required')
        ->call('switchOrganization', $otherOrg)
        ->assertForbidden();
});
