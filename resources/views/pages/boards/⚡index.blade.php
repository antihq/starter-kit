<?php

use App\Models\Board;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Boards')] class extends Component
{
    #[Computed]
    public function boards()
    {
        return Board::where('team_id', auth()->user()->currentTeam->id)
            ->latest()
            ->get();
    }
};
?>

<div>
    <header class="mb-8 flex items-center justify-between">
        <flux:heading class="text-2xl">Boards</flux:heading>
        <flux:modal.trigger name="create-board">
            <flux:button variant="primary">Create Board</flux:button>
        </flux:modal.trigger>
    </header>

    @if ($this->boards->count() > 0)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->boards as $board)
                <flux:card href="/boards/{{ $board->id }}" wire:navigate class="transition-shadow hover:shadow-md">
                    <flux:heading class="text-lg">{{ $board->name }}</flux:heading>
                </flux:card>
            @endforeach
        </div>
    @else
        <div class="py-12 text-center">
            <flux:heading class="mb-2 text-xl">No boards yet</flux:heading>
            <flux:text class="mb-6 text-zinc-600 dark:text-zinc-400">
                Create your first board to get started with your team.
            </flux:text>
            <flux:modal.trigger name="create-board">
                <flux:button variant="primary">Create Board</flux:button>
            </flux:modal.trigger>
        </div>
    @endif

    <flux:modal name="create-board" class="md:w-[512px]">
        <livewire:create-board-form @created="$refresh; $flux.modal('create-board').close();" />
    </flux:modal>
</div>
