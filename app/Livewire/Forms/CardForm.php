<?php

namespace App\Livewire\Forms;

use App\Models\Card;
use App\Models\Column;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CardForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public ?string $description = '';

    #[Validate('required|exists:columns,id')]
    public int $column_id;

    public ?Column $column;

    public ?Card $card;

    public function setColumn(Column $column): void
    {
        $this->column = $column;
        $this->column_id = $column->id;
    }

    public function setCard(Card $card): void
    {
        $this->card = $card;
        $this->title = $card->title;
        $this->description = $card->description;
        $this->column_id = $card->column_id;
    }

    public function store(): void
    {
        $this->validate();

        if (! $this->column) {
            throw new \Exception('Column must be set before storing card');
        }

        DB::transaction(function () {
            $this->column->shiftCardsDown();

            $this->column->cards()->create([
                'title' => $this->title,
                'position' => 1,
                'user_id' => Auth::id(),
            ]);
        });

        $this->reset('title', 'description');
    }

    public function update(): void
    {
        $this->validate();

        if (! $this->card) {
            throw new \Exception('Card must be set before updating');
        }

        $originalColumnId = $this->card->column_id;
        $newColumnId = $this->column_id;

        if ($originalColumnId === $newColumnId) {
            $this->card->update([
                'title' => $this->title,
                'description' => $this->description,
            ]);

            return;
        }

        DB::transaction(function () use ($originalColumnId, $newColumnId) {
            $originalColumn = Column::find($originalColumnId);
            $newColumn = Column::find($newColumnId);

            $originalColumn->removeCardFromSequence($this->card->position);

            $newColumn->addCardToTop();

            $this->card->update([
                'title' => $this->title,
                'description' => $this->description,
                'column_id' => $newColumnId,
                'position' => 1,
            ]);
        });
    }
}
