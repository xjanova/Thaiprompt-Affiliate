<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductLike Model
 *
 * จัดเก็บข้อมูลการกดถูกใจสินค้าของผู้ใช้
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read Product $product
 */
class ProductLike extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'product_likes';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Toggle like สำหรับสินค้า
     *
     * @return array{liked: bool, count: int}
     */
    public static function toggle(int $userId, int $productId): array
    {
        $existing = static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            static::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
            $liked = true;
        }

        // อัพเดท likes_count
        $count = static::where('product_id', $productId)->count();
        Product::where('id', $productId)->update(['likes_count' => $count]);

        return ['liked' => $liked, 'count' => $count];
    }

    /**
     * ตรวจสอบว่าผู้ใช้กดถูกใจสินค้านี้หรือไม่
     */
    public static function isLiked(int $userId, int $productId): bool
    {
        return static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }
}
