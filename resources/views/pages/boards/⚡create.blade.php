<?php

use App\Models\Board;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Kanban Board')] class extends Component
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

        // Create default columns
        $board->columns()->createMany([
            ['name' => 'Maybe', 'position' => 1],
            ['name' => 'Not Now', 'position' => 2],
            ['name' => 'Done', 'position' => 3],
        ]);

        return $this->redirect("/boards/{$board->id}", navigate: true);
    }
};
?>

<div class="mx-auto w-full max-w-[512px]">
    <flux:link href="/dashboard" class="inline-flex items-center gap-2 text-sm" variant="subtle" inline wire:navigate>
        <flux:icon.chevron-left variant="micro" />
        Dashboard
    </flux:link>

    <flux:spacer class="mt-4 lg:mt-8" />

    <form wire:submit="create">
        <flux:heading class="text-xl">Create Kanban Board</flux:heading>
        <flux:text class="mt-2">Create a new board for your team.</flux:text>

        <flux:spacer class="mt-10" />

        <div class="space-y-6">
            <flux:input label="Board Name" placeholder="Project Board" wire:model="name" />
            <flux:textarea
                label="Description (optional)"
                placeholder="What's this board for?"
                wire:model="description"
            />
        </div>

        <flux:spacer class="mt-8" />

        <flux:button type="submit" variant="primary" color="zinc" class="w-full">Create Board</flux:button>
    </form>
</div>
