<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneAdminQA Model
 *
 * เก็บคู่ "คำถามลูกค้า ↔ คำตอบแอดมิน" สำหรับระบบ RAG
 *
 * @property int $id
 * @property string $q_text คำถามลูกค้า
 * @property array|null $q_embedding vector embedding 768-dim
 * @property string $a_text คำตอบแอดมิน
 * @property string|null $category หมวดของคู่ Q&A (pre_purchase, birthdate_help, ...)
 * @property int|null $admin_user_id user id ของแอดมิน (null = FB Page Inbox)
 * @property string $source_platform facebook|line|manual
 * @property string|null $source_user_id ลูกค้าที่ได้รับคำตอบ (FB PSID / LINE userId)
 * @property string|null $page_id Facebook Page ID ที่ admin ตอบจากเพจไหน
 * @property int|null $reading_id ผูกกับ FortuneReading ที่ active ณ ขณะ admin ตอบ
 * @property string|null $reading_type ประเภทแพคเกจ (basic, deep, celtic_cross, free_card)
 * @property array|null $context_json บริบทเพิ่มเติม (prev_turns, conversation_status, etc.)
 * @property float|null $similarity_threshold override threshold (null = ใช้ default)
 * @property bool $is_active
 * @property int $hit_count
 * @property \Carbon\Carbon|null $last_hit_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneAdminQA extends Model
{
    /**
     * ชื่อตาราง
     */
    protected $table = 'fortune_admin_qa';

    /**
     * Source platform enums
     */
    public const SOURCE_FACEBOOK = 'facebook';

    public const SOURCE_LINE = 'line';

    public const SOURCE_MANUAL = 'manual';

    /**
     * Default threshold ถ้าไม่ override ใน row
     */
    public const DEFAULT_THRESHOLD = 0.78;

    /**
     * 🏷 Category constants
     *
     * derive จาก state machine ตอน capture (FortuneAdminQAClassifier)
     * ใช้ pre-filter ก่อน cosine retrieve → AI ค้นถูกบริบทเร็วขึ้น
     */
    public const CATEGORY_PRE_PURCHASE = 'pre_purchase';         // ลูกค้ายังไม่มี reading — ถามราคา/บริการ/ฟรี

    public const CATEGORY_BIRTHDATE_HELP = 'birthdate_help';     // กำลังเก็บวันเกิด — ถามวิธีใส่/รูปแบบ

    public const CATEGORY_QUESTION_INPUT = 'question_input';     // กำลังเก็บคำถาม — ถามว่าถามอะไรได้/กี่คำถาม

    public const CATEGORY_PRE_PAYMENT = 'pre_payment';           // มีบิลแล้ว ยังไม่จ่าย — ถามวิธีโอน/QR/ราคา

    public const CATEGORY_PAYMENT_CONFIRM = 'payment_confirm';   // จ่ายแล้ว/ส่งสลิป — ยืนยันการรับเงิน

    public const CATEGORY_POST_READING = 'post_reading';         // อ่านคำทำนายแล้ว — ถามต่อ/รีวิว

    public const CATEGORY_CELTIC_PICKING = 'celtic_picking';     // 99฿ เลือกไพ่ — ถามวิธีเลือก/ใบไหน

    public const CATEGORY_GENERAL = 'general';                   // ไม่เข้าหมวดไหน — สวัสดี/ขอบคุณ/ชวนเพื่อน

    /**
     * 📚 List of all category keys (สำหรับ validation + dropdown UI)
     *
     * @var array<string>
     */
    public const CATEGORIES = [
        self::CATEGORY_PRE_PURCHASE,
        self::CATEGORY_BIRTHDATE_HELP,
        self::CATEGORY_QUESTION_INPUT,
        self::CATEGORY_PRE_PAYMENT,
        self::CATEGORY_PAYMENT_CONFIRM,
        self::CATEGORY_POST_READING,
        self::CATEGORY_CELTIC_PICKING,
        self::CATEGORY_GENERAL,
    ];

    /**
     * 🏷 Category labels (Thai) — สำหรับแสดงใน admin UI
     *
     * @var array<string, string>
     */
    public const CATEGORY_LABELS = [
        self::CATEGORY_PRE_PURCHASE => 'ก่อนซื้อ (สอบถามบริการ)',
        self::CATEGORY_BIRTHDATE_HELP => 'ช่วยกรอกวันเกิด',
        self::CATEGORY_QUESTION_INPUT => 'ช่วยตั้งคำถาม',
        self::CATEGORY_PRE_PAYMENT => 'รอชำระเงิน',
        self::CATEGORY_PAYMENT_CONFIRM => 'ยืนยันชำระแล้ว',
        self::CATEGORY_POST_READING => 'หลังอ่านคำทำนาย',
        self::CATEGORY_CELTIC_PICKING => 'Celtic 99฿ — เลือกไพ่',
        self::CATEGORY_GENERAL => 'ทั่วไป (ทักทาย/อื่นๆ)',
    ];

    /**
     * Mass-assignable
     *
     * @var array<string>
     */
    protected $fillable = [
        'q_text',
        'q_embedding',
        'a_text',
        'category',
        'admin_user_id',
        'source_platform',
        'source_user_id',
        'page_id',
        'reading_id',
        'reading_type',
        'context_json',
        'similarity_threshold',
        'is_active',
        'hit_count',
        'last_hit_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'q_embedding' => 'array',
        'context_json' => 'array',
        'similarity_threshold' => 'float',
        'is_active' => 'boolean',
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: admin user
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Scope: active rows only (สำหรับ retrieve)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: has embedding (ใช้ retrieve ได้)
     */
    public function scopeHasEmbedding($query)
    {
        return $query->whereNotNull('q_embedding');
    }

    /**
     * Scope: filter by category (สำหรับ pre-filter ก่อน cosine retrieve)
     *
     * @param  string|null  $category  ถ้า null → ไม่ filter (legacy behavior)
     */
    public function scopeByCategory($query, ?string $category)
    {
        if ($category === null || $category === '') {
            return $query;
        }

        // รวม legacy data (category=null) เข้า general
        if ($category === self::CATEGORY_GENERAL) {
            return $query->where(function ($q) {
                $q->where('category', self::CATEGORY_GENERAL)
                    ->orWhereNull('category');
            });
        }

        return $query->where('category', $category);
    }

    /**
     * Scope: เก็บแค่ rows ที่สดใหม่ (ภายใน N เดือน)
     *
     * @param  int  $months  default 6 เดือน
     */
    public function scopeRecent($query, int $months = 6)
    {
        return $query->where('created_at', '>=', now()->subMonths($months));
    }

    /**
     * Helper: คืน label ภาษาไทยของ category ปัจจุบัน
     */
    public function categoryLabel(): string
    {
        if (! $this->category) {
            return '(ไม่ระบุ)';
        }

        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    /**
     * บันทึก hit (ถูก retrieve)
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
        $this->forceFill(['last_hit_at' => now()])->save();
    }
}
