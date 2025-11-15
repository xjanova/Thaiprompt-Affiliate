<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * GoogleTranslateSetting Model
 *
 * ตั้งค่า Google Translate API
 *
 * @property int $id
 * @property string $api_key
 * @property string|null $project_id
 * @property string $default_source_language
 * @property array $enabled_target_languages
 * @property bool $auto_detect_enabled
 * @property bool $cache_enabled
 * @property int $cache_ttl
 * @property int $daily_quota
 * @property int $monthly_quota
 * @property int $current_daily_usage
 * @property int $current_monthly_usage
 * @property \Carbon\Carbon|null $quota_reset_at
 * @property string $fallback_language
 * @property bool $fallback_enabled
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_api_call_at
 * @property string|null $last_error_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GoogleTranslateSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'google_translate_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'api_key',
        'project_id',
        'default_source_language',
        'enabled_target_languages',
        'auto_detect_enabled',
        'cache_enabled',
        'cache_ttl',
        'daily_quota',
        'monthly_quota',
        'current_daily_usage',
        'current_monthly_usage',
        'quota_reset_at',
        'fallback_language',
        'fallback_enabled',
        'is_active',
        'last_api_call_at',
        'last_error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled_target_languages' => 'array',
        'auto_detect_enabled' => 'boolean',
        'cache_enabled' => 'boolean',
        'cache_ttl' => 'integer',
        'daily_quota' => 'integer',
        'monthly_quota' => 'integer',
        'current_daily_usage' => 'integer',
        'current_monthly_usage' => 'integer',
        'quota_reset_at' => 'datetime',
        'fallback_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_api_call_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ดึง setting ที่กำลัง active
     *
     * @return static|null
     */
    public static function active(): ?static
    {
        return static::where('is_active', true)->first();
    }

    /**
     * ตรวจสอบว่ายังมี quota หรือไม่
     *
     * @return bool
     */
    public function hasQuota(): bool
    {
        if ($this->current_daily_usage >= $this->daily_quota) {
            return false;
        }

        if ($this->current_monthly_usage >= $this->monthly_quota) {
            return false;
        }

        return true;
    }

    /**
     * อัปเดต usage count
     *
     * @param int $count
     * @return void
     */
    public function incrementUsage(int $count = 1): void
    {
        $this->increment('current_daily_usage', $count);
        $this->increment('current_monthly_usage', $count);
        $this->update(['last_api_call_at' => now()]);
    }

    /**
     * รีเซ็ต daily quota (เรียกทุกวัน)
     *
     * @return void
     */
    public function resetDailyQuota(): void
    {
        $this->update([
            'current_daily_usage' => 0,
            'quota_reset_at' => now()->addDay(),
        ]);
    }

    /**
     * รีเซ็ต monthly quota (เรียกทุกเดือน)
     *
     * @return void
     */
    public function resetMonthlyQuota(): void
    {
        $this->update([
            'current_monthly_usage' => 0,
        ]);
    }
}
