<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelReviewVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'user_id',
        'is_helpful',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function review()
    {
        return $this->belongsTo(HotelReview::class, 'review_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
