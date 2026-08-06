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
        // ฟิลด์สำหรับระบบรับสมัคร
        'use_for_recruitment',
        'recruitment_system_prompt',
        'allowed_topics',
        'blocked_topics',
        'response_style',
        'greeting_message',
        'max_conversation_turns',
        'collect_user_info',
        'required_fields',
        'max_turns_message',
        'enable_topic_filtering',
        'off_topic_message',
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'conversation_memory_limit' => 'integer',
        'knowledge_base_sources' => 'array',
        'enable_conversation_history' => 'boolean',
        'is_active' => 'boolean',
        'enable_fallback' => 'boolean',
        // Casts สำหรับระบบรับสมัคร
        'use_for_recruitment' => 'boolean',
        'allowed_topics' => 'array',
        'blocked_topics' => 'array',
        'max_conversation_turns' => 'integer',
        'collect_user_info' => 'boolean',
        'required_fields' => 'array',
        'enable_topic_filtering' => 'boolean',
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
        if (! $this->api_key) {
            return 'Not set';
        }

        return substr($this->api_key, 0, 8).'••••••••'.substr($this->api_key, -4);
    }

    /**
     * ความสัมพันธ์กับ Topic Boundaries
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function topicBoundaries()
    {
        return $this->hasMany(LineRecruitmentTopicBoundary::class, 'ai_setting_id');
    }

    /**
     * รับ Topic Boundaries ที่ใช้งาน
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeTopicBoundaries()
    {
        return $this->hasMany(LineRecruitmentTopicBoundary::class, 'ai_setting_id')
            ->where('is_active', true)
            ->orderBy('priority', 'desc');
    }

    /**
     * รับหัวข้อที่อนุญาต
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allowedTopicBoundaries()
    {
        return $this->activeTopicBoundaries()->where('type', 'allowed');
    }

    /**
     * รับหัวข้อที่ห้าม
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function blockedTopicBoundaries()
    {
        return $this->activeTopicBoundaries()->where('type', 'blocked');
    }

    /**
     * ความสัมพันธ์กับ Conversations
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversations()
    {
        return $this->hasMany(LineBotConversation::class, 'ai_setting_id');
    }

    /**
     * Scope: เฉพาะที่ใช้สำหรับรับสมัคร
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRecruitment($query)
    {
        return $query->where('use_for_recruitment', true);
    }

    /**
     * รับ AI Setting สำหรับการรับสมัครที่ active
     */
    public static function getActiveRecruitment(): ?self
    {
        return Cache::remember('line_bot_ai_setting_recruitment', 3600, function () {
            return self::where('is_active', true)
                ->where('use_for_recruitment', true)
                ->first();
        });
    }

    /**
     * ล้าง Cache รับสมัคร
     */
    public static function clearRecruitmentCache(): void
    {
        Cache::forget('line_bot_ai_setting_recruitment');
    }

    /**
     * รับ System Prompt สำหรับการรับสมัคร
     */
    public function getRecruitmentSystemPrompt(): string
    {
        if ($this->use_for_recruitment && ! empty($this->recruitment_system_prompt)) {
            return $this->recruitment_system_prompt;
        }

        return $this->system_prompt ?? 'คุณเป็นผู้ช่วยรับสมัครสมาชิกที่เป็นมิตรและช่วยเหลือดี';
    }

    /**
     * รับข้อความต้อนรับ
     */
    public function getGreetingMessage(): string
    {
        return $this->greeting_message ?? 'สวัสดีค่ะ! ยินดีต้อนรับสู่ระบบรับสมัครสมาชิก มีอะไรให้ช่วยเหลือไหมคะ?';
    }

    /**
     * รับข้อความเมื่อหมดรอบการคุย
     */
    public function getMaxTurnsMessage(): string
    {
        return $this->max_turns_message ?? 'ขอบคุณที่สนใจค่ะ หากต้องการข้อมูลเพิ่มเติม กรุณาติดต่อเจ้าหน้าที่โดยตรงนะคะ';
    }

    /**
     * รับข้อความเมื่อคุยเรื่องที่ไม่เกี่ยวข้อง
     */
    public function getOffTopicMessage(): string
    {
        return $this->off_topic_message ?? 'ขออภัยค่ะ หัวข้อนี้อยู่นอกเหนือขอบเขตที่สามารถให้ข้อมูลได้ค่ะ กรุณาสอบถามเรื่องอื่นได้เลยค่ะ';
    }

    /**
     * ตรวจสอบว่าเปิดใช้งาน Topic Filtering หรือไม่
     */
    public function hasTopicFiltering(): bool
    {
        return $this->enable_topic_filtering ?? false;
    }
}
