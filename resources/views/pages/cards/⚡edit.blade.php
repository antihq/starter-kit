<?php

use App\Models\Card;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Card')] class extends Component
{
    public Card $card;

    public string $title = '';

    public string $description = '';

    public int $column_id;

    #[Computed]
    public function columns()
    {
        return $this->card->board->columns()->orderBy('position')->get();
    }

    public function mount(Card $card)
    {
        $this->authorize('update', $card);
        $this->card = $card;
        $this->title = $card->title;
        $this->description = $card->description;
        $this->column_id = $card->column_id;
    }

    public function updateCard()
    {
        $this->authorize('update', $this->card);

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'column_id' => 'required|exists:columns,id',
        ]);

        DB::transaction(function () {
            $originalColumnId = $this->card->column_id;
            $newColumnId = $this->column_id;

            // Handle position management if column changed
            if ($originalColumnId !== $newColumnId) {
                // Remove card from old column's position sequence
                Card::where('column_id', $originalColumnId)
                    ->where('position', '>', $this->card->position)
                    ->decrement('position');

                // Add card to top of new column (shift all existing cards down)
                Card::where('column_id', $newColumnId)
                    ->increment('position');

                // Update card details including new position and column
                $this->card->update([
                    'title' => $this->title,
                    'description' => $this->description,
                    'column_id' => $newColumnId,
                    'position' => 1,
                ]);
            } else {
                // Same column, just update title and description
                $this->card->update([
                    'title' => $this->title,
                    'description' => $this->description,
                ]);
            }
        });

        Flux::toast(
            heading: 'Card Updated',
            text: 'Card updated successfully.',
            variant: 'success'
        );

        return $this->redirect("/cards/{$this->card->id}");
    }
};
?>

<div>
    <header class="mb-6">
        <flux:link
            href="/cards/{{ $card->id }}"
            class="inline-flex items-center gap-2 text-sm"
            variant="subtle"
            wire:navigate
        >
            <flux:icon.chevron-left variant="micro" />
            {{ $card->title }}
        </flux:link>
        <flux:heading class="mt-2 text-2xl">Edit Card</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500">Update card details and move between columns</flux:text>
    </header>

    <flux:card>
        <div class="p-6">
            <form wire:submit="updateCard">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" placeholder="Enter card title..." autofocus />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Column</flux:label>
                    <flux:select wire:model="column_id">
                        @foreach ($this->columns as $column)
                            <option value="{{ $column->id }}">{{ $column->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="column_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:editor
                        wire:model="description"
                        placeholder="Enter card description..."
                        toolbar="bold italic | bullet | link"
                        class="**:data-[slot=content]:min-h-[150px]!"
                    />
                    <flux:error name="description" />
                </flux:field>

                <flux:button type="submit">Update Card</flux:button>
            </form>
        </div>
    </flux:card>
</div>
