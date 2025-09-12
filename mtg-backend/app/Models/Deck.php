<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Deck extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'owner_id'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function cards() { return $this->hasMany(DeckCard::class); }
}