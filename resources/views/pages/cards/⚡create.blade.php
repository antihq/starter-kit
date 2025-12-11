<?php

use App\Models\Board;
use App\Models\Column;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

new #[Title('Create Card')] class extends Component
{
    public Board $board;

    public Column $column;

    // Card creation properties
    public string $title = '';

    public string $description = '';

    public function mount(Board $board)
    {
        $this->authorize('view', $board);
        $this->authorize('create', \App\Models\Card::class);

        $this->board = $board;

        // Find column by ID from query parameter or default to Maybe column
        $columnId = request()->query('column');
        if ($columnId) {
            $this->column = Column::findOrFail($columnId);
        } else {
            $this->column = $board->columns()->where('name', 'Maybe')->first();
        }
    }

    public function createCard()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () {
            // Shift existing cards down to make room at top
            \App\Models\Card::where('column_id', $this->column->id)
                ->increment('position');

            // New card at position 1 (top)
            \App\Models\Card::create([
                'title' => $this->title,
                'description' => $this->description,
                'column_id' => $this->column->id,
                'position' => 1,
                'user_id' => auth()->id(),
            ]);
        });

        return $this->redirect("/boards/{$this->board->id}");
    }
};
?>

<div>
    <header class="mb-6">
        <flux:link
            href="/boards/{{ $board->id }}"
            class="inline-flex items-center gap-2 text-sm"
            variant="subtle"
            wire:navigate
        >
            <flux:icon.chevron-left variant="micro" />
            {{ $board->name }}
        </flux:link>
        <flux:heading class="mt-2 text-2xl">Create Card in {{ $column->name }}</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500">
            This card will be added to the top of the "{{ $column->name }}" column
        </flux:text>
    </header>

    <flux:card>
        <div class="p-6">
            <form wire:submit="createCard">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" placeholder="Enter card title..." autofocus />
                    <flux:error name="title" />
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

                <flux:button type="submit">Create Card</flux:button>
            </form>
        </div>
    </flux:card>
</div>
