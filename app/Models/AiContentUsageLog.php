<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Content Usage Log Model
 *
 * เก็บ Log การใช้งาน API ทั้งหมด
 * สำหรับติดตาม Usage, Cost, และ Analytics
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $generation_id
 * @property string $ai_provider
 * @property string $ai_model
 * @property string|null $endpoint
 * @property string $request_type
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $total_tokens
 * @property float $cost_usd
 * @property float $cost_thb
 * @property int|null $response_time_ms
 * @property bool $is_success
 * @property string|null $error_code
 * @property string|null $error_message
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class AiContentUsageLog extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'ai_content_usage_logs';

    /**
     * ไม่มี updated_at
     */
    public $timestamps = false;

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'generation_id',
        'ai_provider',
        'ai_model',
        'endpoint',
        'request_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'cost_thb',
        'response_time_ms',
        'is_success',
        'error_code',
        'error_message',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * การแปลงชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost_usd' => 'decimal:6',
        'cost_thb' => 'decimal:4',
        'response_time_ms' => 'integer',
        'is_success' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Request Types
     */
    public const REQUEST_TYPES = [
        'completion' => 'Text Completion',
        'chat' => 'Chat Completion',
        'embedding' => 'Embedding',
        'moderation' => 'Moderation',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * ผู้ใช้
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generation ที่เกี่ยวข้อง
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiContentGeneration::class, 'generation_id');
    }

    // =========================================
    // Scopes
    // =========================================

    public function scopeOfUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfProvider($query, string $provider)
    {
        return $query->where('ai_provider', $provider);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('is_success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_success', false);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * บันทึก Log การใช้งาน
     *
     * @param array $data
     * @return static
     */
    public static function log(array $data): self
    {
        return static::create([
            'user_id' => $data['user_id'],
            'generation_id' => $data['generation_id'] ?? null,
            'ai_provider' => $data['ai_provider'],
            'ai_model' => $data['ai_model'],
            'endpoint' => $data['endpoint'] ?? null,
            'request_type' => $data['request_type'] ?? 'chat',
            'prompt_tokens' => $data['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['completion_tokens'] ?? 0,
            'total_tokens' => $data['total_tokens'] ?? 0,
            'cost_usd' => $data['cost_usd'] ?? 0,
            'cost_thb' => $data['cost_thb'] ?? ($data['cost_usd'] ?? 0) * 35,
            'response_time_ms' => $data['response_time_ms'] ?? null,
            'is_success' => $data['is_success'] ?? true,
            'error_code' => $data['error_code'] ?? null,
            'error_message' => $data['error_message'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * ดึงสถิติการใช้งานของ User
     *
     * @param int $userId
     * @param string $period ('today', 'week', 'month', 'year')
     * @return array
     */
    public static function getUserStats(int $userId, string $period = 'month'): array
    {
        $query = static::ofUser($userId)->successful();

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        return [
            'total_requests' => $query->count(),
            'total_tokens' => $query->sum('total_tokens'),
            'total_cost_usd' => $query->sum('cost_usd'),
            'total_cost_thb' => $query->sum('cost_thb'),
            'avg_response_time' => $query->avg('response_time_ms'),
        ];
    }
}
