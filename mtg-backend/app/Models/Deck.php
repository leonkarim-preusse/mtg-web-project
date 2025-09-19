<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Deck extends Model
{
    use HasUuids;

    protected $fillable = ['owner_id', 'name', 'share_token', 'share_enabled'];

    // Auto-generate share_token when creating
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->share_token)) {
                $model->share_token = bin2hex(random_bytes(16));
            }
        });
    }
    public $incrementing = false;
    protected $keyType = 'string';

    // Owner relationship
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    // Cards relationship
    public function cards() { return $this->hasMany(DeckCard::class); }
}