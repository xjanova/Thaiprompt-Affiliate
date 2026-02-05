<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI API Key Settings Model
 *
 * เก็บ settings สำหรับ API Key Pool ของแต่ละ provider
 *
 * @property int $id
 * @property string $provider ผู้ให้บริการ
 * @property string $rotation_mode โหมดวนใช้
 * @property int $max_consecutive_errors จำนวน errors ก่อน disable
 * @property int $disable_duration_minutes ระยะเวลา disable (นาที)
 * @property bool $auto_disable_on_limit ปิดอัตโนมัติเมื่อถึง limit
 * @property string|null $default_model Model เริ่มต้น
 * @property int|null $default_rate_limit Rate limit เริ่มต้น
 */
class AiApiKeySetting extends Model
{
    /**
     * ชื่อตาราง
     */
    protected $table = 'ai_api_key_settings';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     */
    protected $fillable = [
        'provider',
        'rotation_mode',
        'max_consecutive_errors',
        'disable_duration_minutes',
        'auto_disable_on_limit',
        'default_model',
        'default_rate_limit',
    ];

    /**
     * การ cast ประเภทข้อมูล
     */
    protected $casts = [
        'max_consecutive_errors' => 'integer',
        'disable_duration_minutes' => 'integer',
        'auto_disable_on_limit' => 'boolean',
        'default_rate_limit' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     */
    protected $attributes = [
        'rotation_mode' => 'round_robin',
        'max_consecutive_errors' => 3,
        'disable_duration_minutes' => 5,
        'auto_disable_on_limit' => true,
    ];

    // ============================================================
    // Static Methods
    // ============================================================

    /**
     * ดึง settings สำหรับ provider (สร้างใหม่ถ้าไม่มี)
     */
    public static function forProvider(string $provider): self
    {
        return self::firstOrCreate(
            ['provider' => $provider],
            [
                'rotation_mode' => 'round_robin',
                'max_consecutive_errors' => 3,
                'disable_duration_minutes' => 5,
                'auto_disable_on_limit' => true,
            ]
        );
    }

    /**
     * อัพเดท settings สำหรับ provider
     */
    public static function updateForProvider(string $provider, array $settings): self
    {
        return self::updateOrCreate(
            ['provider' => $provider],
            $settings
        );
    }

    // ============================================================
    // Methods
    // ============================================================

    /**
     * ดึงชื่อโหมดแบบเต็ม
     */
    public function getRotationModeNameAttribute(): string
    {
        return AiApiKey::ROTATION_MODES[$this->rotation_mode] ?? $this->rotation_mode;
    }
}
