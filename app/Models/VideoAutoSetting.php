<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * VideoAutoSetting Model
 *
 * จัดการการตั้งค่าระบบ Video Automation รวมถึง API keys
 *
 * @property int $id
 * @property string $key ชื่อ setting key
 * @property string|null $value ค่าของ setting
 * @property string $type ประเภทข้อมูล
 * @property string $group กลุ่ม: general, suno, freepik, youtube, social
 * @property string|null $label ป้ายกำกับ
 * @property string|null $description คำอธิบาย
 * @property string|null $default_value ค่าเริ่มต้น
 * @property bool $is_encrypted เข้ารหัสหรือไม่
 * @property bool $is_required จำเป็นต้องกรอก
 * @property array|null $validation_rules กฎการตรวจสอบ
 * @property int $sort_order ลำดับแสดง
 * @property bool $is_active เปิดใช้งาน
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VideoAutoSetting extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_settings';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'default_value',
        'is_encrypted',
        'is_required',
        'validation_rules',
        'sort_order',
        'is_active',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'validation_rules' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * กลุ่มของ Settings ทั้งหมด
     *
     * @var array<string, string>
     */
    public const GROUPS = [
        'general' => 'ตั้งค่าทั่วไป',
        'suno' => 'Suno AI (สร้างเพลง)',
        'freepik' => 'Freepik AI (สร้างภาพ)',
        'youtube' => 'YouTube API',
        'facebook' => 'Facebook API',
        'instagram' => 'Instagram API',
        'tiktok' => 'TikTok API',
        'twitter' => 'Twitter/X API',
        'threads' => 'Threads API',
        'storage' => 'การจัดเก็บไฟล์',
        'video' => 'การสร้างวีดีโอ',
    ];

    /**
     * ประเภทข้อมูลที่รองรับ
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'string' => 'ข้อความ',
        'text' => 'ข้อความยาว',
        'integer' => 'จำนวนเต็ม',
        'float' => 'ทศนิยม',
        'boolean' => 'ใช่/ไม่',
        'json' => 'JSON',
        'encrypted' => 'เข้ารหัส (API Key)',
        'select' => 'เลือก',
    ];

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองตาม group
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: กรองเฉพาะที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม sort_order
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('group')->orderBy('sort_order');
    }

    // =========================================
    // Accessors & Mutators
    // =========================================

    /**
     * ดึงค่าที่ถอดรหัสแล้ว (ถ้าเข้ารหัส)
     *
     * @return mixed
     */
    public function getDecodedValueAttribute()
    {
        if (empty($this->value)) {
            return $this->default_value;
        }

        // ถอดรหัสถ้าเป็น encrypted
        $value = $this->is_encrypted ? $this->decryptValue() : $this->value;

        // แปลงตามประเภท
        return match ($this->type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * ถอดรหัสค่า
     *
     * @return string|null
     */
    protected function decryptValue(): ?string
    {
        try {
            return Crypt::decryptString($this->value);
        } catch (\Exception $e) {
            return $this->value;
        }
    }

    /**
     * เข้ารหัสและบันทึกค่า
     *
     * @param mixed $value
     * @return void
     */
    public function setEncodedValue($value): void
    {
        if ($this->is_encrypted || $this->type === 'encrypted') {
            $this->value = Crypt::encryptString($value);
            $this->is_encrypted = true;
        } else {
            $this->value = is_array($value) ? json_encode($value) : $value;
        }
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * ดึงค่า setting ตาม key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->decoded_value ?? $default;
    }

    /**
     * ตั้งค่า setting
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public static function setValue(string $key, $value): static
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->setEncodedValue($value);
        $setting->save();

        return $setting;
    }

    /**
     * ดึง settings ทั้งหมดของ group
     *
     * @param string $group
     * @return \Illuminate\Support\Collection
     */
    public static function getByGroup(string $group)
    {
        return static::byGroup($group)
            ->active()
            ->ordered()
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->decoded_value];
            });
    }

    /**
     * ดึง API settings ทั้งหมด
     *
     * @return array
     */
    public static function getApiSettings(): array
    {
        return [
            'suno' => static::getByGroup('suno')->toArray(),
            'freepik' => static::getByGroup('freepik')->toArray(),
            'youtube' => static::getByGroup('youtube')->toArray(),
            'facebook' => static::getByGroup('facebook')->toArray(),
            'instagram' => static::getByGroup('instagram')->toArray(),
            'tiktok' => static::getByGroup('tiktok')->toArray(),
            'twitter' => static::getByGroup('twitter')->toArray(),
        ];
    }

    /**
     * ตรวจสอบว่า API พร้อมใช้งานหรือไม่
     *
     * @param string $apiName
     * @return bool
     */
    public static function isApiConfigured(string $apiName): bool
    {
        $requiredKeys = match ($apiName) {
            'suno' => ['suno_api_key'],
            'freepik' => ['freepik_api_key'],
            'youtube' => ['youtube_client_id', 'youtube_client_secret'],
            'facebook' => ['facebook_app_id', 'facebook_app_secret'],
            'instagram' => ['instagram_app_id', 'instagram_app_secret'],
            'tiktok' => ['tiktok_client_key', 'tiktok_client_secret'],
            'twitter' => ['twitter_api_key', 'twitter_api_secret'],
            default => [],
        };

        foreach ($requiredKeys as $key) {
            if (empty(static::getValue($key))) {
                return false;
            }
        }

        return true;
    }
}
