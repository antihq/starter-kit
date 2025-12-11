<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('it can update card title and description', function () {
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

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'title' => 'Original Title',
        'description' => '<p>Original description</p>',
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('title', 'Updated Title')
        ->set('description', '<p>Updated description</p>')
        ->call('updateCard')
        ->assertRedirect("/cards/{$card->id}");

    expect($card->fresh()->title)->toBe('Updated Title');
    expect($card->fresh()->description)->toBe('<p>Updated description</p>');
});

test('it can move card between columns', function () {
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
    $notNowColumn = $board->columns()->where('name', 'Not Now')->first();

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'position' => 1,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('column_id', $notNowColumn->id)
        ->call('updateCard')
        ->assertRedirect("/cards/{$card->id}");

    expect($card->fresh()->column_id)->toBe($notNowColumn->id);
    expect($card->fresh()->position)->toBe(1); // Top of new column
});

test('it manages positions correctly when moving cards', function () {
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
    $notNowColumn = $board->columns()->where('name', 'Not Now')->first();

    // Create existing card in target column
    $existingCard = Card::factory()->create([
        'column_id' => $notNowColumn->id,
        'position' => 1,
        'user_id' => $user->id,
    ]);

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'position' => 1,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('column_id', $notNowColumn->id)
        ->call('updateCard');

    // Moved card should be at position 1
    expect($card->fresh()->position)->toBe(1);
    // Existing card should be shifted to position 2
    expect($existingCard->fresh()->position)->toBe(2);
});

test('it adjusts old column positions when moving card', function () {
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
    $notNowColumn = $board->columns()->where('name', 'Not Now')->first();

    // Create cards below moving card in original column
    $cardBelow = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'position' => 2,
        'user_id' => $user->id,
    ]);

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'position' => 1,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('column_id', $notNowColumn->id)
        ->call('updateCard');

    // Card below should be shifted up to position 1
    expect($cardBelow->fresh()->position)->toBe(1);
});

test('card editing requires valid title', function () {
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

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'user_id' => $user->id,
    ]);

    // Test empty title
    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('title', '')
        ->call('updateCard')
        ->assertHasErrors(['title' => 'required']);

    // Test title too long
    $longTitle = str_repeat('a', 256);
    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('title', $longTitle)
        ->call('updateCard')
        ->assertHasErrors(['title' => 'max']);
});

test('card editing requires valid column', function () {
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

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('column_id', 999) // Non-existent column ID
        ->call('updateCard')
        ->assertHasErrors(['column_id' => 'exists']);
});

test('unauthorized users cannot edit cards', function () {
    $user = User::factory()->create();
    $nonTeamUser = User::factory()->create();
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

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($nonTeamUser)
        ->test('pages::cards.edit', ['card' => $card])
        ->assertForbidden();
});

test('team members can edit cards', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($member);
    $member->current_team_id = $team->id;
    $member->save();

    $board = Board::factory()->create(['team_id' => $team->id]);
    $board->columns()->createMany([
        ['name' => 'Maybe', 'position' => 1],
        ['name' => 'Not Now', 'position' => 2],
        ['name' => 'Done', 'position' => 3],
    ]);
    $maybeColumn = $board->columns()->where('name', 'Maybe')->first();

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'user_id' => $owner->id,
    ]);

    Livewire::actingAs($member)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('title', 'Updated by team member')
        ->call('updateCard')
        ->assertRedirect("/cards/{$card->id}");

    expect($card->fresh()->title)->toBe('Updated by team member');
});

test('edit form shows current card values', function () {
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

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'title' => 'Test Card Title',
        'description' => '<p>Test description</p>',
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->assertSet('title', 'Test Card Title')
        ->assertSet('description', '<p>Test description</p>')
        ->assertSet('column_id', $maybeColumn->id)
        ->assertSee('Test Card Title')
        ->assertSee('Maybe')
        ->assertSee('Not Now')
        ->assertSee('Done')
        ->assertSee('Edit Card'); // Page heading
});

test('it does not change positions when staying in same column', function () {
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

    $card = Card::factory()->create([
        'column_id' => $maybeColumn->id,
        'position' => 2,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::cards.edit', ['card' => $card])
        ->set('title', 'Updated Title')
        ->set('column_id', $maybeColumn->id) // Same column
        ->call('updateCard')
        ->assertRedirect("/cards/{$card->id}");

    // Position should remain unchanged
    expect($card->fresh()->position)->toBe(2);
});
