<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartSliderAnalytics extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'slider_id',
        'slide_id',
        'event_type',
        'ip_address',
        'user_agent',
        'referrer',
        'session_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    const EVENT_VIEW = 'view';
    const EVENT_CLICK = 'click';
    const EVENT_SLIDE_CHANGE = 'slide_change';

    /**
     * Relationships
     */
    public function slider()
    {
        return $this->belongsTo(SmartSlider::class, 'slider_id');
    }

    public function slide()
    {
        return $this->belongsTo(SmartSlide::class, 'slide_id');
    }

    /**
     * Track event
     */
    public static function track($sliderId, $eventType, $slideId = null)
    {
        return self::create([
            'slider_id' => $sliderId,
            'slide_id' => $slideId,
            'event_type' => $eventType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }
}
