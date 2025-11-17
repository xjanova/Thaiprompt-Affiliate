<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineBotKeyword extends Model
{
    use SoftDeletes;

    /**
     * Custom Keywords สำหรับ Hybrid Bot
     *
     * ตัวอย่าง:
     * - keyword: 'refund'
     * - trigger_words: ['refund', 'คืนเงิน', 'return']
     * - response_text: 'สำหรับการคืนเงิน...'
     * - response_type: 'text' | 'flex_message' | 'quick_reply'
     */

    protected $fillable = [
        'keyword',                  // ชื่อ keyword (unique)
        'description',              // คำอธิบาย keyword
        'trigger_words',            // Array of trigger words (JSON)
        'response_type',            // 'text', 'flex_message', 'quick_reply'
        'response_text',            // ข้อความที่ตอบกลับ
        'response_flex_json',       // Flex message JSON (ถ้า response_type = flex_message)
        'quick_reply_options',      // Quick reply options (JSON, ถ้า response_type = quick_reply)
        'category',                 // หมวดหมู่: 'faq', 'support', 'product', 'custom'
        'priority',                 // ความสำคัญ (1-100, สูงกว่า = ตรวจสอบก่อน)
        'is_active',                // เปิด/ปิด keyword
        'notes',                    // หมายเหตุ
    ];

    protected $casts = [
        'trigger_words' => 'array',
        'quick_reply_options' => 'array',
        'response_flex_json' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Get active keywords only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope: Get keywords by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Format trigger words for display
     */
    public function getFormattedTriggersAttribute(): string
    {
        return implode(', ', $this->trigger_words ?? []);
    }

    /**
     * Check if message matches this keyword
     */
    public function matches(string $messageText): bool
    {
        $lowerText = strtolower(trim($messageText));

        foreach ($this->trigger_words ?? [] as $trigger) {
            if (str_contains($lowerText, strtolower($trigger))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get response based on type
     */
    public function getFormattedResponse(): array
    {
        return [
            'type' => $this->response_type,
            'text' => $this->response_text,
            'flex_message' => $this->response_flex_json,
            'quick_reply' => $this->quick_reply_options,
        ];
    }
}
