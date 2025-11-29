<?php

use App\Models\Organization;
use App\Models\User;
use App\Models\OrganizationInvitation;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Notifications\OrganizationInvitation as OrganizationInvitationNotification;

uses(RefreshDatabase::class);

it('unsets the members current organization when they are removed from that organization', function () {
    $owner = User::factory()->withPersonalOrganization()->create();
    $organization = Organization::factory()->for($owner)->create();
    $member = User::factory()->withPersonalOrganization()->create();

    $organization->addMember($member);
    $member->switchOrganization($organization);
    expect($member->currentOrganization->is($organization))->toBeTrue();

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->call('removeMember', $member)
        ->assertHasNoErrors();

    expect($member->fresh()->currentOrganization->is($organization))->toBeFalse();
});

it('allows the owner to remove a member from their organization', function () {
    $owner = User::factory()->withPersonalOrganization()->create();
    $organization = Organization::factory()->for($owner)->create();
    $member = User::factory()->create();
    $organization->members()->attach($member);

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->call('removeMember', $member)
        ->assertHasNoErrors();

    expect($organization->members()->find($member->id))->toBeNull();
});

it('forbids removing members from another organization', function () {
    $owner = User::factory()->withPersonalOrganization()->create();
    $organizationA = Organization::factory()->for($owner)->create();
    $organizationB = Organization::factory()->create();
    $member = User::factory()->create();
    $organizationB->members()->attach($member);

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organizationA])
        ->call('removeMember', $member)
        ->assertForbidden();
});

it('forbids non-owners from accessing the organization members component', function () {
    $owner = User::factory()->withPersonalOrganization()->create();
    $organization = Organization::factory()->for($owner)->create();
    $nonOwner = User::factory()->create();

    Livewire::actingAs($nonOwner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->assertForbidden();
});

it('invites a member to an organization by email', function () {
    Notification::fake();
    $owner = User::factory()->withPersonalOrganization()->create();
    $organization = Organization::factory()->for($owner)->create();
    $inviteEmail = 'invitee@example.com';

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->set('email', $inviteEmail)
        ->call('sendInvitation')
        ->assertHasNoErrors();

    Notification::assertSentOnDemand(
        OrganizationInvitationNotification::class,
        function ($notification, $channels, $notifiable) use ($inviteEmail) {
            return $notifiable->routes['mail'] === $inviteEmail;
        }
    );

    $invitation = $organization->invitations()->first();
    expect($invitation)->not->toBeNull();
    expect($invitation->email)->toBe($inviteEmail);
});

it('requires an email to invite a member', function () {
    Notification::fake();
    $owner = User::factory()->withPersonalOrganization()->create();
    $organization = Organization::factory()->for($owner)->create();

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->set('email', '')
        ->call('sendInvitation')
        ->assertHasErrors(['email' => 'required']);
});

it('requires the email to be unique for the organization', function () {
    Notification::fake();
    $owner = User::factory()->withPersonalOrganization()->create();
    $organization = Organization::factory()->for($owner)->create();
    $inviteEmail = 'invitee@example.com';

    OrganizationInvitation::factory()->for($organization)->create([
        'email' => $inviteEmail,
    ]);

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->set('email', $inviteEmail)
        ->call('sendInvitation')
        ->assertHasErrors(['email' => 'unique']);
});

it('allows the owner to revoke a pending invitation', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->for($owner)->create();
    $inviteEmail = 'invitee@example.com';
    $invitation = OrganizationInvitation::factory()->for($organization)->create([
        'email' => $inviteEmail,
    ]);

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->call('revokeInvitation', $invitation)
        ->assertHasNoErrors();

    expect($organization->invitations()->find($invitation->id))->toBeNull();
});

it('forbids revoking an invitation for another organization', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->for($owner)->create();
    $invitation = OrganizationInvitation::factory()->create();
    expect($invitation->organization->isNot($organization))->toBeTrue();

    Livewire::actingAs($owner)
        ->test('organizations.settings.members', ['organization' => $organization])
        ->call('revokeInvitation', $invitation)
        ->assertForbidden();
});
