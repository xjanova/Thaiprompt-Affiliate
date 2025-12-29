<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductView Model
 *
 * บันทึกประวัติการดูสินค้าของผู้ใช้
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $product_id
 * @property string|null $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property int $view_duration
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User|null $user
 * @property-read Product $product
 */
class ProductView extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'product_views';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
        'view_duration',
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
     * บันทึกการดูสินค้า
     *
     * @param  array  $data  ข้อมูลเพิ่มเติม
     */
    public static function recordView(int $productId, ?int $userId = null, array $data = []): static
    {
        $view = static::create(array_merge([
            'product_id' => $productId,
            'user_id' => $userId,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
            'referrer' => substr(request()->header('referer') ?? '', 0, 500),
        ], $data));

        // อัพเดท view_count ใน products
        Product::where('id', $productId)->increment('view_count');

        return $view;
    }

    /**
     * ดึงสินค้าที่ดูล่าสุดของผู้ใช้
     */
    public static function getRecentlyViewed(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->with('product')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->unique('product_id')
            ->pluck('product')
            ->filter();
    }

    /**
     * ดึงสินค้าที่ดูล่าสุดจาก session
     */
    public static function getRecentlyViewedBySession(string $sessionId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('session_id', $sessionId)
            ->with('product')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->unique('product_id')
            ->pluck('product')
            ->filter();
    }
}
