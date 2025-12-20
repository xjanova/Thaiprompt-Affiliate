<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StoreFollower Model
 *
 * จัดเก็บข้อมูลการติดตามร้านค้าของผู้ใช้
 *
 * @property int $id
 * @property int $user_id
 * @property int $store_id
 * @property bool $notify_new_products แจ้งเตือนสินค้าใหม่
 * @property bool $notify_promotions แจ้งเตือนโปรโมชั่น
 * @property bool $notify_live แจ้งเตือน Live
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read VendorStore $store
 */
class StoreFollower extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'store_followers';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'store_id',
        'notify_new_products',
        'notify_promotions',
        'notify_live',
    ];

    /**
     * การ cast ชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'notify_new_products' => 'boolean',
        'notify_promotions' => 'boolean',
        'notify_live' => 'boolean',
    ];

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ VendorStore
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Toggle follow สำหรับร้านค้า
     *
     * @param int $userId
     * @param int $storeId
     * @param array $settings ตั้งค่าการแจ้งเตือน
     * @return array{following: bool, count: int}
     */
    public static function toggle(int $userId, int $storeId, array $settings = []): array
    {
        $existing = static::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->first();

        if ($existing) {
            $existing->delete();
            $following = false;
        } else {
            static::create(array_merge([
                'user_id' => $userId,
                'store_id' => $storeId,
                'notify_new_products' => true,
                'notify_promotions' => true,
                'notify_live' => false,
            ], $settings));
            $following = true;
        }

        // อัพเดท followers_count
        $count = static::where('store_id', $storeId)->count();
        VendorStore::where('id', $storeId)->update(['followers_count' => $count]);

        return ['following' => $following, 'count' => $count];
    }

    /**
     * ตรวจสอบว่าผู้ใช้ติดตามร้านนี้หรือไม่
     *
     * @param int $userId
     * @param int $storeId
     * @return bool
     */
    public static function isFollowing(int $userId, int $storeId): bool
    {
        return static::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->exists();
    }

    /**
     * อัพเดทการตั้งค่าการแจ้งเตือน
     *
     * @param int $userId
     * @param int $storeId
     * @param array $settings
     * @return bool
     */
    public static function updateSettings(int $userId, int $storeId, array $settings): bool
    {
        return static::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->update($settings) > 0;
    }

    /**
     * ดึง followers ที่ต้องการรับการแจ้งเตือนสินค้าใหม่
     *
     * @param int $storeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getNewProductNotifyList(int $storeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('store_id', $storeId)
            ->where('notify_new_products', true)
            ->with('user')
            ->get();
    }

    /**
     * ดึง followers ที่ต้องการรับการแจ้งเตือนโปรโมชั่น
     *
     * @param int $storeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPromotionNotifyList(int $storeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('store_id', $storeId)
            ->where('notify_promotions', true)
            ->with('user')
            ->get();
    }
}
