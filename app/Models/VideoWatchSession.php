<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VideoWatchSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'video_id',
        'session_token',
        'start_position',
        'end_position',
        'watch_duration',
        'ip_address',
        'user_agent',
        'is_valid',
        'heartbeats',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'start_position' => 'integer',
        'end_position' => 'integer',
        'watch_duration' => 'integer',
        'is_valid' => 'boolean',
        'heartbeats' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Generate session token
     */
    public static function generateToken()
    {
        return Str::random(64);
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video
     */
    public function video()
    {
        return $this->belongsTo(VideoContent::class, 'video_id');
    }

    /**
     * Add heartbeat
     */
    public function addHeartbeat($position, $timestamp)
    {
        $heartbeats = $this->heartbeats ?? [];
        $heartbeats[] = [
            'position' => $position,
            'timestamp' => $timestamp,
            'time' => now()->toIso8601String(),
        ];
        $this->heartbeats = $heartbeats;
        $this->save();
    }

    /**
     * End session
     */
    public function endSession($endPosition)
    {
        $this->end_position = $endPosition;
        $this->watch_duration = $endPosition - $this->start_position;
        $this->ended_at = now();
        $this->save();
    }

    /**
     * Validate session (anti-cheat)
     */
    public function validateSession()
    {
        // ตรวจสอบ heartbeats
        $heartbeats = $this->heartbeats ?? [];

        if (count($heartbeats) < 3) {
            // ต้องมี heartbeat อย่างน้อย 3 ครั้ง
            $this->is_valid = false;
            $this->save();
            return false;
        }

        // ตรวจสอบความต่อเนื่องของเวลา
        for ($i = 1; $i < count($heartbeats); $i++) {
            $timeDiff = strtotime($heartbeats[$i]['time']) - strtotime($heartbeats[$i-1]['time']);
            // ถ้าห่างกันเกิน 30 วินาที อาจเป็นการโกง
            if ($timeDiff > 30) {
                $this->is_valid = false;
                $this->save();
                return false;
            }
        }

        $this->is_valid = true;
        $this->save();
        return true;
    }
}
