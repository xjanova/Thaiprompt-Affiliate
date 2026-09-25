<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * รายการในตะกร้าตลาดสด (ฝั่งเซิร์ฟเวอร์ = ข้อมูลหลักของแอปและเว็บ)
 *
 * ตะกร้าแยกตามร้าน (seller_id) — ชำระเงินทีละร้าน ได้ออเดอร์ละ 1 ร้าน
 * ราคาไม่ถูกเก็บในตะกร้า: คำนวณใหม่จากราคาสินค้า/ตัวเลือกปัจจุบันทุกครั้ง
 *
 * @property int $id
 * @property int $user_id
 * @property int $seller_id
 * @property int $listing_id
 * @property int $quantity
 * @property array|null $option_ids
 * @property string $line_key
 * @property string|null $note
 */
class FreshMarketCartItem extends Model
{
    protected $table = 'fresh_market_cart_items';

    protected $fillable = [
        'user_id',
        'seller_id',
        'listing_id',
        'quantity',
        'option_ids',
        'line_key',
        'note',
    ];

    protected $casts = [
        'option_ids' => 'array',
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(FreshMarketSeller::class, 'seller_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(FreshMarketListing::class, 'listing_id');
    }

    /**
     * รหัสตัวเลือกแบบเรียงแล้ว (int)
     *
     * @return array<int, int>
     */
    public function normalizedOptionIds(): array
    {
        return self::normalizeOptionIds($this->option_ids ?? []);
    }

    /**
     * แปลงรายการรหัสตัวเลือกให้เป็น int ไม่ซ้ำ เรียงจากน้อยไปมาก
     *
     * @return array<int, int>
     */
    public static function normalizeOptionIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            $ids = $ids === null || $ids === '' ? [] : [$ids];
        }

        $clean = [];

        foreach ($ids as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $clean[(int) $id] = (int) $id;
            }
        }

        ksort($clean);

        return array_values($clean);
    }

    /**
     * คีย์รวมรายการซ้ำ: สินค้าเดียวกัน + ตัวเลือกชุดเดียวกัน + โน้ตเดียวกัน = บรรทัดเดียวกัน
     */
    public static function makeLineKey(int $listingId, array $optionIds, ?string $note): string
    {
        return hash('sha256', $listingId.'|'.implode(',', self::normalizeOptionIds($optionIds)).'|'.trim((string) $note));
    }
}
