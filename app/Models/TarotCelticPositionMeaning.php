<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TarotCelticPositionMeaning Model
 *
 * โมเดลเก็บคำทำนายไพ่ยิปซีในรูปแบบ Celtic Cross แบบละเอียดที่สุด
 *
 * ครอบคลุม:
 * - 78 ใบ × 10 ตำแหน่ง × 2 ทิศทาง × 5 หมวด = 7,800 entries
 *
 * @property int $id
 * @property int $card_id
 * @property int $position_number ตำแหน่ง 1-10
 * @property string $position_slug
 * @property string $position_name_th
 * @property bool $is_reversed
 * @property int|null $category_id
 * @property string $category_slug
 * @property string $core_meaning_th
 * @property string $detailed_interpretation_th
 * @property array|null $adjacent_influences
 * @property string|null $secret_keys_th
 * @property string $advice_th
 * @property array|null $keywords_th
 * @property int $intensity_level 1-5
 * @property string $emotional_tone positive|neutral|negative|mixed
 * @property bool $is_active
 */
class TarotCelticPositionMeaning extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     */
    protected $table = 'tarot_celtic_position_meanings';

    /**
     * คอลัมน์ที่อนุญาตให้ mass assignment
     */
    protected $fillable = [
        'card_id',
        'position_number',
        'position_slug',
        'position_name_th',
        'is_reversed',
        'category_id',
        'category_slug',
        'core_meaning_th',
        'detailed_interpretation_th',
        'adjacent_influences',
        'secret_keys_th',
        'advice_th',
        'keywords_th',
        'intensity_level',
        'emotional_tone',
        'is_active',
    ];

    /**
     * Type casting
     */
    protected $casts = [
        'is_reversed' => 'boolean',
        'is_active' => 'boolean',
        'position_number' => 'integer',
        'intensity_level' => 'integer',
        'adjacent_influences' => 'array',
        'keywords_th' => 'array',
    ];

    /**
     * ตำแหน่ง Celtic Cross ทั้ง 10 ตำแหน่ง
     *
     * @var array<int, array<string, string>>
     */
    public const CELTIC_POSITIONS = [
        1 => ['slug' => 'present', 'name_th' => 'ปัจจุบัน', 'description_th' => 'สถานการณ์ปัจจุบันที่ผู้ถามกำลังเผชิญอยู่'],
        2 => ['slug' => 'challenge', 'name_th' => 'อุปสรรค', 'description_th' => 'สิ่งที่ขัดขวางหรือท้าทาย (ไพ่นี้ตีความตั้งตรงเสมอ)'],
        3 => ['slug' => 'past', 'name_th' => 'อดีต', 'description_th' => 'รากฐานในอดีตที่ส่งผลต่อปัจจุบัน'],
        4 => ['slug' => 'future', 'name_th' => 'อนาคต', 'description_th' => 'สิ่งที่กำลังจะเกิดขึ้นในระยะใกล้'],
        5 => ['slug' => 'above', 'name_th' => 'เป้าหมาย', 'description_th' => 'สิ่งที่ผู้ถามมุ่งหวังหรือจิตสำนึก'],
        6 => ['slug' => 'below', 'name_th' => 'รากฐาน', 'description_th' => 'จิตใต้สำนึก/รากฐานพลังภายใน'],
        7 => ['slug' => 'advice', 'name_th' => 'คำแนะนำ', 'description_th' => 'ตัวคุณ - ทัศนคติของผู้ถาม'],
        8 => ['slug' => 'external', 'name_th' => 'ภายนอก', 'description_th' => 'อิทธิพลจากคนรอบข้าง/สิ่งแวดล้อม'],
        9 => ['slug' => 'hopes', 'name_th' => 'ความหวัง', 'description_th' => 'ความหวังและความกลัว'],
        10 => ['slug' => 'outcome', 'name_th' => 'ผลลัพธ์', 'description_th' => 'ผลลัพธ์สุดท้าย'],
    ];

    /**
     * หมวดหมู่การทำนายทั้ง 5 หมวด
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'general' => 'การทำนายทั่วไป',
        'love-relationships' => 'ความรักและความสัมพันธ์',
        'career-finance' => 'การงานและการเงิน',
        'personal-growth' => 'การพัฒนาตนเอง',
        'health-wellness' => 'สุขภาพและความเป็นอยู่',
    ];

    /**
     * ความสัมพันธ์กับ TarotCard
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(TarotCard::class, 'card_id');
    }

    /**
     * ความสัมพันธ์กับ TarotReadingCategory
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TarotReadingCategory::class, 'category_id');
    }

    /**
     * Scope: หาคำทำนายตาม card_id และ position
     */
    public function scopeForPosition($query, int $cardId, int $position, bool $isReversed = false, string $categorySlug = 'general')
    {
        return $query
            ->where('card_id', $cardId)
            ->where('position_number', $position)
            ->where('is_reversed', $isReversed)
            ->where('category_slug', $categorySlug)
            ->where('is_active', true);
    }

    /**
     * Scope: หาตาม position
     */
    public function scopeByPosition($query, int $position)
    {
        return $query->where('position_number', $position);
    }

    /**
     * Scope: เฉพาะหัวตั้ง
     */
    public function scopeUpright($query)
    {
        return $query->where('is_reversed', false);
    }

    /**
     * Scope: เฉพาะกลับหัว
     */
    public function scopeReversed($query)
    {
        return $query->where('is_reversed', true);
    }

    /**
     * Scope: ตามหมวดหมู่
     */
    public function scopeForCategory($query, string $categorySlug)
    {
        return $query->where('category_slug', $categorySlug);
    }

    /**
     * Scope: เฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
