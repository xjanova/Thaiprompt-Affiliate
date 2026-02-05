<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI API Key Usage Log Model
 *
 * เก็บ log การใช้งาน API Keys
 *
 * @property int $id
 * @property int $ai_api_key_id
 * @property string|null $model Model ที่ใช้
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $total_tokens
 * @property int|null $response_time_ms เวลาตอบกลับ (ms)
 * @property bool $is_success สำเร็จหรือไม่
 * @property string|null $error_message Error message
 * @property string|null $request_type ประเภท request
 */
class AiApiKeyUsageLog extends Model
{
    /**
     * ชื่อตาราง
     */
    protected $table = 'ai_api_key_usage_logs';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     */
    protected $fillable = [
        'ai_api_key_id',
        'model',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'response_time_ms',
        'is_success',
        'error_message',
        'request_type',
    ];

    /**
     * การ cast ประเภทข้อมูล
     */
    protected $casts = [
        'ai_api_key_id' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'response_time_ms' => 'integer',
        'is_success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     */
    protected $attributes = [
        'input_tokens' => 0,
        'output_tokens' => 0,
        'total_tokens' => 0,
        'is_success' => true,
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * ความสัมพันธ์กับ AiApiKey
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(AiApiKey::class, 'ai_api_key_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * Scope: เฉพาะที่สำเร็จ
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_success', true);
    }

    /**
     * Scope: เฉพาะที่ล้มเหลว
     */
    public function scopeFailed($query)
    {
        return $query->where('is_success', false);
    }

    /**
     * Scope: เฉพาะวันนี้
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: เฉพาะเดือนนี้
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}
