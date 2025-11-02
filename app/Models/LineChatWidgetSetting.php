<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LineChatWidgetSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'primary_color',
        'secondary_color',
        'welcome_message',
        'avatar_id',
        'show_on_pages',
        'enabled_pages',
        'enable_sound',
        'enable_notifications',
        'auto_open',
        'auto_open_delay',
        'offline_message',
        'enable_user_chat',
        'enable_seller_chat',
        'enable_ai_chat',
        'is_active',
    ];

    protected $casts = [
        'enabled_pages' => 'array',
        'show_on_pages' => 'boolean',
        'enable_sound' => 'boolean',
        'enable_notifications' => 'boolean',
        'auto_open' => 'boolean',
        'auto_open_delay' => 'integer',
        'enable_user_chat' => 'boolean',
        'enable_seller_chat' => 'boolean',
        'enable_ai_chat' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get avatar
     */
    public function avatar()
    {
        return $this->belongsTo(LineAvatar::class, 'avatar_id');
    }

    /**
     * Get active settings
     */
    public static function getActive(): ?self
    {
        return Cache::remember('line_chat_widget_setting_active', 3600, function () {
            return self::where('is_active', true)->with('avatar')->first();
        });
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        Cache::forget('line_chat_widget_setting_active');
    }

    /**
     * Get position options
     */
    public static function getPositions(): array
    {
        return [
            'bottom-right' => 'มุมล่างขวา',
            'bottom-left' => 'มุมล่างซ้าย',
            'top-right' => 'มุมบนขวา',
            'top-left' => 'มุมบนซ้าย',
        ];
    }
}
