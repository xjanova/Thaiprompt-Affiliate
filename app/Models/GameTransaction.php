<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'skin_id',
        'type',
        'amount',
        'currency',
        'status',
        'description',
        'metadata',
        'wallet_balance_before',
        'wallet_balance_after',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function skin()
    {
        return $this->belongsTo(GameSkin::class);
    }
}
