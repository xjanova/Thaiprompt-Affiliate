<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FreshMarketShopFollower - ผู้ซื้อที่ติดตามร้านตลาดสด
 *
 * ร้านกด "เปิดร้าน" → แจ้งผู้ติดตาม (ในแอป + Expo push) สูงสุด 1 ครั้งต่อร้านต่อคนทุก 3 ชั่วโมง
 * (last_notified_at ใช้กันแจ้งถี่ — ไม่มี LINE push)
 *
 * @property int $id
 * @property int $seller_id
 * @property int $user_id
 * @property bool $notify
 * @property \Carbon\Carbon|null $last_notified_at
 */
class FreshMarketShopFollower extends Model
{
    protected $table = 'fresh_market_shop_followers';

    protected $fillable = [
        'seller_id',
        'user_id',
        'notify',
        'last_notified_at',
    ];

    protected $casts = [
        'notify' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    /**
     * ร้านที่ติดตาม
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(FreshMarketSeller::class, 'seller_id');
    }

    /**
     * ผู้ติดตาม
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
