<?php

use App\Models\Board;
use App\Models\User;
use Livewire\Livewire;

it('can view boards index with create button', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('pages::boards.index')
        ->assertSee('Create Board')
        ->assertSee('No boards yet');
});

it('can create a board with name only', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    Livewire::actingAs($user)
        ->test('create-board-form')
        ->set('name', 'Test Board')
        ->call('create')
        ->assertDispatched('created');

    $board = Board::first();
    expect($board->name)->toBe('Test Board');
    expect($board->team->is($team))->toBeTrue();
    expect($board->user->is($user))->toBeTrue();

    $columns = $board->columns()->get();
    expect($columns)->toHaveCount(3);

    $notNowColumn = $columns->firstWhere('name', 'Not Now');
    expect($notNowColumn->position)->toBe(1);

    $maybeColumn = $columns->firstWhere('name', 'Maybe?');
    expect($maybeColumn->position)->toBe(2);

    $doneColumn = $columns->firstWhere('name', 'Done');
    expect($doneColumn->position)->toBe(3);
});
