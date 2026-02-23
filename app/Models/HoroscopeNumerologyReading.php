<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HoroscopeNumerologyReading Model — บันทึกการวิเคราะห์เลขศาสตร์
 *
 * รองรับ 5 ประเภท: ชื่อ, เบอร์โทร, ทะเบียนรถ, เลขบัตรประชาชน, วันเกิด
 *
 * @property int $id
 * @property int|null $user_id FK ผู้ใช้
 * @property string $session_id Session ID
 * @property string $reading_type ประเภท (name/phone/license_plate/id_card/birthday)
 * @property string $input_value ค่าที่ป้อน
 * @property array|null $input_data ข้อมูลแยกส่วน
 * @property string|null $ai_analysis ผลวิเคราะห์ AI
 * @property string|null $summary_th สรุป
 * @property int|null $numerology_number ตัวเลขประจำตัว (1-9)
 * @property array|null $lucky_numbers เลขมงคล
 * @property array|null $lucky_colors สีมงคล
 */
class HoroscopeNumerologyReading extends Model
{
    protected $table = 'horoscope_numerology_readings';

    protected $fillable = [
        'user_id',
        'session_id',
        'reading_type',
        'input_value',
        'input_data',
        'ai_analysis',
        'summary_th',
        'numerology_number',
        'numerology_meaning_th',
        'lucky_numbers',
        'lucky_colors',
        'ai_provider_used',
        'ai_model_used',
        'is_free',
        'view_count',
        'share_count',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'input_data' => 'array',
        'lucky_numbers' => 'array',
        'lucky_colors' => 'array',
        'numerology_number' => 'integer',
        'is_free' => 'boolean',
        'view_count' => 'integer',
        'share_count' => 'integer',
    ];

    // =============================================
    // ค่าคงที่ประเภทการอ่าน
    // =============================================

    public const TYPE_NAME = 'name';

    public const TYPE_PHONE = 'phone';

    public const TYPE_LICENSE_PLATE = 'license_plate';

    public const TYPE_ID_CARD = 'id_card';

    public const TYPE_BIRTHDAY = 'birthday';

    /**
     * ชื่อประเภทภาษาไทย
     */
    public const TYPE_LABELS_TH = [
        self::TYPE_NAME => 'ทำนายชื่อ',
        self::TYPE_PHONE => 'เบอร์โทรศัพท์',
        self::TYPE_LICENSE_PLATE => 'ทะเบียนรถ',
        self::TYPE_ID_CARD => 'เลขบัตรประชาชน',
        self::TYPE_BIRTHDAY => 'วันเกิด',
    ];

    /**
     * ไอคอนประเภท
     */
    public const TYPE_ICONS = [
        self::TYPE_NAME => '📝',
        self::TYPE_PHONE => '📱',
        self::TYPE_LICENSE_PLATE => '🚗',
        self::TYPE_ID_CARD => '🪪',
        self::TYPE_BIRTHDAY => '🎂',
    ];

    // =============================================
    // ความสัมพันธ์
    // =============================================

    /**
     * ผู้ใช้ (nullable สำหรับ guest)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =============================================
    // Scopes
    // =============================================

    /**
     * กรองตามประเภท
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('reading_type', $type);
    }

    // =============================================
    // Accessors / Helpers
    // =============================================

    /**
     * ชื่อประเภทภาษาไทย
     */
    public function getTypeLabelThAttribute(): string
    {
        return self::TYPE_LABELS_TH[$this->reading_type] ?? $this->reading_type;
    }

    /**
     * ไอคอนประเภท
     */
    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->reading_type] ?? '🔢';
    }

    /**
     * เพิ่มจำนวนการดู
     */
    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    /**
     * นับการใช้งานวันนี้ตาม session/user
     */
    public static function countTodayReadings(string $sessionId, ?int $userId = null): int
    {
        $query = static::whereDate('created_at', today());

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        return $query->count();
    }
}
