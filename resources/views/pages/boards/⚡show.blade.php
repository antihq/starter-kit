<?php

use App\Models\Board;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Board')] class extends Component
{
    public Board $board;

    #[Computed]
    public function columns()
    {
        return $this->board->columns()->with('cards')->orderBy('position')->get();
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
                <flux:kanban.column.header :heading="$column->name" :count="$column->cards->count()" />
                @unless ($column->cards->isEmpty())
                    <flux:kanban.column.cards>
                        @foreach ($column->cards as $card)
                            <flux:card
                                wire:navigate
                                href="/cards/{{ $card->id }}"
                                class="group cursor-pointer transition-shadow hover:shadow-md"
                            >
                                <flux:heading class="pr-8 text-sm">{{ $card->title }}</flux:heading>
                            </flux:card>
                        @endforeach
                    </flux:kanban.column.cards>
                @endunless

                <flux:kanban.column.footer>
                    @if ($column->name === 'Maybe?')
                        <flux:button
                            href="/boards/{{ $board->id }}/cards/create?column={{ $column->id }}"
                            variant="subtle" icon="plus" size="sm" align="start"
                        >
                            Add card
                        </flux:button>
                    @elseif ($column->cards->isEmpty())
                        <div class="flex items-center justify-between min-h-8">
                            <flux:text class="px-3">No cards yet</flux:text>
                        </div>
                    @endif
                </flux:kanban.column.footer>
            </flux:kanban.column>
        @endforeach
    </flux:kanban>
</div>
