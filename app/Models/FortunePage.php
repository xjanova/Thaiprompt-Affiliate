<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 🏬 FortunePage — "สาขา" ของระบบแม่หมอ (1 แถว = 1 เพจ)
 *
 * @property int $id
 * @property string $code รหัสสาขา (slug)
 * @property string $name ชื่อสาขาที่แอดมินเห็น
 * @property string|null $brand_name ชื่อแบรนด์ที่ลูกค้าเห็น
 * @property string $platform facebook / line
 * @property string $external_page_id Facebook Page ID (= entry.id ใน webhook)
 * @property string|null $page_access_token Token ส่งข้อความของเพจนี้
 * @property string|null $app_secret ใส่เฉพาะเพจที่อยู่คนละ Meta App
 * @property string|null $verify_token ใส่เฉพาะเพจที่อยู่คนละ Meta App
 * @property array|null $settings_override ค่าที่เขียนทับ fortune_telling_settings เฉพาะสาขานี้
 * @property int|null $owner_user_id
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $notes
 */
class FortunePage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'fortune_pages';

    protected $fillable = [
        'code',
        'name',
        'brand_name',
        'platform',
        'external_page_id',
        'page_access_token',
        'app_secret',
        'verify_token',
        'settings_override',
        'owner_user_id',
        'is_active',
        'auto_post_enabled',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'settings_override' => 'array',
        'is_active' => 'boolean',
        // 🏬 (2026-08-15) สวิตช์ "โพสอัตโนมัติลงหน้าเพจนี้" — opt-in เท่านั้น
        'auto_post_enabled' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * ซ่อนความลับจาก toArray()/toJson()
     *
     * 🔒 กันหลุดผ่าน API response / log ที่ dump ทั้งโมเดล
     */
    protected $hidden = [
        'page_access_token',
        'app_secret',
        'verify_token',
    ];

    /**
     * คอลัมน์ใน fortune_pages ที่แมปตรงเข้าคอลัมน์ของ fortune_telling_settings
     *
     * ใช้ตอน merge settings — คีย์เหล่านี้ชนะ settings_override เสมอ
     * เพราะเป็น "ตัวตนของเพจ" ไม่ใช่ค่าคอนฟิกทั่วไป
     *
     * ⚠️ ต้องแยกตาม platform เด็ดขาด
     *    เคยเขียนรวมเป็นแมปเดียวแล้วพบว่า สาขา LINE จะเอา channel id ไปทับ
     *    `facebook_page_id` → โค้ดฝั่ง FB ที่รันระหว่างเซสชัน LINE (เช่นลิงก์รีวิวเพจ)
     *    จะชี้ไปที่ id ผิดชนิดแบบเงียบๆ
     *
     * @var array<string, array<string, string>> platform => (fortune_pages column => settings column)
     */
    public const IDENTITY_MAP = [
        'facebook' => [
            'external_page_id' => 'facebook_page_id',
            'page_access_token' => 'facebook_page_token',
            'app_secret' => 'facebook_app_secret',
            'verify_token' => 'facebook_verify_token',
            'brand_name' => 'fortune_brand_name',
        ],
        'line' => [
            'external_page_id' => 'line_channel_id',
            'page_access_token' => 'line_channel_access_token',
            'app_secret' => 'line_channel_secret',
            'brand_name' => 'fortune_brand_name',
        ],
    ];

    /**
     * ความสัมพันธ์กับเจ้าของสาขา
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * เฉพาะสาขาที่เปิดใช้งาน
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * แปลงค่าของสาขาเป็น array สำหรับเขียนทับ fortune_telling_settings
     *
     * ลำดับความสำคัญ: settings_override < IDENTITY_MAP
     * (ค่าที่ว่าง/null จะถูกตัดทิ้ง เพื่อให้ fallback ไปค่า global)
     *
     * @return array<string, mixed>
     */
    public function toSettingsOverrides(): array
    {
        $overrides = $this->settings_override ?? [];

        if (! is_array($overrides)) {
            $overrides = [];
        }

        $identityMap = self::IDENTITY_MAP[$this->platform] ?? self::IDENTITY_MAP['facebook'];

        foreach ($identityMap as $pageColumn => $settingColumn) {
            $value = $this->getAttribute($pageColumn);

            // null/'' = ไม่ได้ตั้งค่าไว้ → ใช้ค่า global (สำคัญมากสำหรับ app_secret/verify_token
            // ที่ปกติเพจในแอปเดียวกันใช้ตัวเดียวกันหมด)
            if ($value === null || $value === '') {
                continue;
            }

            $overrides[$settingColumn] = $value;
        }

        return $overrides;
    }

    /**
     * ป้ายชื่อที่เอาไปโชว์หลังบ้าน
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->brand_name ?: $this->name;
    }
}
