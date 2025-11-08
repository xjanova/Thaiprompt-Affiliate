<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarotReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'spread_type_id',
        'question',
        'interpretation',
        'amount_paid',
        'is_free',
        'is_saved',
        'session_id',
        'ip_address',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'is_free' => 'boolean',
        'is_saved' => 'boolean',
    ];

    /**
     * Get the user who created this reading
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of this reading
     */
    public function category()
    {
        return $this->belongsTo(TarotReadingCategory::class, 'category_id');
    }

    /**
     * Get the spread type used
     */
    public function spreadType()
    {
        return $this->belongsTo(TarotSpreadType::class, 'spread_type_id');
    }

    /**
     * Get cards in this reading
     */
    public function cards()
    {
        return $this->hasMany(TarotReadingCard::class, 'reading_id')->orderBy('position');
    }

    /**
     * Check if reading belongs to user (by user_id or session_id)
     */
    public function belongsToUser($userId = null, $sessionId = null): bool
    {
        if ($userId && $this->user_id == $userId) {
            return true;
        }

        if ($sessionId && $this->session_id == $sessionId) {
            return true;
        }

        return false;
    }

    /**
     * Scope for user readings
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for session readings
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for saved readings
     */
    public function scopeSaved($query)
    {
        return $query->where('is_saved', true);
    }

    /**
     * Scope for free readings
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    /**
     * Scope for paid readings
     */
    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    /**
     * Scope for today's readings
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Get full reading with all cards
     */
    public function getFullReading(string $language = 'th'): array
    {
        $cards = $this->cards()->with('card')->get();

        return [
            'id' => $this->id,
            'question' => $this->question,
            'category' => $this->category->getName($language),
            'spread_type' => $this->spreadType->getName($language),
            'interpretation' => $this->interpretation,
            'is_free' => $this->is_free,
            'is_saved' => $this->is_saved,
            'created_at' => $this->created_at,
            'cards' => $cards->map(function ($readingCard) use ($language) {
                return [
                    'position' => $readingCard->position,
                    'position_name' => $readingCard->position_name,
                    'card_name' => $readingCard->card->getName($language),
                    'is_reversed' => $readingCard->is_reversed,
                    'meaning' => $readingCard->card->getMeaning($readingCard->is_reversed, $language),
                    'interpretation' => $readingCard->interpretation,
                    'image_url' => $readingCard->card->image_url,
                ];
            }),
        ];
    }
}
