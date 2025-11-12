<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'rating',
        'feedback',
        'response_speed',
        'solution_quality',
        'staff_friendliness',
    ];

    protected $casts = [
        'rating' => 'integer',
        'response_speed' => 'integer',
        'solution_quality' => 'integer',
        'staff_friendliness' => 'integer',
    ];

    /**
     * Get the ticket this rating belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who rated
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: High ratings (4-5 stars)
     */
    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    /**
     * Scope: Low ratings (1-2 stars)
     */
    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    /**
     * Scope: By rating value
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get average rating for a specific aspect
     */
    public static function getAverageRating($aspect = 'rating')
    {
        return self::avg($aspect);
    }

    /**
     * Get rating distribution
     */
    public static function getDistribution()
    {
        return self::selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->pluck('count', 'rating')
            ->toArray();
    }

    /**
     * Get detailed statistics
     */
    public static function getStatistics()
    {
        return [
            'total_ratings' => self::count(),
            'average_rating' => round(self::avg('rating'), 2),
            'average_response_speed' => round(self::avg('response_speed'), 2),
            'average_solution_quality' => round(self::avg('solution_quality'), 2),
            'average_staff_friendliness' => round(self::avg('staff_friendliness'), 2),
            'positive_count' => self::positive()->count(),
            'negative_count' => self::negative()->count(),
            'distribution' => self::getDistribution(),
        ];
    }

    /**
     * Get rating emoji
     */
    public function getRatingEmojiAttribute()
    {
        return match($this->rating) {
            5 => '⭐⭐⭐⭐⭐',
            4 => '⭐⭐⭐⭐',
            3 => '⭐⭐⭐',
            2 => '⭐⭐',
            1 => '⭐',
            default => '—',
        };
    }

    /**
     * Get rating label
     */
    public function getRatingLabelAttribute()
    {
        return match($this->rating) {
            5 => 'ยอดเยี่ยม',
            4 => 'ดีมาก',
            3 => 'ดี',
            2 => 'ปานกลาง',
            1 => 'ต้องปรับปรุง',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get rating color
     */
    public function getRatingColorAttribute()
    {
        return match($this->rating) {
            5 => 'green',
            4 => 'blue',
            3 => 'yellow',
            2 => 'orange',
            1 => 'red',
            default => 'gray',
        };
    }
}
