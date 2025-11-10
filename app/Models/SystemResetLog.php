<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemResetLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'performed_by',
        'reset_options',
        'reset_summary',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'duration_seconds',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'reset_options' => 'array',
        'reset_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who performed the reset
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope for completed resets
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed resets
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationHumanAttribute()
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . ' วินาที';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes} นาที {$seconds} วินาที";
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังดำเนินการ',
            'completed' => 'เสร็จสิ้น',
            'failed' => 'ล้มเหลว',
            default => 'ไม่ทราบสถานะ',
        };
    }
}
