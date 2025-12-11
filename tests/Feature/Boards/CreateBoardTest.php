<?php

use App\Models\Board;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('can view board creation page', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user)->get('/boards/create')->assertSuccessful();
});

it('can create a board with name only', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    Livewire::actingAs($user)
        ->test('pages::boards.create')
        ->set('name', 'Test Board')
        ->call('create')
        ->assertRedirect('/boards/1');

    $board = Board::first();
    expect($board->name)->toBe('Test Board');
    expect($board->team->is($team))->toBeTrue();
    expect($board->user->is($user))->toBeTrue();

    $columns = $board->columns()->get();
    expect($columns)->toHaveCount(3);

    $maybeColumn = $columns->firstWhere('name', 'Maybe');
    expect($maybeColumn->position)->toBe(1);

    $notNowColumn = $columns->firstWhere('name', 'Not Now');
    expect($notNowColumn->position)->toBe(2);

    $doneColumn = $columns->firstWhere('name', 'Done');
    expect($doneColumn->position)->toBe(3);

    $maybeColumn = $columns->firstWhere('name', 'Maybe');
    expect($maybeColumn->position)->toBe(1);

    $notNowColumn = $columns->firstWhere('name', 'Not Now');
    expect($notNowColumn->position)->toBe(2);

    $doneColumn = $columns->firstWhere('name', 'Done');
    expect($doneColumn->position)->toBe(3);
});
