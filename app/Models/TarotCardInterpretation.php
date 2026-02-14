<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model สำหรับเก็บคำทำนายที่ปรับแต่งได้ตามหมวดหมู่
 *
 * ตารางนี้ช่วยให้แอดมินสามารถตั้งค่าคำทำนายเฉพาะสำหรับแต่ละไพ่
 * ในแต่ละหมวดหมู่ เช่น ไพ่ The Fool จะมีคำทำนายที่ต่างกัน
 * สำหรับหมวดความรัก vs หมวดการงาน
 *
 * @property int $id
 * @property int $card_id
 * @property int $category_id
 * @property string|null $upright_interpretation_th
 * @property string|null $upright_interpretation_en
 * @property string|null $reversed_interpretation_th
 * @property string|null $reversed_interpretation_en
 * @property string|null $advice_th
 * @property string|null $advice_en
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TarotCardInterpretation extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'tarot_card_interpretations';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'card_id',
        'category_id',
        'upright_interpretation_th',
        'upright_interpretation_en',
        'reversed_interpretation_th',
        'reversed_interpretation_en',
        'advice_th',
        'advice_en',
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
     * ดึงคำทำนายหัวตั้งตามภาษา
     *
     * @param  string  $language  รหัสภาษา (th หรือ en)
     * @return string คำทำนาย
     */
    public function getUprightInterpretation(string $language = 'th'): string
    {
        $field = "upright_interpretation_{$language}";

        return $this->$field ?? $this->upright_interpretation_th ?? '';
    }

    /**
     * ดึงคำทำนายกลับหัวตามภาษา
     *
     * @param  string  $language  รหัสภาษา (th หรือ en)
     * @return string คำทำนาย
     */
    public function getReversedInterpretation(string $language = 'th'): string
    {
        $field = "reversed_interpretation_{$language}";

        return $this->$field ?? $this->reversed_interpretation_th ?? '';
    }

    /**
     * ดึงคำแนะนำตามภาษา
     *
     * @param  string  $language  รหัสภาษา (th หรือ en)
     * @return string คำแนะนำ
     */
    public function getAdvice(string $language = 'th'): string
    {
        $field = "advice_{$language}";

        return $this->$field ?? $this->advice_th ?? '';
    }

    /**
     * ค้นหาหรือสร้าง interpretation สำหรับไพ่และหมวด
     *
     * @param  int  $cardId  ID ของไพ่
     * @param  int  $categoryId  ID ของหมวดหมู่
     */
    public static function findOrCreateFor(int $cardId, int $categoryId): self
    {
        return static::firstOrCreate([
            'card_id' => $cardId,
            'category_id' => $categoryId,
        ]);
    }

    /**
     * ค้นหา interpretation สำหรับไพ่และหมวด
     *
     * @param  int  $cardId  ID ของไพ่
     * @param  int  $categoryId  ID ของหมวดหมู่
     */
    public static function findFor(int $cardId, int $categoryId): ?self
    {
        return static::where('card_id', $cardId)
            ->where('category_id', $categoryId)
            ->first();
    }

    /**
     * ตรวจสอบว่ามีคำทำนายที่กำหนดเองหรือไม่
     */
    public function hasCustomInterpretation(): bool
    {
        return ! empty($this->upright_interpretation_th)
            || ! empty($this->upright_interpretation_en)
            || ! empty($this->reversed_interpretation_th)
            || ! empty($this->reversed_interpretation_en);
    }
}
