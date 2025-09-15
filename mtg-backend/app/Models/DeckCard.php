<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeckCard extends Model
{
    protected $fillable = ['deck_id','mtg_card_id','quantity'];
}