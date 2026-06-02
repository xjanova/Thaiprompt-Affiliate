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
 *   - love_relationship= ความรัก/เนื้อคู่ (สถานะรัก/คนที่เข้ามา/แววแต่งงาน) — per-card
 *   - wealth_luck      = การเงิน/โชคลาภ (กระแสเงิน/หนี้/โชค/เสี่ยงโชค) — per-card
 *   - auspicious_timing= ฤกษ์ยาม/วันมงคล (ควรลงมือเมื่อไหร่ — แต่ง/เปิดร้าน/เซ็น) — per-card
 *   - numerology       = เลขศาสตร์/เบอร์มงคล (พลังเลข/คู่เสริม/เลขห้าม) — per-card
 *   - lucky_items      = ของมงคล/สีมงคล/เครื่องราง (พกพา/แต่งกาย/เสริมดวง) — per-card
 *   - mental_emotional = จิตใจ/อารมณ์ (เครียด/วิตก/ฮีลใจ — เติม health ที่เน้นกายล้วน) — per-card
 *   - family_children  = ครอบครัว/บุตร/บริวาร (พ่อแม่/พี่น้อง/ลูก/ลูกน้อง) — per-card
 *   - travel_abroad    = เดินทาง/ต่างแดน/ย้ายถิ่น (ทริป/วีซ่า/ย้ายประเทศ) — per-card
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

    public const CATEGORY_AGE_RANGE = 'age_range';

    public const CATEGORY_TIMING = 'timing';

    public const CATEGORY_CAREER_STUDY = 'career_study';

    public const CATEGORY_BUSINESS_WORK = 'business_work';

    public const CATEGORY_SPIRITUAL_CALLING = 'spiritual_calling';

    public const CATEGORY_PAST_LIFE = 'past_life';

    public const CATEGORY_LOVE_RELATIONSHIP = 'love_relationship';

    public const CATEGORY_WEALTH_LUCK = 'wealth_luck';

    public const CATEGORY_AUSPICIOUS_TIMING = 'auspicious_timing';

    public const CATEGORY_NUMEROLOGY = 'numerology';

    public const CATEGORY_LUCKY_ITEMS = 'lucky_items';

    public const CATEGORY_MENTAL_EMOTIONAL = 'mental_emotional';

    public const CATEGORY_FAMILY_CHILDREN = 'family_children';

    public const CATEGORY_TRAVEL_ABROAD = 'travel_abroad';

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
        self::CATEGORY_AGE_RANGE,
        self::CATEGORY_TIMING,
        self::CATEGORY_CAREER_STUDY,
        self::CATEGORY_BUSINESS_WORK,
        self::CATEGORY_SPIRITUAL_CALLING,
        self::CATEGORY_PAST_LIFE,
        self::CATEGORY_LOVE_RELATIONSHIP,
        self::CATEGORY_WEALTH_LUCK,
        self::CATEGORY_AUSPICIOUS_TIMING,
        self::CATEGORY_NUMEROLOGY,
        self::CATEGORY_LUCKY_ITEMS,
        self::CATEGORY_MENTAL_EMOTIONAL,
        self::CATEGORY_FAMILY_CHILDREN,
        self::CATEGORY_TRAVEL_ABROAD,
        self::CATEGORY_GENERAL,
    ];

    public const CATEGORY_LABELS = [
        self::CATEGORY_HEALTH => '🩺 สุขภาพ (ตำราประจำไพ่)',
        self::CATEGORY_FENG_SHUI => '🏯 ฮวงจุ้ย',
        self::CATEGORY_GUARDIAN_SPIRITS => '🛕 เจ้าที่/พระภูมิ',
        self::CATEGORY_DEITIES => '🕉️ องค์เทพ',
        self::CATEGORY_BLACK_MAGIC => '🪬 ไสยศาสตร์/มนต์ดำ',
        self::CATEGORY_PHYSIOGNOMY => '👤 โหงวเฮ้ง/ลักษณะคน',
        self::CATEGORY_AGE_RANGE => '🎂 ช่วงอายุ',
        self::CATEGORY_TIMING => '⏳ สถานการณ์/จังหวะเวลา',
        self::CATEGORY_CAREER_STUDY => '🎓 การศึกษา/อาชีพ',
        self::CATEGORY_BUSINESS_WORK => '💼 ธุรกิจ/การงาน',
        self::CATEGORY_SPIRITUAL_CALLING => '🔮 สายญาณ/ผู้มีองค์/ภารกิจสวรรค์',
        self::CATEGORY_PAST_LIFE => '🕉️ อดีตชาติ/กรรมเก่า',
        self::CATEGORY_LOVE_RELATIONSHIP => '❤️ ความรัก/เนื้อคู่',
        self::CATEGORY_WEALTH_LUCK => '💰 การเงิน/โชคลาภ',
        self::CATEGORY_AUSPICIOUS_TIMING => '📅 ฤกษ์ยาม/วันมงคล',
        self::CATEGORY_NUMEROLOGY => '🔢 เลขศาสตร์/เบอร์มงคล',
        self::CATEGORY_LUCKY_ITEMS => '🧿 ของมงคล/สีมงคล/เครื่องราง',
        self::CATEGORY_MENTAL_EMOTIONAL => '🧠 จิตใจ/อารมณ์',
        self::CATEGORY_FAMILY_CHILDREN => '👨‍👩‍👧 ครอบครัว/บุตร/บริวาร',
        self::CATEGORY_TRAVEL_ABROAD => '✈️ เดินทาง/ต่างแดน/ย้ายถิ่น',
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
