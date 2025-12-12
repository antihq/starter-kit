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

    public $selectedCard = null;

    public $showCardModal = false;

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

    public function selectCard(Card $card)
    {
        $this->authorize('view', $card);

        $this->selectedCard = $card;

        $this->showCardModal = true;
    }

    public function closeCardModal()
    {
        $this->showCardModal = false;
        $this->selectedCard = null;
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
                <div class="flex flex-col gap-2 px-2 pb-2">
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
                                @click="$wire.selectCard({{ $card->id }})"
                            />
                        @endforeach
                    </flux:kanban.column.cards>
                @endunless
            </flux:kanban.column>
        @endforeach
    </flux:kanban>

    <!-- Card Details Modal -->
    <flux:modal wire:model="showCardModal" class="md:w-96">
        @if ($selectedCard)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ $selectedCard->title }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">
                        Created {{ $selectedCard->created_at->diffForHumans() }} by {{ $selectedCard->user->name }}
                    </flux:text>
                </div>

                @if ($selectedCard->description)
                    <div>
                        <flux:text size="sm">Description</flux:text>
                        <div class="prose prose-sm mt-2 max-w-none">
                            {!! $selectedCard->description !!}
                        </div>
                    </div>
                @endif

                <div class="flex items-center justify-between border-t pt-4">
                    <flux:text class="text-sm text-zinc-500">Column: {{ $selectedCard->column->name }}</flux:text>
                    <div class="flex gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Close</flux:button>
                        </flux:modal.close>
                        <flux:button href="/cards/{{ $selectedCard->id }}/edit" variant="subtle">
                            <flux:icon.pencil variant="micro" />
                            Edit
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
