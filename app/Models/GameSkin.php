<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSkin extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'slug',
        'name',
        'description',
        'preview_image',
        'price',
        'currency',
        'color_primary',
        'color_secondary',
        'pattern',
        'config',
        'is_premium',
        'is_limited',
        'available_from',
        'available_until',
        'min_level',
        'min_score',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'is_premium' => 'boolean',
        'is_limited' => 'boolean',
        'is_active' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_game_skins', 'skin_id', 'user_id')
            ->withTimestamps()
            ->withPivot(['purchase_price', 'purchased_at']);
    }

    public function isAvailable()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    public function canUserPurchase(User $user)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        // Check if already owned
        if ($user->gameSkins()->where('skin_id', $this->id)->exists()) {
            return false;
        }

        // Check level requirement
        if ($user->level < $this->min_level) {
            return false;
        }

        return true;
    }

    public function scopeAvailable($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', $now);
            });
    }

    public function scopeForGame($query, $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }
}
