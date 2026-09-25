<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * กลุ่มตัวเลือกของสินค้าตลาดสด (เช่น "เลือกเนื้อสัตว์" บังคับเลือก 1 / "เพิ่มเติม" เลือกได้หลายอย่าง)
 *
 * กติกา (ตรวจฝั่งเซิร์ฟเวอร์เสมอใน FreshMarketOptionService::resolveLine):
 *  - single: เลือกได้ไม่เกิน 1 · บังคับ = ต้องเลือกพอดี 1
 *  - multi : จำนวนที่เลือกต้องอยู่ระหว่าง minRequired() ถึง max_select (null = ไม่จำกัด)
 *
 * @property int $id
 * @property int $listing_id
 * @property string $name
 * @property string $selection_type single|multi
 * @property bool $is_required
 * @property int $min_select
 * @property int|null $max_select
 * @property int $sort_order
 */
class FreshMarketListingOptionGroup extends Model
{
    public const TYPE_SINGLE = 'single';

    public const TYPE_MULTI = 'multi';

    protected $table = 'fresh_market_listing_option_groups';

    protected $fillable = [
        'listing_id',
        'name',
        'selection_type',
        'is_required',
        'min_select',
        'max_select',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'min_select' => 'integer',
        'max_select' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * ปรับค่ากติกาให้สอดคล้องกันทุกครั้งที่บันทึก (single = สูงสุด 1, บังคับ = ขั้นต่ำอย่างน้อย 1)
     */
    protected static function booted(): void
    {
        static::saving(function (self $group) {
            $type = $group->selection_type === self::TYPE_MULTI ? self::TYPE_MULTI : self::TYPE_SINGLE;
            $group->selection_type = $type;

            $min = max(0, (int) $group->min_select);
            $max = $group->max_select !== null ? max(0, (int) $group->max_select) : null;

            if ($type === self::TYPE_SINGLE) {
                $max = 1;
                $min = $group->is_required ? 1 : 0;
            } else {
                if ($group->is_required) {
                    $min = max(1, $min);
                }

                // max = 0 ถือว่าไม่จำกัด · max ต้องไม่น้อยกว่า min
                if ($max === 0) {
                    $max = null;
                }

                if ($max !== null && $max < max(1, $min)) {
                    $max = max(1, $min);
                }
            }

            // ขั้นต่ำ > 0 เท่ากับบังคับเลือก
            $group->is_required = $min > 0;
            $group->min_select = $min;
            $group->max_select = $max;
        });
    }

    // ===== Relationships =====

    public function listing(): BelongsTo
    {
        return $this->belongsTo(FreshMarketListing::class, 'listing_id');
    }

    /**
     * ตัวเลือกในกลุ่ม (เรียงตาม sort_order)
     */
    public function options(): HasMany
    {
        return $this->hasMany(FreshMarketListingOption::class, 'group_id')->orderBy('sort_order')->orderBy('id');
    }

    // ===== Helpers =====

    public function isMulti(): bool
    {
        return $this->selection_type === self::TYPE_MULTI;
    }

    /**
     * จำนวนขั้นต่ำที่ต้องเลือก
     */
    public function minRequired(): int
    {
        return max((int) $this->min_select, $this->is_required ? 1 : 0);
    }

    /**
     * จำนวนสูงสุดที่เลือกได้ (null = ไม่จำกัด)
     */
    public function maxAllowed(): ?int
    {
        if (! $this->isMulti()) {
            return 1;
        }

        return $this->max_select !== null && (int) $this->max_select > 0 ? (int) $this->max_select : null;
    }

    /**
     * คำอธิบายกติกาภาษาไทย (แสดงใต้ชื่อกลุ่ม)
     */
    public function ruleLabel(): string
    {
        $min = $this->minRequired();
        $max = $this->maxAllowed();

        if (! $this->isMulti()) {
            return $min > 0 ? 'เลือก 1 อย่าง (บังคับ)' : 'เลือกได้ 1 อย่าง (ไม่บังคับ)';
        }

        if ($min > 0 && $max !== null) {
            return $min === $max ? "เลือก {$min} อย่าง (บังคับ)" : "เลือก {$min}-{$max} อย่าง (บังคับ)";
        }

        if ($min > 0) {
            return "เลือกอย่างน้อย {$min} อย่าง (บังคับ)";
        }

        return $max !== null ? "เลือกได้สูงสุด {$max} อย่าง (ไม่บังคับ)" : 'เลือกได้หลายอย่าง (ไม่บังคับ)';
    }

    /**
     * ข้อมูลสำหรับ API / หน้าเว็บ (ตัวเลขเป็น number)
     */
    public function toApiArray(bool $includeUnavailable = true): array
    {
        $options = $this->relationLoaded('options') ? $this->options : $this->options()->get();

        if (! $includeUnavailable) {
            $options = $options->where('is_available', true);
        }

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'selection_type' => $this->selection_type,
            'is_required' => (bool) $this->is_required,
            'min_select' => $this->minRequired(),
            'max_select' => $this->maxAllowed(),
            'rule_label' => $this->ruleLabel(),
            'sort_order' => (int) $this->sort_order,
            'options' => $options->map(fn (FreshMarketListingOption $o) => $o->toApiArray())->values()->all(),
        ];
    }
}
