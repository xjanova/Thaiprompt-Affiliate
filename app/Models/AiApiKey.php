<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

/**
 * AI API Key Model
 *
 * จัดการ API Keys ของ AI Providers สำหรับ Pool Management
 *
 * @property int $id
 * @property string $name ชื่อ key
 * @property string $provider ผู้ให้บริการ (grok, openai, gemini, etc.)
 * @property string $api_key API Key (encrypted)
 * @property bool $is_active สถานะเปิด/ปิด
 * @property int $priority ลำดับความสำคัญ
 * @property int $tokens_used_today Tokens ใช้วันนี้
 * @property int $tokens_used_month Tokens ใช้เดือนนี้
 * @property int $tokens_used_total Tokens ใช้ทั้งหมด
 * @property int|null $tokens_limit_daily จำกัด tokens/วัน
 * @property int|null $tokens_limit_monthly จำกัด tokens/เดือน
 * @property int $requests_today Requests วันนี้
 * @property int $requests_minute Requests นาทีนี้
 * @property int|null $rate_limit_per_minute Rate limit
 * @property Carbon|null $last_used_at ใช้งานล่าสุด
 * @property int $consecutive_errors Errors ติดต่อกัน
 * @property string|null $last_error Error ล่าสุด
 * @property Carbon|null $last_error_at เวลา error ล่าสุด
 * @property Carbon|null $disabled_until ปิดใช้งานจนถึง
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property string|null $notes หมายเหตุ
 */
