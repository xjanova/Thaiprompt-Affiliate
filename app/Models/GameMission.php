<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GameMission extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'slug',
        'name',
        'description',
        'type',
        'objective',
        'target',
        'requirements',
        'reward_type',
        'reward_amount',
        'reward_item',
        'reward_points',
        'difficulty',
        'is_active',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_active' => 'boolean',
        'available_from' => 'date',
        'available_until' => 'date',
    ];

    /**
     * Relationships
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function userProgress()
    {
        return $this->hasMany(UserMissionProgress::class, 'mission_id');
    }

    /**
     * Check if mission is available
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    /**
     * Get user's progress for this mission
     */
    public function getProgressForUser($userId)
    {
        return UserMissionProgress::firstOrCreate(
            [
                'user_id' => $userId,
                'mission_id' => $this->id,
            ],
            [
                'current_progress' => 0,
            ]
        );
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDaily($query)
    {
        return $query->where('type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('type', 'weekly');
    }

    public function scopeAvailable($query)
    {
        $now = Carbon::now();
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
}
