<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * OfficialShopSetting Model
 *
 * การตั้งค่าสำหรับ Official Shop
 * - คะแนนขั้นต่ำ AI Selection
 * - จำนวนสินค้าขายดีที่แสดง
 * - จำนวนวันเตือนก่อนถอด
 * - อื่นๆ
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 */
class OfficialShopSetting extends Model
{
    /**
     * ตารางที่ใช้
     *
     * @var string
     */
    protected $table = 'official_shop_settings';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'official_shop_setting_';
    protected const CACHE_TTL = 3600; // 1 ชั่วโมง

    /**
     * ค่าเริ่มต้น
     */
    protected static array $defaults = [
        'min_ai_score' => [
            'value' => '60',
            'type' => 'integer',
            'description' => 'คะแนนขั้นต่ำสำหรับ AI Selection (0-100)',
        ],
        'best_seller_count' => [
            'value' => '10',
            'type' => 'integer',
            'description' => 'จำนวนสินค้าขายดีที่แสดง',
        ],
        'warning_days' => [
            'value' => '3',
            'type' => 'integer',
            'description' => 'จำนวนวันให้ปรับปรุงก่อนถอดสินค้า',
        ],
        'new_product_promo_days' => [
            'value' => '7',
            'type' => 'integer',
            'description' => 'จำนวนวันโปรโมทสินค้าใหม่',
        ],
        'new_product_cooldown_days' => [
            'value' => '7',
            'type' => 'integer',
            'description' => 'Cooldown ก่อนโปรโมทสินค้าใหม่ครั้งต่อไป (วัน)',
        ],
        'ai_selection_enabled' => [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'เปิด/ปิดระบบ AI Selection',
        ],
        'new_product_promo_enabled' => [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'เปิด/ปิดระบบโปรโมทสินค้าใหม่',
        ],
        'best_seller_enabled' => [
            'value' => 'true',
            'type' => 'boolean',
            'description' => 'เปิด/ปิดระบบสินค้าขายดี',
        ],
        'max_featured_products' => [
            'value' => '50',
            'type' => 'integer',
            'description' => 'จำนวนสินค้า AI Featured สูงสุด',
        ],
        'max_new_products' => [
            'value' => '20',
            'type' => 'integer',
            'description' => 'จำนวนสินค้าใหม่สูงสุดที่แสดง',
        ],
        // น้ำหนักการคำนวณ AI Score
        'score_weight_rating' => [
            'value' => '25',
            'type' => 'integer',
            'description' => 'น้ำหนักคะแนน Rating (%)',
        ],
        'score_weight_reviews' => [
            'value' => '10',
            'type' => 'integer',
            'description' => 'น้ำหนักคะแนน Reviews (%)',
        ],
        'score_weight_sales' => [
            'value' => '25',
            'type' => 'integer',
            'description' => 'น้ำหนักคะแนน Sales (%)',
        ],
        'score_weight_followers' => [
            'value' => '15',
            'type' => 'integer',
            'description' => 'น้ำหนักคะแนน Followers (%)',
        ],
        'score_weight_products' => [
            'value' => '10',
            'type' => 'integer',
            'description' => 'น้ำหนักคะแนน จำนวนสินค้า (%)',
        ],
        'score_weight_age' => [
            'value' => '5',
            'type' => 'integer',
            'description' => 'น้ำหนักคะแนน อายุร้าน (%)',
        ],
        'score_weight_trophies' => [
            'value' => '10',
            'type' => 'integer',
            'description' => 'น้ำหนักคะแนน Trophies (%)',
        ],
    ];

    // ===== Static Methods =====

    /**
     * ดึงค่าตั้งค่า
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // ลองดึงจาก cache ก่อน
        $cacheKey = self::CACHE_PREFIX . $key;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // ดึงจาก database
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            // ใช้ค่าเริ่มต้น
            if (isset(self::$defaults[$key])) {
                $value = self::castValue(self::$defaults[$key]['value'], self::$defaults[$key]['type']);
                Cache::put($cacheKey, $value, self::CACHE_TTL);
                return $value;
            }

            return $default;
        }

        $value = self::castValue($setting->value, $setting->type);
        Cache::put($cacheKey, $value, self::CACHE_TTL);

        return $value;
    }

    /**
     * บันทึกค่าตั้งค่า
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @param string|null $description
     * @return self
     */
    public static function set(string $key, $value, ?string $type = null, ?string $description = null): self
    {
        // กำหนด type ถ้าไม่ได้ระบุ
        if (!$type) {
            $type = isset(self::$defaults[$key]) ? self::$defaults[$key]['type'] : 'string';
        }

        // แปลง value เป็น string
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $type = 'json';
        } else {
            $value = (string) $value;
        }

        // อัพเดทหรือสร้างใหม่
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description ?? (self::$defaults[$key]['description'] ?? null),
            ]
        );

        // ล้าง cache
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * ดึงการตั้งค่าทั้งหมด
     *
     * @return array
     */
    public static function getAll(): array
    {
        $settings = [];

        // รวม defaults
        foreach (self::$defaults as $key => $config) {
            $settings[$key] = [
                'key' => $key,
                'value' => self::get($key),
                'type' => $config['type'],
                'description' => $config['description'],
            ];
        }

        // รวม custom settings จาก database
        $dbSettings = self::all();
        foreach ($dbSettings as $setting) {
            $settings[$setting->key] = [
                'key' => $setting->key,
                'value' => self::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        }

        return $settings;
    }

    /**
     * สร้างค่าเริ่มต้นทั้งหมด
     *
     * @return void
     */
    public static function seedDefaults(): void
    {
        foreach (self::$defaults as $key => $config) {
            self::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    /**
     * ล้าง cache ทั้งหมด
     *
     * @return void
     */
    public static function clearCache(): void
    {
        foreach (array_keys(self::$defaults) as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }

        // ล้าง custom keys จาก database
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * แปลงค่าตาม type
     *
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'float', 'decimal' => (float) $value,
            'boolean', 'bool' => in_array(strtolower($value), ['true', '1', 'yes']),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    // ===== Shortcuts =====

    /**
     * คะแนนขั้นต่ำ
     *
     * @return int
     */
    public static function getMinAiScore(): int
    {
        return (int) self::get('min_ai_score', 60);
    }

    /**
     * จำนวนสินค้าขายดี
     *
     * @return int
     */
    public static function getBestSellerCount(): int
    {
        return (int) self::get('best_seller_count', 10);
    }

    /**
     * จำนวนวันเตือน
     *
     * @return int
     */
    public static function getWarningDays(): int
    {
        return (int) self::get('warning_days', 3);
    }

    /**
     * น้ำหนักคะแนนทั้งหมด
     *
     * @return array
     */
    public static function getScoreWeights(): array
    {
        return [
            'rating' => (int) self::get('score_weight_rating', 25) / 100,
            'reviews' => (int) self::get('score_weight_reviews', 10) / 100,
            'sales' => (int) self::get('score_weight_sales', 25) / 100,
            'followers' => (int) self::get('score_weight_followers', 15) / 100,
            'products' => (int) self::get('score_weight_products', 10) / 100,
            'age' => (int) self::get('score_weight_age', 5) / 100,
            'trophies' => (int) self::get('score_weight_trophies', 10) / 100,
        ];
    }
}