class AiApiKey extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     */
    protected $table = 'ai_api_keys';

    /**
     * Providers ที่รองรับ
     */
    public const PROVIDERS = [
        'grok' => 'Grok (xAI)',
        'groq' => 'Groq',
        'openai' => 'OpenAI',
        'gemini' => 'Google Gemini',
        'qwen' => 'Qwen (Alibaba)',
        'openrouter' => 'OpenRouter',
        'anthropic' => 'Anthropic Claude',
        'deepseek' => 'DeepSeek',
        'typhoon' => 'Typhoon (SCB 10X)',
    ];

    /**
     * โหมดการวนใช้
     */
    public const ROTATION_MODES = [
        'round_robin' => 'Round Robin (วนตามลำดับ)',
        'least_used' => 'Least Used (ใช้น้อยสุดก่อน)',
        'priority' => 'Priority (ตาม priority สูง→ต่ำ)',
        'random' => 'Random (สุ่ม)',
        'failover' => 'Failover (ใช้ตัวหลัก, สำรองเมื่อ error)',
    ];

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     */
    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'is_active',
        'priority',
        'tokens_used_today',
        'tokens_used_month',
        'tokens_used_total',
        'tokens_limit_daily',
        'tokens_limit_monthly',
        'requests_today',
        'requests_minute',
        'rate_limit_per_minute',
        'last_rate_limit_reset',
        'last_used_at',
        'consecutive_errors',
        'last_error',
        'last_error_at',
        'disabled_until',
        'metadata',
        'notes',
        'tokens_reset_date',
    ];

    /**
     * การ cast ประเภทข้อมูล
     */
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'tokens_used_today' => 'integer',
        'tokens_used_month' => 'integer',
        'tokens_used_total' => 'integer',
        'tokens_limit_daily' => 'integer',
        'tokens_limit_monthly' => 'integer',
        'requests_today' => 'integer',
        'requests_minute' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'consecutive_errors' => 'integer',
        'last_rate_limit_reset' => 'datetime',
        'last_used_at' => 'datetime',
        'last_error_at' => 'datetime',
        'disabled_until' => 'datetime',
        'metadata' => 'array',
        'tokens_reset_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     */
    protected $attributes = [
        'is_active' => true,
        'priority' => 0,
        'tokens_used_today' => 0,
        'tokens_used_month' => 0,
        'tokens_used_total' => 0,
        'requests_today' => 0,
        'requests_minute' => 0,
        'consecutive_errors' => 0,
    ];

    // ============================================================
    // Accessors & Mutators
    // ============================================================

    /**
     * Encrypt API key เมื่อบันทึก
     */
    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt API key เมื่อดึงข้อมูล
     */
    public function getApiKeyAttribute($value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * ดึงชื่อ provider แบบเต็ม
     */
    public function getProviderNameAttribute(): string
    {
        return self::PROVIDERS[$this->provider] ?? $this->provider;
    }

    /**
     * ดึง masked API key สำหรับแสดงผล
     */
    public function getMaskedKeyAttribute(): string
    {
        $key = $this->api_key;
        $length = strlen($key);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }

    /**
     * คำนวณ % การใช้งาน tokens วันนี้
     */
    public function getDailyUsagePercentAttribute(): ?float
    {
        if (!$this->tokens_limit_daily) {
            return null;
        }

        return min(100, round(($this->tokens_used_today / $this->tokens_limit_daily) * 100, 1));
    }

    /**
     * คำนวณ % การใช้งาน tokens เดือนนี้
     */
    public function getMonthlyUsagePercentAttribute(): ?float
    {
        if (!$this->tokens_limit_monthly) {
            return null;
        }

        return min(100, round(($this->tokens_used_month / $this->tokens_limit_monthly) * 100, 1));
    }

    /**
     * คำนวณ tokens ที่เหลือวันนี้
     */
    public function getDailyTokensRemainingAttribute(): ?int
    {
        if (!$this->tokens_limit_daily) {
            return null;
        }

        return max(0, $this->tokens_limit_daily - $this->tokens_used_today);
    }

    /**
     * คำนวณ tokens ที่เหลือเดือนนี้
     */
    public function getMonthlyTokensRemainingAttribute(): ?int
    {
        if (!$this->tokens_limit_monthly) {
            return null;
        }

        return max(0, $this->tokens_limit_monthly - $this->tokens_used_month);
    }

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * ความสัมพันธ์กับ usage logs
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiApiKeyUsageLog::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * Scope: เฉพาะ keys ที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะ provider ที่ระบุ
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: Keys ที่พร้อมใช้งาน (active + ไม่ถูก disable ชั่วคราว + ไม่เกิน limit)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('disabled_until')
                    ->orWhere('disabled_until', '<=', now());
            });
    }

    /**
     * Scope: เรียงตาม priority (สูง → ต่ำ)
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * Scope: เรียงตามการใช้งานน้อยสุด
     */
    public function scopeOrderByLeastUsed($query)
    {
        return $query->orderBy('tokens_used_today');
    }

    // ============================================================
    // Methods
    // ============================================================

    /**
     * ตรวจสอบว่า key พร้อมใช้งานหรือไม่
     */
    public function isAvailable(): bool
    {
        // ไม่ active
        if (!$this->is_active) {
            return false;
        }

        // ถูก disable ชั่วคราว
        if ($this->disabled_until && $this->disabled_until > now()) {
            return false;
        }

        // เกิน daily limit
        if ($this->tokens_limit_daily && $this->tokens_used_today >= $this->tokens_limit_daily) {
            return false;
        }

        // เกิน monthly limit
        if ($this->tokens_limit_monthly && $this->tokens_used_month >= $this->tokens_limit_monthly) {
            return false;
        }

        return true;
    }

    /**
     * บันทึกการใช้งาน tokens
     */
    public function recordUsage(int $inputTokens, int $outputTokens, ?string $model = null, ?int $responseTimeMs = null, string $requestType = 'general'): void
    {
        $totalTokens = $inputTokens + $outputTokens;

        // Reset counters ถ้าเป็นวันใหม่
        $this->resetDailyCountersIfNeeded();

        // อัพเดท counters
        $this->increment('tokens_used_today', $totalTokens);
        $this->increment('tokens_used_month', $totalTokens);
        $this->increment('tokens_used_total', $totalTokens);
        $this->increment('requests_today');

        $this->update([
            'last_used_at' => now(),
            'consecutive_errors' => 0,  // reset errors เมื่อใช้งานสำเร็จ
        ]);

        // บันทึก log
        $this->usageLogs()->create([
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'response_time_ms' => $responseTimeMs,
            'is_success' => true,
            'request_type' => $requestType,
        ]);
    }

    /**
     * บันทึก error
     */
    public function recordError(string $errorMessage, ?string $model = null): void
    {
        $this->increment('consecutive_errors');

        $this->update([
            'last_error' => $errorMessage,
            'last_error_at' => now(),
        ]);

        // บันทึก log
        $this->usageLogs()->create([
            'model' => $model,
            'is_success' => false,
            'error_message' => $errorMessage,
        ]);

        // Disable ชั่วคราวถ้า error มากเกินไป
        $settings = AiApiKeySetting::forProvider($this->provider);
        $maxErrors = $settings->max_consecutive_errors ?? 3;
        $disableDuration = $settings->disable_duration_minutes ?? 5;

        if ($this->consecutive_errors >= $maxErrors) {
            $this->update([
                'disabled_until' => now()->addMinutes($disableDuration),
            ]);
        }
    }

    /**
     * Reset daily counters ถ้าเป็นวันใหม่
     */
    protected function resetDailyCountersIfNeeded(): void
    {
        $today = now()->toDateString();

        if ($this->tokens_reset_date != $today) {
            $this->update([
                'tokens_used_today' => 0,
                'requests_today' => 0,
                'tokens_reset_date' => $today,
            ]);

            // Reset monthly ถ้าเป็นเดือนใหม่
            if (now()->day === 1) {
                $this->update(['tokens_used_month' => 0]);
            }
        }
    }

    /**
     * Enable key กลับมาใช้งาน
     */
    public function enable(): void
    {
        $this->update([
            'is_active' => true,
            'disabled_until' => null,
            'consecutive_errors' => 0,
        ]);
    }

    /**
     * Disable key ชั่วคราว
     */
    public function disable(?int $minutes = null): void
    {
        $this->update([
            'disabled_until' => $minutes ? now()->addMinutes($minutes) : null,
            'is_active' => $minutes ? $this->is_active : false,
        ]);
    }

    // ============================================================
    // Static Methods
    // ============================================================

    /**
     * ดึง key ที่พร้อมใช้งานสำหรับ provider
     */
    public static function getAvailableForProvider(string $provider): ?self
    {
        return self::forProvider($provider)
            ->available()
            ->orderByPriority()
            ->first();
    }

    /**
     * ดึงสถิติรวมของ provider
     */
    public static function getProviderStats(string $provider): array
    {
        $keys = self::forProvider($provider)->get();

        return [
            'total_keys' => $keys->count(),
            'active_keys' => $keys->where('is_active', true)->count(),
            'available_keys' => $keys->filter(fn($k) => $k->isAvailable())->count(),
            'tokens_used_today' => $keys->sum('tokens_used_today'),
            'tokens_used_month' => $keys->sum('tokens_used_month'),
            'tokens_used_total' => $keys->sum('tokens_used_total'),
            'requests_today' => $keys->sum('requests_today'),
        ];
    }
}
