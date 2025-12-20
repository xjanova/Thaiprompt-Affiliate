<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductFavorite Model
 *
 * จัดเก็บรายการโปรด/wishlist ของผู้ใช้
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string|null $collection_name ชื่อ collection
 * @property string|null $note โน้ตส่วนตัว
 * @property bool $notify_price_drop แจ้งเตือนลดราคา
 * @property float|null $target_price ราคาเป้าหมาย
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read Product $product
 */
class ProductFavorite extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'product_favorites';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'collection_name',
        'note',
        'notify_price_drop',
        'target_price',
    ];

    /**
     * การ cast ชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'notify_price_drop' => 'boolean',
        'target_price' => 'decimal:2',
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
     * ความสัมพันธ์กับ Product
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Toggle favorite สำหรับสินค้า
     *
     * @param int $userId
     * @param int $productId
     * @param array $data ข้อมูลเพิ่มเติม (collection_name, note, etc.)
     * @return array{favorited: bool, count: int}
     */
    public static function toggle(int $userId, int $productId, array $data = []): array
    {
        $existing = static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            static::create(array_merge([
                'user_id' => $userId,
                'product_id' => $productId,
            ], $data));
            $favorited = true;
        }

        // อัพเดท favorites_count
        $count = static::where('product_id', $productId)->count();
        Product::where('id', $productId)->update(['favorites_count' => $count]);

        return ['favorited' => $favorited, 'count' => $count];
    }

    /**
     * ตรวจสอบว่าสินค้าอยู่ในรายการโปรดหรือไม่
     *
     * @param int $userId
     * @param int $productId
     * @return bool
     */
    public static function isFavorited(int $userId, int $productId): bool
    {
        return static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * ดึงรายการ collections ของผู้ใช้
     *
     * @param int $userId
     * @return \Illuminate\Support\Collection
     */
    public static function getCollections(int $userId): \Illuminate\Support\Collection
    {
        return static::where('user_id', $userId)
            ->whereNotNull('collection_name')
            ->groupBy('collection_name')
            ->selectRaw('collection_name, COUNT(*) as items_count')
            ->pluck('items_count', 'collection_name');
    }

    /**
     * Scope สำหรับสินค้าที่ต้องการแจ้งเตือนลดราคา
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWantsPriceAlert($query)
    {
        return $query->where('notify_price_drop', true)
            ->whereNotNull('target_price');
    }
}
