<?php

use App\Models\Card;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Card')] class extends Component
{
    public Card $card;

    public function mount(Card $card)
    {
        $this->authorize('view', $card);
        $this->card = $card;
    }
};
?>

<div>
    <header class="mb-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <flux:link
                    href="/boards/{{ $card->board->id }}"
                    class="inline-flex items-center gap-2 text-sm"
                    variant="subtle"
                    wire:navigate
                >
                    <flux:icon.chevron-left variant="micro" />
                    {{ $card->board->name }}
                </flux:link>
                <flux:heading class="mt-2 text-2xl">{{ $card->title }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">
                    Created {{ $card->created_at->diffForHumans() }} by {{ $card->user->name }}
                </flux:text>
            </div>

            <flux:button href="/cards/{{ $card->id }}/edit" variant="subtle" class="ml-4">
                <flux:icon.pencil variant="micro" />
                Edit
            </flux:button>
        </div>
    </header>

    <flux:card>
        <div class="prose prose-sm max-w-none">
            {!! $card->description !!}
        </div>
    </flux:card>
</div>
