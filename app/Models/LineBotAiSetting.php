<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class LineBotAiSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'api_endpoint',
        'model',
        'temperature',
        'max_tokens',
        'system_prompt',
        'enable_conversation_history',
        'conversation_memory_limit',
        'knowledge_base_sources',
        'is_active',
        'enable_fallback',
        'fallback_message',
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'conversation_memory_limit' => 'integer',
        'knowledge_base_sources' => 'array',
        'enable_conversation_history' => 'boolean',
        'is_active' => 'boolean',
        'enable_fallback' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Get active AI setting
     */
    public static function getActive(): ?self
    {
        return Cache::remember('line_bot_ai_setting_active', 3600, function () {
            return self::where('is_active', true)->first();
        });
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        Cache::forget('line_bot_ai_setting_active');
    }

    /**
     * Get knowledge bases
     */
    public function knowledgeBases()
    {
        return $this->hasMany(LineBotKnowledgeBase::class, 'ai_setting_id');
    }

    /**
     * Get active knowledge bases
     */
    public function activeKnowledgeBases()
    {
        return $this->hasMany(LineBotKnowledgeBase::class, 'ai_setting_id')
            ->where('is_active', true)
            ->orderBy('priority', 'desc');
    }

    /**
     * Get fallback message with default
     */
    public function getFallbackMessageAttribute($value): string
    {
        return $value ?? 'ขออภัยค่ะ ขณะนี้ระบบมีปัญหา กรุณาลองใหม่อีกครั้งหรือติดต่อเจ้าหน้าที่ค่ะ';
    }

    /**
     * Mask API key for display
     */
    public function getMaskedApiKey(): string
    {
        if (!$this->api_key) {
            return 'Not set';
        }
        return substr($this->api_key, 0, 8) . '••••••••' . substr($this->api_key, -4);
    }
}
