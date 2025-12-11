<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('it can create a card at the top of the maybe column', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    $board = Board::factory()->create(['team_id' => $team->id]);
    $board->columns()->createMany([
        ['name' => 'Maybe', 'position' => 1],
        ['name' => 'Not Now', 'position' => 2],
        ['name' => 'Done', 'position' => 3],
    ]);
    $maybeColumn = $board->columns()->where('name', 'Maybe')->first();

    // Create existing card
    $existingCard = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'position' => 1,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.create', ['board' => $board, 'column' => $maybeColumn])
        ->set('title', 'New Card')
        ->set('description', '<p>Test description</p>')
        ->call('createCard')
        ->assertRedirect("/boards/{$board->id}");

    // New card should be at position 1
    $newCard = Card::where('title', 'New Card')->first();
    expect($newCard->position)->toBe(1);
    expect($newCard->description)->toBe('<p>Test description</p>');

    // Existing card should be shifted to position 2
    $existingCard->refresh();
    expect($existingCard->position)->toBe(2);
});

test('it can view individual card', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    $board = Board::factory()->create(['team_id' => $team->id]);
    $board->columns()->createMany([
        ['name' => 'Maybe', 'position' => 1],
        ['name' => 'Not Now', 'position' => 2],
        ['name' => 'Done', 'position' => 3],
    ]);
    $maybeColumn = $board->columns()->where('name', 'Maybe')->first();

    $card = Card::factory()->create(['column_id' => $maybeColumn->id]);

    Livewire::actingAs($user)
        ->test('pages::cards.show', ['card' => $card])
        ->assertSee($card->title)
        ->assertSee($card->description, false); // Don't escape HTML
});

test('card creation requires title with proper limits', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    $board = Board::factory()->create(['team_id' => $team->id]);
    $board->columns()->createMany([
        ['name' => 'Maybe', 'position' => 1],
        ['name' => 'Not Now', 'position' => 2],
        ['name' => 'Done', 'position' => 3],
    ]);
    $maybeColumn = $board->columns()->where('name', 'Maybe')->first();

    // Test empty title
    Livewire::actingAs($user)
        ->test('pages::cards.create', ['board' => $board, 'column' => $maybeColumn])
        ->set('title', '')
        ->call('createCard')
        ->assertHasErrors(['title' => 'required']);

    // Test title too long
    $longTitle = str_repeat('a', 256);
    Livewire::actingAs($user)
        ->test('pages::cards.create', ['board' => $board, 'column' => $maybeColumn])
        ->set('title', $longTitle)
        ->call('createCard')
        ->assertHasErrors(['title' => 'max']);
});

test('redirects to board after successful card creation', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    $board = Board::factory()->create(['team_id' => $team->id]);
    $board->columns()->createMany([
        ['name' => 'Maybe', 'position' => 1],
        ['name' => 'Not Now', 'position' => 2],
        ['name' => 'Done', 'position' => 3],
    ]);
    $maybeColumn = $board->columns()->where('name', 'Maybe')->first();

    Livewire::actingAs($user)
        ->test('pages::cards.create', ['board' => $board, 'column' => $maybeColumn])
        ->set('title', 'Test Card')
        ->set('description', '<p>Test description</p>')
        ->call('createCard')
        ->assertRedirect("/boards/{$board->id}");
});
