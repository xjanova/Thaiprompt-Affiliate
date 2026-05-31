<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 🧠 FortuneKnowledge — คลังองค์ความรู้แม่หมอ (RAG)
 *
 * องค์ความรู้ที่แม่หมอ (AI) ดึงไป "ทำนายตามหน้าไพ่" — แทนคำตอบกว้างๆ
 *   - health           = ตำราสุขภาพประจำไพ่ (card_name = name_en ของไพ่)
 *   - feng_shui        = ฮวงจุ้ย (ทิศ/ธาตุ/จัดบ้าน/ของแก้/ปีชง)
 *   - guardian_spirits = เจ้าที่/พระภูมิ/ตายาย
 *   - deities          = องค์เทพ (ไดเรกทอรีเทพ + ของบูชา + คาถา)
 *   - black_magic      = ไสยศาสตร์/มนต์ดำ (ชนิดของ/อาการ/การแก้)
 *
 * แอดมินจัดการได้เต็มที่ผ่าน /admin/fortune/knowledge (เห็น/เพิ่ม/แก้/ปิด ทุก row)
 * Retrieval = เจาะจงตาม category + card_name + keyword (ดู App\Services\FortuneKnowledgeService)
 *
 * @property int $id
 * @property string $category หมวดความรู้
 * @property string|null $card_name name_en ของไพ่ (เฉพาะความรู้รายไพ่)
 * @property string|null $subject ชื่อย่อยจัดกลุ่ม
 * @property string $title หัวข้อสั้น
 * @property string|null $keywords คำค้น trigger (คั่นด้วย ,)
 * @property string $content เนื้อหาความรู้
 * @property string|null $severity ระดับความรุนแรง (low/medium/high)
 * @property int $priority ลำดับการแสดง/inject
 * @property bool $is_active เปิดใช้งาน
 * @property array|null $embedding vector (สำรอง semantic mode)
 * @property string|null $source อ้างอิง
 */
class FortuneKnowledge extends Model
{
    protected $table = 'fortune_knowledge';

    public const CATEGORY_HEALTH = 'health';

    public const CATEGORY_FENG_SHUI = 'feng_shui';

    public const CATEGORY_GUARDIAN_SPIRITS = 'guardian_spirits';

    public const CATEGORY_DEITIES = 'deities';

    public const CATEGORY_BLACK_MAGIC = 'black_magic';

    public const CATEGORY_PHYSIOGNOMY = 'physiognomy';

    public const CATEGORY_GENERAL = 'general';

    /**
     * หมวดมาตรฐาน (แอดมินเพิ่มหมวดใหม่ได้ — เป็น free string)
     */
    public const CATEGORIES = [
        self::CATEGORY_HEALTH,
        self::CATEGORY_FENG_SHUI,
        self::CATEGORY_GUARDIAN_SPIRITS,
        self::CATEGORY_DEITIES,
        self::CATEGORY_BLACK_MAGIC,
        self::CATEGORY_PHYSIOGNOMY,
        self::CATEGORY_GENERAL,
    ];

    public const CATEGORY_LABELS = [
        self::CATEGORY_HEALTH => '🩺 สุขภาพ (ตำราประจำไพ่)',
        self::CATEGORY_FENG_SHUI => '🏯 ฮวงจุ้ย',
        self::CATEGORY_GUARDIAN_SPIRITS => '🛕 เจ้าที่/พระภูมิ',
        self::CATEGORY_DEITIES => '🕉️ องค์เทพ',
        self::CATEGORY_BLACK_MAGIC => '🪬 ไสยศาสตร์/มนต์ดำ',
        self::CATEGORY_PHYSIOGNOMY => '👤 โหงวเฮ้ง/ลักษณะคน',
        self::CATEGORY_GENERAL => '📚 ทั่วไป',
    ];

    protected $fillable = [
        'category',
        'card_name',
        'subject',
        'title',
        'keywords',
        'content',
        'severity',
        'priority',
        'is_active',
        'embedding',
        'source',
    ];

    protected $casts = [
        'embedding' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * เฉพาะที่เปิดใช้งาน
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * กรองตามหมวด
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * ความรู้รายไพ่ (เช่น สุขภาพ) — ตาม name_en ของไพ่ที่เปิด
     *
     * @param  array<string>  $cardNamesEn
     */
    public function scopeForCards(Builder $query, array $cardNamesEn, string $category = self::CATEGORY_HEALTH): Builder
    {
        return $query->where('category', $category)
            ->whereIn('card_name', $cardNamesEn);
    }

    /**
     * ป้ายชื่อหมวด (ภาษาไทย)
     */
    public function categoryLabel(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }
}
