<?php

use App\Models\Board;
use App\Models\Card;
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

    $board->createDefaultColumns();

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

test('it can create card inline and see it in the board', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $board = Board::factory()->for($team)->create();
    $board->createDefaultColumns();

    $component = Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->set('createCardForm.title', 'New Inline Card')
        ->call('createCard');

    $maybeColumn = $board->maybeColumn();
    $card = $maybeColumn->cards()->where('title', 'New Inline Card')->first();
    expect($card)->not->toBeNull();
    expect($card->user->is($user))->toBeTrue();
});

test('inline card creation maintains correct positioning', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $board = Board::factory()->for($team)->create();
    $board->createDefaultColumns();
    $maybeColumn = $board->maybeColumn();

    $existingCard1 = Card::factory()->for($maybeColumn)->for($user)->create([
        'position' => 1,
    ]);
    $existingCard2 = Card::factory()->for($maybeColumn)->for($user)->create([
        'position' => 2,
    ]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->set('createCardForm.title', 'New Top Card')
        ->call('createCard');

    $newCard = $maybeColumn->cards()->where('title', 'New Top Card')->first();
    expect($newCard)->not->toBeNull();
    expect($newCard->position)->toBe(1);

    $existingCard1->refresh();
    expect($existingCard1->position)->toBe(2);

    $existingCard2->refresh();
    expect($existingCard2->position)->toBe(3);
});

test('it can select card and show modal', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $board = Board::factory()->for($team)->create();
    $board->createDefaultColumns();
    $column = $board->columns()->first();

    $card = Card::factory()->for($column)->for($user)->create([
        'title' => 'Test Card',
        'description' => 'Test description',
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('selectCard', $card->id);

    $component
        ->assertSet('showCardModal', true)
        ->assertSet('selectedCard.id', $card->id);
});

test('it cannot select card from different team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    $otherUser = User::factory()->withPersonalTeam()->create();

    $board = Board::factory()->for($team)->create();
    $card = Card::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('selectCard', $card->id)
        ->assertForbidden();
});

test('team member can select card from shared board', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    $team->addMember($member);

    $board = Board::factory()->for($team)->create();
    $board->createDefaultColumns();
    $column = $board->columns()->first();

    $card = Card::factory()->for($column)->for($owner)->create([
        'title' => 'Shared Card',
    ]);

    Livewire::actingAs($member)
        ->test('pages::boards.show', ['board' => $board])
        ->call('selectCard', $card->id)
        ->assertOk();
});

test('it can edit card title and description inline', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $board = Board::factory()->for($team)->create();
    $board->createDefaultColumns();
    $column = $board->columns()->first();

    $card = Card::factory()->for($column)->for($user)->create([
        'title' => 'Original Title',
        'description' => 'Original Description',
    ]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('selectCard', $card->id)
        ->assertSet('selectedCard.id', $card->id)
        ->assertSet('isEditingCard', false)
        ->call('startEditingCard')
        ->assertSet('isEditingCard', true)
        ->set('editCardForm.title', 'Updated Title')
        ->set('editCardForm.description', 'Updated Description')
        ->call('updateCard')
        ->assertSet('isEditingCard', false);

    $card->refresh();
    expect($card->title)->toBe('Updated Title');
    expect($card->description)->toBe('Updated Description');
});

test('it can move card to different column while editing', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $board = Board::factory()->for($team)->create();
    $board->createDefaultColumns();
    $originalColumn = $board->columns()->first();
    $newColumn = $board->columns()->skip(1)->first();

    $card = Card::factory()->for($originalColumn)->for($user)->create([
        'title' => 'Test Card',
        'position' => 1,
    ]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('selectCard', $card->id)
        ->call('startEditingCard')
        ->set('editCardForm.column_id', $newColumn->id)
        ->call('updateCard');

    $card->refresh();
    expect($card->column_id)->toBe($newColumn->id);
    expect($card->position)->toBe(1);
});

test('it can move card to different column using radio group', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $board = Board::factory()->for($team)->create();
    $board->createDefaultColumns();
    $originalColumn = $board->columns()->first();
    $newColumn = $board->columns()->skip(1)->first();

    $card = Card::factory()->for($originalColumn)->for($user)->create([
        'title' => 'Test Card',
        'position' => 1,
    ]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('selectCard', $card->id)
        ->assertSet('editCardForm.column_id', $originalColumn->id)
        ->set('editCardForm.column_id', $newColumn->id)
        ->call('moveCardToColumn');

    $card->refresh();
    expect($card->column_id)->toBe($newColumn->id);
    expect($card->position)->toBe(1);
});
