<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ตัวเลือกหนึ่งรายการในกลุ่มตัวเลือกสินค้าตลาดสด (เช่น "กุ้ง +20")
 *
 * ราคาเพิ่ม (price_delta) ใช้คำนวณฝั่งเซิร์ฟเวอร์เท่านั้น — ไม่เชื่อราคาที่ client ส่งมา
 *
 * @property int $id
 * @property int $group_id
 * @property int $listing_id
 * @property string $name
 * @property float $price_delta
 * @property string|null $image_url
 * @property bool $is_available
 * @property int $sort_order
 */
class FreshMarketListingOption extends Model
{
    protected $table = 'fresh_market_listing_options';

    protected $fillable = [
        'group_id',
        'listing_id',
        'name',
        'price_delta',
        'image_url',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price_delta' => 'decimal:2',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(FreshMarketListingOptionGroup::class, 'group_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(FreshMarketListing::class, 'listing_id');
    }

    /**
     * ข้อมูลสำหรับ API / หน้าเว็บ
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'group_id' => (int) $this->group_id,
            'name' => $this->name,
            'price_delta' => round((float) $this->price_delta, 2),
            'image_url' => $this->image_url,
            'is_available' => (bool) $this->is_available,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
