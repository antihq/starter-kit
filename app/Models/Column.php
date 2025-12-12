<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Column extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }

    public function shiftCardsDown(): void
    {
        $this->cards()->increment('position');
    }

    public function shiftCardsUp(int $fromPosition): void
    {
        $this->cards()
            ->where('position', '>', $fromPosition)
            ->decrement('position');
    }

    public function addCardToTop(): void
    {
        $this->shiftCardsDown();
    }

    public function removeCardFromSequence(int $position): void
    {
        $this->shiftCardsUp($position);
    }
}
