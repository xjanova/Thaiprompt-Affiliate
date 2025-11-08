<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarotReadingCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'reading_id',
        'card_id',
        'position',
        'position_name',
        'is_reversed',
        'interpretation',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_reversed' => 'boolean',
    ];

    /**
     * Get the reading this card belongs to
     */
    public function reading()
    {
        return $this->belongsTo(TarotReading::class, 'reading_id');
    }

    /**
     * Get the tarot card
     */
    public function card()
    {
        return $this->belongsTo(TarotCard::class, 'card_id');
    }

    /**
     * Get orientation text
     */
    public function getOrientationText(string $language = 'th'): string
    {
        if ($language === 'th') {
            return $this->is_reversed ? 'กลับหัว' : 'หัวตั้ง';
        }
        return $this->is_reversed ? 'Reversed' : 'Upright';
    }

    /**
     * Scope ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
