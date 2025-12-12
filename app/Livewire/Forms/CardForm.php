<?php

namespace App\Livewire\Forms;

use App\Models\Column;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CardForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    public ?Column $column;

    public function setColumn(Column $column): void
    {
        $this->column = $column;
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

        $this->reset('title');
    }
}
