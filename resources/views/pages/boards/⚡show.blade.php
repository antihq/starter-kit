<?php

use App\Models\Board;
use Livewire\Attributes\Async;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Board')] class extends Component
{
    public Board $board;

    public function mount()
    {
        $this->authorize('view', $this->board);
    }

    #[Renderless, Async]
    public function moveColumn($item, $position)
    {
        $column = $this->board->columns()->findOrFail($item);

        $column->move($position);
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

    <flux:kanban wire:sort="moveColumn">
        @foreach ($this->board->columns as $column)
            <livewire:boards.column :column="$column" wire:key="{{ $column->id }}" wire:sort:item="{{ $column->id }}" />
        @endforeach
    </flux:kanban>
</div>
