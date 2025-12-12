<?php

use App\Livewire\Forms\CardForm;
use App\Models\Board;
use App\Models\Card;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Board')] class extends Component
{
    public Board $board;

    public CardForm $createCardForm;

    public $showCreateCardForm = false;

    #[Computed]
    public function columns()
    {
        return $this->board->columns()->with('cards')->orderBy('position')->get();
    }

    public function mount()
    {
        $this->authorize('view', $this->board);

        if ($maybeColumn = $this->board->maybeColumn()) {
            $this->createCardForm->setColumn($maybeColumn);
        }
    }

    public function createCard()
    {
        $this->authorize('create', Card::class);

        $this->createCardForm->store();
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
                <div class="px-2 pb-2 flex flex-col gap-2">
                    @if ($column->name === 'Maybe?')
                        <form wire:submit="createCard" x-show="$wire.showCreateCardForm" x-cloak>
                            <flux:kanban.card>
                                <div class="flex items-center gap-1">
                                    <flux:heading class="flex-1">
                                        <input
                                            class="w-full outline-none"
                                            wire:model="createCardForm.title"
                                            placeholder="New card..."
                                            required
                                            autofocus
                                        />
                                    </flux:heading>
                                    <flux:button
                                        @click="$wire.showCreateCardForm = false"
                                        variant="subtle"
                                        icon="x-mark"
                                        size="sm"
                                        inset="top bottom"
                                        square
                                    />
                                    <flux:button
                                        type="submit"
                                        variant="filled"
                                        size="sm"
                                        inset="top bottom"
                                        class="-me-1.5"
                                    >
                                        Add
                                    </flux:button>
                                </div>
                                <flux:error name="createCardForm.title" />
                            </flux:kanban.card>
                        </form>
                        <flux:button
                            x-show="! $wire.showCreateCardForm"
                            @click="$wire.showCreateCardForm = true"
                            variant="subtle"
                            icon="plus"
                            size="sm"
                            align="start"
                        >
                            Add card
                        </flux:button>
                    @elseif ($column->cards->isEmpty())
                        <div class="flex min-h-8 items-center justify-between">
                            <flux:text class="px-3">No cards yet</flux:text>
                        </div>
                    @endif
                </div>
                @unless ($column->cards->isEmpty())
                    <flux:kanban.column.cards>
                        @foreach ($column->cards as $card)
                            <flux:kanban.card
                                as="button"
                                :heading="$card->title"
                                href="/cards/{{ $card->id }}"
                                wire:navigate
                            />
                        @endforeach
                    </flux:kanban.column.cards>
                @endunless
            </flux:kanban.column>
        @endforeach
    </flux:kanban>
</div>
