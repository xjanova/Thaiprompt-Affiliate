<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneResponseTemplate Model
 *
 * เทมเพลตการตอบกลับคำทำนาย ใช้สำหรับจัดรูปแบบข้อความที่ส่งกลับผู้ใช้
 * รองรับ placeholders, รูปภาพส่วนหัว/ท้าย (เช่น QR Code ชำระเงิน)
 *
 * ประเภทเทมเพลต:
 * - basic: คำทำนายพื้นฐาน
 * - deep: คำทำนายเชิงลึก
 * - welcome: ข้อความต้อนรับ
 * - payment: แจ้งชำระเงิน (มีรูป QR)
 * - limit_exceeded: หมดโควต้าฟรี
 * - error: เกิดข้อผิดพลาด
 *
 * @property int $id
 * @property string $name ชื่อเทมเพลต
 * @property string $slug Key สำหรับอ้างอิง
 * @property string $type ประเภท: basic/deep/welcome/payment/limit_exceeded/error
 * @property string $body เนื้อหาเทมเพลต (รองรับ placeholders)
 * @property string|null $header_text ข้อความส่วนหัว
 * @property string|null $footer_text ข้อความส่วนท้าย
 * @property string|null $header_image_url URL รูปส่วนหัว
 * @property string|null $footer_image_url URL รูปส่วนท้าย (เช่น QR Code)
 * @property array|null $available_placeholders รายการ placeholders ที่ใช้ได้
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_default เป็นเทมเพลตเริ่มต้น
 * @property int $sort_order ลำดับการแสดง
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FortuneResponseTemplate extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'fortune_response_templates';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'body',
        'header_text',
        'footer_text',
        'header_image_url',
        'footer_image_url',
        'available_placeholders',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'available_placeholders' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'basic',
        'is_active' => true,
        'is_default' => false,
        'sort_order' => 0,
    ];

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * กรองเฉพาะที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * กรองตามประเภท
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * กรองเฉพาะเทมเพลตเริ่มต้น
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ============================================================
    // Static Methods
    // ============================================================

    /**
     * ดึงเทมเพลตเริ่มต้นตามประเภท
     *
     * @param string $type ประเภท: basic/deep/welcome/payment/limit_exceeded/error
     * @return self|null
     */
    public static function getDefault(string $type): ?self
    {
        return static::active()
            ->ofType($type)
            ->default()
            ->first()
            ?? static::active()
                ->ofType($type)
                ->orderBy('sort_order')
                ->first();
    }

    /**
     * ดึงเทมเพลตตาม slug
     *
     * @param string $slug
     * @return self|null
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * ดึงเทมเพลตทั้งหมดตามประเภท
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByType(string $type)
    {
        return static::active()
            ->ofType($type)
            ->orderBy('sort_order')
            ->get();
    }

    // ============================================================
    // Template Rendering
    // ============================================================

    /**
     * แทนที่ placeholders ในเทมเพลตด้วยค่าจริง
     *
     * Placeholders ที่รองรับ:
     * - {response} คำทำนายจาก AI
     * - {user_name} ชื่อผู้ใช้
     * - {date} วันที่ทำนาย (รูปแบบ: 29 ม.ค. 2569)
     * - {date_thai} วันที่แบบไทย (รูปแบบ: วันพุธที่ 29 มกราคม 2569)
     * - {questions} คำถาม (รวมเป็นข้อความเดียว)
     * - {reading_type} ประเภทคำทำนาย (พื้นฐาน/เชิงลึก)
     * - {reading_id} ID ของการทำนาย
     * - {rate_url} URL ให้คะแนน
     * - {register_url} URL สมัครสมาชิก
     * - {payment_url} URL ชำระเงิน
     * - {remaining_free} จำนวนครั้งฟรีที่เหลือ
     * - {max_free} จำนวนครั้งฟรีสูงสุด
     * - {price} ราคาต่อครั้ง
     *
     * @param array $data ข้อมูลสำหรับแทนที่ placeholders
     * @return string ข้อความที่แทนที่แล้ว
     */
    public function render(array $data = []): string
    {
        $output = '';

        // ข้อความส่วนหัว
        if (!empty($this->header_text)) {
            $output .= $this->replacePlaceholders($this->header_text, $data) . "\n\n";
        }

        // เนื้อหาหลัก
        $output .= $this->replacePlaceholders($this->body, $data);

        // ข้อความส่วนท้าย
        if (!empty($this->footer_text)) {
            $output .= "\n\n" . $this->replacePlaceholders($this->footer_text, $data);
        }

        return trim($output);
    }

    /**
     * แทนที่ placeholders ในข้อความ
     *
     * @param string $text ข้อความต้นฉบับ
     * @param array $data ข้อมูลสำหรับแทนที่
     * @return string
     */
    protected function replacePlaceholders(string $text, array $data): string
    {
        $replacements = [
            '{response}' => $data['response'] ?? '',
            '{user_name}' => $data['user_name'] ?? 'ท่าน',
            '{date}' => $data['date'] ?? now()->format('d/m/Y'),
            '{date_thai}' => $data['date_thai'] ?? $this->formatThaiDate(now()),
            '{questions}' => $data['questions'] ?? '',
            '{reading_type}' => $data['reading_type'] ?? 'พื้นฐาน',
            '{reading_id}' => $data['reading_id'] ?? '',
            '{rate_url}' => $data['rate_url'] ?? url('/fortune/rate'),
            '{register_url}' => $data['register_url'] ?? url('/register'),
            '{payment_url}' => $data['payment_url'] ?? url('/payment'),
            '{remaining_free}' => $data['remaining_free'] ?? '0',
            '{max_free}' => $data['max_free'] ?? '3',
            '{price}' => $data['price'] ?? '0',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );
    }

    /**
     * จัดรูปแบบวันที่เป็นภาษาไทย
     *
     * @param \Carbon\Carbon $date
     * @return string เช่น "29 มกราคม 2569"
     */
    protected function formatThaiDate($date): string
    {
        $thaiMonths = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
            4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
            7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
            10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        $day = $date->day;
        $month = $thaiMonths[$date->month] ?? '';
        $year = $date->year + 543; // พ.ศ.

        return "{$day} {$month} {$year}";
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * มีรูปส่วนหัวหรือไม่
     */
    public function hasHeaderImage(): bool
    {
        return !empty($this->header_image_url);
    }

    /**
     * มีรูปส่วนท้ายหรือไม่ (เช่น QR Code)
     */
    public function hasFooterImage(): bool
    {
        return !empty($this->footer_image_url);
    }

    /**
     * ดึง label ประเภทเทมเพลต (ภาษาไทย)
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'basic' => 'คำทำนายพื้นฐาน',
            'deep' => 'คำทำนายเชิงลึก',
            'welcome' => 'ข้อความต้อนรับ',
            'payment' => 'แจ้งชำระเงิน',
            'limit_exceeded' => 'หมดโควต้าฟรี',
            'error' => 'ข้อผิดพลาด',
            default => $this->type,
        };
    }

    /**
     * ดึง icon ประเภทเทมเพลต
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'basic' => '🔮',
            'deep' => '🌟',
            'welcome' => '👋',
            'payment' => '💰',
            'limit_exceeded' => '⏳',
            'error' => '❌',
            default => '📝',
        };
    }

    /**
     * ตั้งเป็นเทมเพลตเริ่มต้น (ยกเลิกอันเดิมของประเภทเดียวกัน)
     */
    public function setAsDefault(): void
    {
        // ยกเลิก default อันเดิมของประเภทเดียวกัน
        static::where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
