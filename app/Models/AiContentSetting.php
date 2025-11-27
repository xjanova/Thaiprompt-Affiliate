<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * AI Content Setting Model
 *
 * จัดการการตั้งค่าระบบ AI Content Writer
 * รวมถึง API Keys สำหรับ OpenAI, Claude, Gemini
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $label
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_public
 */
class AiContentSetting extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'ai_content_settings';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'sort_order',
        'is_active',
        'is_public',
    ];

    /**
     * การแปลงชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * กลุ่มการตั้งค่าที่รองรับ
     */
    public const GROUPS = [
        'openai' => 'OpenAI',
        'claude' => 'Claude (Anthropic)',
        'gemini' => 'Gemini (Google)',
        'general' => 'การตั้งค่าทั่วไป',
        'quota' => 'โควต้าการใช้งาน',
        'pricing' => 'การคิดราคา',
    ];

    /**
     * ชนิดข้อมูลที่รองรับ
     */
    public const TYPES = [
        'string' => 'ข้อความ',
        'integer' => 'ตัวเลข',
        'boolean' => 'ใช่/ไม่ใช่',
        'json' => 'JSON',
        'encrypted' => 'เข้ารหัส (API Key)',
    ];

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: เฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope: เฉพาะกลุ่มที่ระบุ
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    // =========================================
    // Accessors & Mutators
    // =========================================

    /**
     * ดึงค่าที่ถอดรหัสแล้ว (สำหรับ encrypted type)
     *
     * @return mixed
     */
    public function getDecodedValueAttribute()
    {
        if (empty($this->value)) {
            return null;
        }

        return match ($this->type) {
            'encrypted' => $this->decryptValue(),
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * ตั้งค่าและเข้ารหัสถ้าจำเป็น
     *
     * @param mixed $value
     * @return void
     */
    public function setEncodedValue($value): void
    {
        $this->value = match ($this->type) {
            'encrypted' => $this->encryptValue($value),
            'json' => is_array($value) ? json_encode($value) : $value,
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * เข้ารหัสค่า
     *
     * @param string $value
     * @return string
     */
    protected function encryptValue(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return Crypt::encryptString($value);
    }

    /**
     * ถอดรหัสค่า
     *
     * @return string|null
     */
    protected function decryptValue(): ?string
    {
        if (empty($this->value)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Exception $e) {
            // ถ้าถอดรหัสไม่ได้ (อาจเป็นค่าเก่าที่ไม่ได้เข้ารหัส)
            return $this->value;
        }
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * ดึงค่า setting จาก key
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
     * @return bool
     */
    public static function setValue(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $setting->setEncodedValue($value);
        return $setting->save();
    }

    /**
     * ดึง settings ทั้งหมดในกลุ่ม
     *
     * @param string $group
     * @return \Illuminate\Support\Collection
     */
    public static function getGroup(string $group)
    {
        return static::inGroup($group)
            ->active()
            ->ordered()
            ->get()
            ->mapWithKeys(fn($item) => [$item->key => $item->decoded_value]);
    }
}
