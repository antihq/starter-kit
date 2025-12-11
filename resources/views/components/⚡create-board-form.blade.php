<?php

use App\Models\Board;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $description = '';

    public function create()
    {
        if (! auth()->user()->currentTeam) {
            return $this->redirect('/dashboard', navigate: true);
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $currentTeam = auth()->user()->currentTeam;
        if (! $currentTeam) {
            return $this->redirect('/dashboard', navigate: true);
        }

        $board = Board::create([
            'name' => $this->name,
            'description' => $this->description,
            'team_id' => $currentTeam->id,
            'user_id' => auth()->id(),
        ]);

        $board->columns()->createMany([
            ['name' => 'Maybe', 'position' => 1],
            ['name' => 'Not Now', 'position' => 2],
            ['name' => 'Done', 'position' => 3],
        ]);

        $this->name = '';
        $this->description = '';

        $this->dispatch('created');
    }
};
?>

<form wire:submit="create">
    <flux:heading class="text-xl">Create Kanban Board</flux:heading>
    <flux:text class="mt-2">Create a new board for your team.</flux:text>

    <flux:spacer class="mt-10" />

    <div class="space-y-6">
        <flux:input label="Board Name" placeholder="Project Board" wire:model="name" />
        <flux:textarea label="Description (optional)" placeholder="What's this board for?" wire:model="description" />
    </div>

    <flux:spacer class="mt-8" />

    <flux:button type="submit" variant="primary" color="zinc" class="w-full">Create Board</flux:button>
</form>
