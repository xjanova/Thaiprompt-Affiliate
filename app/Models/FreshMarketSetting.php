<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FreshMarketSetting - ตั้งค่าระบบตลาดสดไทยพร้อม
 *
 * @property int $id
 * @property string|null $line_channel_id
 * @property string|null $line_channel_secret
 * @property string|null $line_channel_access_token
 * @property string $ai_provider
 * @property string $ai_model
 * @property bool $use_global_ai_settings
 * @property string|null $ai_system_prompt
 * @property float $platform_fee_percentage
 * @property float $monthly_subscription_fee
 * @property string $fee_mode
 * @property int $free_trial_days
 * @property int $max_listings_free
 * @property int $max_listings_subscribed
 * @property float $default_search_radius_km
 * @property float $max_search_radius_km
 * @property bool $escrow_enabled
 * @property bool $cod_enabled
 * @property bool $rider_enabled
 * @property bool $mlm_commission_enabled
 * @property bool $cashback_enabled
 * @property string $line_flex_primary_color
 * @property string $brand_name
 * @property string|null $welcome_message
 */
class FreshMarketSetting extends Model
{
    protected $table = 'fresh_market_settings';

    /**
     * Cached instance สำหรับ singleton pattern
     */
    protected static ?self $cachedInstance = null;

    protected $fillable = [
        'line_channel_id',
        'line_channel_secret',
        'line_channel_access_token',
        'ai_provider',
        'ai_model',
        'use_global_ai_settings',
        'ai_system_prompt',
        'platform_fee_percentage',
        'monthly_subscription_fee',
        'fee_mode',
        'free_trial_days',
        'max_listings_free',
        'max_listings_subscribed',
        'default_search_radius_km',
        'max_search_radius_km',
        'escrow_enabled',
        'cod_enabled',
        'rider_enabled',
        'mlm_commission_enabled',
        'cashback_enabled',
        'line_flex_primary_color',
        'brand_name',
        'welcome_message',
    ];

    /**
     * ซ่อน credentials จาก serialization
     */
    protected $hidden = [
        'line_channel_secret',
        'line_channel_access_token',
    ];

    protected $casts = [
        'use_global_ai_settings' => 'boolean',
        'platform_fee_percentage' => 'decimal:2',
        'monthly_subscription_fee' => 'decimal:2',
        'default_search_radius_km' => 'decimal:2',
        'max_search_radius_km' => 'decimal:2',
        'escrow_enabled' => 'boolean',
        'cod_enabled' => 'boolean',
        'rider_enabled' => 'boolean',
        'mlm_commission_enabled' => 'boolean',
        'cashback_enabled' => 'boolean',
        'free_trial_days' => 'integer',
        'max_listings_free' => 'integer',
        'max_listings_subscribed' => 'integer',
    ];

    /**
     * ดึง settings แบบ singleton (cache ภายใน request)
     */
    public static function getSettings(): self
    {
        if (static::$cachedInstance !== null) {
            return static::$cachedInstance;
        }

        $settings = self::first();

        if (! $settings) {
            $settings = self::create([
                'brand_name' => 'ตลาดสดไทยพร้อม',
                'ai_provider' => 'groq',
                'ai_model' => 'llama-3.3-70b-versatile',
                'welcome_message' => 'สวัสดีค่ะ! ยินดีต้อนรับสู่ ตลาดสดไทยพร้อม 🌿 พี่ตลาดพร้อมช่วยคุณซื้อ-ขายสินค้าสดๆ ใกล้บ้านค่ะ',
            ]);
        }

        static::$cachedInstance = $settings;

        return $settings;
    }

    /**
     * ล้าง cache (เมื่อมีการอัพเดทค่า)
     */
    public static function clearCache(): void
    {
        static::$cachedInstance = null;
    }

    /**
     * ตรวจสอบว่า LINE messaging พร้อมใช้งานหรือไม่
     */
    public function isLineConfigured(): bool
    {
        return ! empty($this->line_channel_id)
            && ! empty($this->line_channel_secret)
            && ! empty($this->line_channel_access_token);
    }
}
