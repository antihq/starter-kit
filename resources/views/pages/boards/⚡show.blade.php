<?php

use App\Models\Board;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Board')] class extends Component
{
    public Board $board;

    #[Computed]
    public function columns()
    {
        return $this->board->columns()->orderBy('position')->get();
    }

    public function mount(Board $board)
    {
        $this->authorize('view', $board);
        $this->board = $board;
    }
};
?>

<div>
    <header class="mb-6">
        <flux:link href="/boards" class="inline-flex items-center gap-2 text-sm" variant="subtle" wire:navigate>
            <flux:icon.chevron-left variant="micro" />
            Boards
        </flux:link>
        <flux:heading class="mt-2 text-2xl">{{ $board->name }}</flux:heading>
        @if ($board->description)
            <flux:text class="mt-1">{{ $board->description }}</flux:text>
        @endif
    </header>

    <flux:kanban>
        @foreach ($this->columns as $column)
            <flux:kanban.column>
                <flux:kanban.column.header :heading="$column->name" :count="0" />
                <flux:kanban.column.cards>
                    <flux:text class="py-8 text-center text-zinc-500">No cards yet</flux:text>
                </flux:kanban.column.cards>
            </flux:kanban.column>
        @endforeach
    </flux:kanban>
</div>
