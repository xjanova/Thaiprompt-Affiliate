<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'title',
        'message',
        'icon',
        'color',
        'priority',
        'is_important',
        'show_immediately',
        'action_url',
        'action_text',
        'description',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_important' => 'boolean',
        'show_immediately' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        static::updating(function ($template) {
            if ($template->isDirty('name') && empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'wallet' => 'กระเป๋าเงิน',
            'withdrawal' => 'ถอนเงิน',
            'deposit' => 'ฝากเงิน',
            'transfer' => 'โอนเงิน',
            'commission' => 'คอมมิชชั่น',
            'system' => 'ระบบ',
            'announcement' => 'ประกาศ',
            'alert' => 'แจ้งเตือน',
            default => 'ทั่วไป',
        };
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'ต่ำ',
            'normal' => 'ปกติ',
            'high' => 'สูง',
            'urgent' => 'เร่งด่วน',
            default => 'ปกติ',
        };
    }
}
