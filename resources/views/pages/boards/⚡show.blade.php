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

    public CardForm $editCardForm;

    public $showCreateCardForm = false;

    public $selectedCard = null;

    public $showCardModal = false;

    public $isEditingCard = false;

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

        $this->isEditingCard = false;

        $this->showCardModal = true;
    }

    public function startEditingCard()
    {
        $this->authorize('update', $this->selectedCard);

        $this->editCardForm->setCard($this->selectedCard);

        $this->isEditingCard = true;
    }

    public function cancelEditing()
    {
        $this->isEditingCard = false;

        $this->editCardForm->setCard($this->selectedCard);
    }

    public function updateCard()
    {
        $this->authorize('update', $this->selectedCard);

        $this->editCardForm->update();

        $this->selectedCard->refresh();

        $this->isEditingCard = false;
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

    <flux:modal wire:model="showCardModal" class="w-full max-w-216">
        @if ($selectedCard)
            @if ($isEditingCard)
                <!-- Edit Mode -->
                <div class="space-y-4">
                    <form wire:submit="updateCard" class="space-y-4">
                        <div>
                            <flux:heading>
                                <input
                                    wire:model="editCardForm.title"
                                    placeholder="Enter card title..."
                                    class="text-xl outline-none"
                                    autofocus
                                />
                            </flux:heading>
                            <flux:error name="editCardForm.title" />
                        </div>

                        <flux:field>
                            <flux:editor
                                wire:model="editCardForm.description"
                                placeholder="Enter card description..."
                                toolbar="bold italic | bullet | link"
                                class="**:data-[slot=content]:min-h-[150px]!"
                            />
                            <flux:error name="editCardForm.description" />
                        </flux:field>
                    </form>
                    <div class="flex items-center gap-2">
                        <flux:button wire:click="cancelEditing" variant="subtle" size="sm">Cancel</flux:button>
                        <flux:button wire:click="updateCard" variant="filled" size="sm">Save</flux:button>
                    </div>
                </div>
            @else
                <!-- View Mode -->
                <div class="space-y-4">
                    <header class="flex items-center justify-between">
                        <flux:button
                            variant="ghost"
                            inset="left right top bottom"
                            class="text-left text-xl"
                            wire:click="startEditingCard"
                        >
                            {{ $selectedCard->title }}
                        </flux:button>
                    </header>

                    @if ($selectedCard->description)
                        <div class="prose prose-sm prose-zinc max-w-none">
                            {!! $selectedCard->description !!}
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </flux:modal>
</div>
