<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * LazadaCategoryMap — แปลงเลขหมวดของ Lazada เป็นหมวดบนหน้าร้านเรา
 *
 * ฟีด Lazada คืน `categoryL1` เป็นเลขของเขาเอง (3008, 42062201, 10100083 …)
 * ตารางนี้บอกว่าเลขนั้นควรลงหมวดไหนบนเว็บเรา
 *
 * @property int $id
 * @property string $lazada_category_l1 เลขหมวด Lazada
 * @property string|null $lazada_category_name ชื่อหมวดฝั่ง Lazada
 * @property int $product_category_id หมวดปลายทางบนเว็บเรา
 * @property string $confidence derived | manual
 * @property int $sample_count
 */
class LazadaCategoryMap extends Model
{
    protected $table = 'lazada_category_map';

    protected $fillable = [
        'lazada_category_l1',
        'lazada_category_name',
        'product_category_id',
        'confidence',
        'sample_count',
    ];

    protected $casts = [
        'sample_count' => 'integer',
    ];

    /** จับคู่โดยเดาจากเสียงข้างมากของข้อมูลเดิม */
    public const CONFIDENCE_DERIVED = 'derived';

    /** คนจับคู่เอง — เชื่อถือได้กว่า ห้ามให้ตัว derive ทับ */
    public const CONFIDENCE_MANUAL = 'manual';

    /** อายุแคชของแผนที่หมวด (แผนที่เปลี่ยนไม่บ่อย แต่ต้องล้างเมื่อ owner แก้) */
    private const CACHE_TTL_MINUTES = 60;

    private const CACHE_KEY = 'lazada:category_map:all';

    /**
     * หมวดปลายทางบนหน้าร้าน
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * แปลงเลขหมวด Lazada → id หมวดบนเว็บเรา
     *
     * @param  string|int|null  $lazadaCategoryL1  เลขหมวดจากฟีด
     * @return int|null id หมวดของเรา หรือ null ถ้ายังไม่เคยจับคู่ (ให้ caller ตกไปหมวดสำรอง)
     */
    public static function resolve(string|int|null $lazadaCategoryL1): ?int
    {
        $key = trim((string) $lazadaCategoryL1);
        if ($key === '') {
            return null;
        }

        return static::allPairs()[$key] ?? null;
    }

    /**
     * แผนที่ทั้งหมดในรูป [เลขหมวด Lazada => id หมวดเรา]
     *
     * ⚠️ อย่าเรียก resolve() ในลูปโดยไม่ผ่านแคช — การนำเข้ารอบหนึ่งแปลงหลายร้อยชิ้น
     *
     * @return array<string,int>
     */
    public static function allPairs(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => static::query()
                ->pluck('product_category_id', 'lazada_category_l1')
                ->map(fn ($v) => (int) $v)
                ->all()
        );
    }

    /**
     * ล้างแคช — ต้องเรียกทุกครั้งที่ owner แก้แผนที่ในหลังบ้าน
     * ไม่งั้นแก้แล้วของยังลงหมวดเดิมไปอีกชั่วโมง
     */
    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * เขียนแผนที่ 1 คู่ — ของที่คนจับคู่เองห้ามถูกตัว derive ทับ
     *
     * @param  string|int  $lazadaCategoryL1  เลขหมวด Lazada
     * @param  int  $productCategoryId  หมวดปลายทาง
     * @param  string  $confidence  derived | manual
     * @param  int  $sampleCount  เดาจากสินค้ากี่ชิ้น
     */
    public static function put(
        string|int $lazadaCategoryL1,
        int $productCategoryId,
        string $confidence = self::CONFIDENCE_DERIVED,
        int $sampleCount = 0,
        ?string $lazadaCategoryName = null
    ): ?self {
        $key = trim((string) $lazadaCategoryL1);
        if ($key === '') {
            return null;
        }

        $existing = static::where('lazada_category_l1', $key)->first();

        // 🔒 คนจับคู่เองแล้ว = คำตอบสุดท้าย ตัวเดาอัตโนมัติห้ามเขียนทับ
        if ($existing
            && $existing->confidence === self::CONFIDENCE_MANUAL
            && $confidence !== self::CONFIDENCE_MANUAL) {
            return $existing;
        }

        $row = static::updateOrCreate(
            ['lazada_category_l1' => $key],
            array_filter([
                'product_category_id' => $productCategoryId,
                'confidence' => $confidence,
                'sample_count' => $sampleCount,
                'lazada_category_name' => $lazadaCategoryName,
            ], fn ($v) => $v !== null)
        );

        static::flushCache();

        return $row;
    }
}
