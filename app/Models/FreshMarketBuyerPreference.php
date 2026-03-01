<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FreshMarketBuyerPreference - เงื่อนไขการหาสินค้าของผู้ซื้อ
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $line_user_id
 * @property array|null $preferred_categories
 * @property float|null $max_price
 * @property float|null $max_distance_km
 * @property bool $preferred_organic
 * @property string|null $preferred_delivery_type
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $notification_enabled
 */
class FreshMarketBuyerPreference extends Model
{
    protected $table = 'fresh_market_buyer_preferences';

    protected $fillable = [
        'user_id',
        'line_user_id',
        'preferred_categories',
        'max_price',
        'max_distance_km',
        'preferred_organic',
        'preferred_delivery_type',
        'latitude',
        'longitude',
        'notification_enabled',
    ];

    protected $casts = [
        'preferred_categories' => 'array',
        'max_price' => 'decimal:2',
        'max_distance_km' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'preferred_organic' => 'boolean',
        'notification_enabled' => 'boolean',
    ];

    /**
     * ผู้ซื้อ (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ดึง preference ของ LINE user หรือสร้างใหม่
     */
    public static function getOrCreate(string $lineUserId, ?int $userId = null): self
    {
        return self::firstOrCreate(
            ['line_user_id' => $lineUserId],
            [
                'user_id' => $userId,
                'notification_enabled' => true,
            ]
        );
    }

    /**
     * มีพิกัดบ้านหรือยัง
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
