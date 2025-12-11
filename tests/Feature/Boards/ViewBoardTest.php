<?php

use App\Models\Board;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('it can view boards index page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    $board1 = Board::factory()->create(['team_id' => $team->id, 'name' => 'Board 1']);
    $board2 = Board::factory()->create(['team_id' => $team->id, 'name' => 'Board 2']);

    Livewire::actingAs($user)
        ->test('pages::boards.index')
        ->assertSee('All boards')
        ->assertSee('Board 1')
        ->assertSee('Board 2')
        ->assertSee('Create Board');
});

test('it shows empty state when no boards exist', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    Livewire::actingAs($user)
        ->test('pages::boards.index')
        ->assertSee('No boards yet')
        ->assertSee('Create your first board')
        ->assertSee('Create Board');
});

test('it only shows boards from current team', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create(['user_id' => $user->id]);
    $team2 = Team::factory()->create();
    $user->current_team_id = $team1->id;
    $user->save();

    $board1 = Board::factory()->create(['team_id' => $team1->id, 'name' => 'Team 1 Board']);
    $board2 = Board::factory()->create(['team_id' => $team2->id, 'name' => 'Team 2 Board']);

    Livewire::actingAs($user)
        ->test('pages::boards.index')
        ->assertSee('Team 1 Board')
        ->assertDontSee('Team 2 Board');
});

test('it can view individual board', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    $board = Board::factory()->create([
        'team_id' => $team->id,
        'name' => 'Test Board',
    ]);

    // Create default columns like in the board creation
    $board->columns()->createMany([
        ['name' => 'Maybe', 'position' => 1],
        ['name' => 'Not Now', 'position' => 2],
        ['name' => 'Done', 'position' => 3],
    ]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->assertSee('Test Board')

        ->assertSee('Maybe')
        ->assertSee('Not Now')
        ->assertSee('Done')
        ->assertSee('No cards yet');
});

test('it cannot view board from different team', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create(['user_id' => $user->id]);
    $team2 = Team::factory()->create();
    $user->current_team_id = $team1->id;
    $user->save();

    $board = Board::factory()->create(['team_id' => $team2->id]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->assertForbidden();
});

test('team member can view board', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($member->id);

    $owner->current_team_id = $team->id;
    $owner->save();

    $board = Board::factory()->create(['team_id' => $team->id]);

    Livewire::actingAs($member)
        ->test('pages::boards.show', ['board' => $board])
        ->assertSee($board->name);
});
