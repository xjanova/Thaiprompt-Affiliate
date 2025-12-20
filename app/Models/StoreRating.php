<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * StoreRating Model
 *
 * ระบบดาวร้าน - ให้คะแนนร้านค้าโดยตรง (แยกจาก ProductReview และ Trophy)
 *
 * คะแนน 4 ด้าน:
 * - rating: คะแนนรวม 1-5 ดาว
 * - service_rating: คะแนนบริการ
 * - shipping_rating: คะแนนจัดส่ง
 * - communication_rating: คะแนนการสื่อสาร
 *
 * @property int $id
 * @property int $store_id
 * @property int $user_id
 * @property int|null $order_id
 * @property int $rating
 * @property int|null $service_rating
 * @property int|null $shipping_rating
 * @property int|null $communication_rating
 * @property string|null $comment
 * @property array|null $images
 * @property bool $is_anonymous
 * @property bool $is_verified_purchase
 * @property bool $is_approved
 * @property int $helpful_count
 * @property string|null $seller_response
 * @property \Carbon\Carbon|null $seller_responded_at
 */
class StoreRating extends Model
{
    use SoftDeletes;

    /**
     * ตารางที่ใช้
     *
     * @var string
     */
    protected $table = 'store_ratings';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'order_id',
        'rating',
        'service_rating',
        'shipping_rating',
        'communication_rating',
        'comment',
        'images',
        'is_anonymous',
        'is_verified_purchase',
        'is_approved',
        'helpful_count',
        'seller_response',
        'seller_responded_at',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'service_rating' => 'integer',
        'shipping_rating' => 'integer',
        'communication_rating' => 'integer',
        'images' => 'array',
        'is_anonymous' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'helpful_count' => 'integer',
        'seller_responded_at' => 'datetime',
    ];

    // ===== Relationships =====

    /**
     * ร้านค้า
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ผู้ให้คะแนน
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * คำสั่งซื้อ
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ===== Scopes =====

    /**
     * เฉพาะที่อนุมัติแล้ว
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * เฉพาะซื้อจริง
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * เฉพาะที่มี comment
     */
    public function scopeWithComment($query)
    {
        return $query->whereNotNull('comment')->where('comment', '!=', '');
    }

    /**
     * เรียงตาม helpful
     */
    public function scopeOrderByHelpful($query)
    {
        return $query->orderByDesc('helpful_count');
    }

    // ===== Static Methods =====

    /**
     * ให้คะแนนร้านค้า
     *
     * @param VendorStore $store
     * @param User $user
     * @param array $data
     * @return static
     */
    public static function rateStore(VendorStore $store, User $user, array $data): static
    {
        return DB::transaction(function () use ($store, $user, $data) {
            // ตรวจสอบว่าเคยให้คะแนนแล้วหรือยัง (ถ้าไม่มี order_id)
            $orderId = $data['order_id'] ?? null;

            $existing = static::where('user_id', $user->id)
                ->where('store_id', $store->id)
                ->where('order_id', $orderId)
                ->first();

            if ($existing) {
                // อัพเดทคะแนนเดิม
                $existing->update([
                    'rating' => $data['rating'],
                    'service_rating' => $data['service_rating'] ?? null,
                    'shipping_rating' => $data['shipping_rating'] ?? null,
                    'communication_rating' => $data['communication_rating'] ?? null,
                    'comment' => $data['comment'] ?? null,
                    'images' => $data['images'] ?? null,
                ]);
                $rating = $existing;
            } else {
                // สร้างคะแนนใหม่
                $rating = static::create([
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                    'order_id' => $orderId,
                    'rating' => $data['rating'],
                    'service_rating' => $data['service_rating'] ?? null,
                    'shipping_rating' => $data['shipping_rating'] ?? null,
                    'communication_rating' => $data['communication_rating'] ?? null,
                    'comment' => $data['comment'] ?? null,
                    'images' => $data['images'] ?? null,
                    'is_anonymous' => $data['is_anonymous'] ?? false,
                    'is_verified_purchase' => $orderId !== null,
                    'is_approved' => true,
                ]);
            }

            // อัพเดทคะแนนเฉลี่ยของร้าน
            static::updateStoreAverages($store);

            return $rating;
        });
    }

    /**
     * อัพเดทคะแนนเฉลี่ยของร้าน
     *
     * @param VendorStore $store
     * @return void
     */
    public static function updateStoreAverages(VendorStore $store): void
    {
        $stats = static::where('store_id', $store->id)
            ->approved()
            ->selectRaw('
                AVG(rating) as avg_rating,
                AVG(service_rating) as avg_service,
                AVG(shipping_rating) as avg_shipping,
                AVG(communication_rating) as avg_communication,
                COUNT(*) as total_count
            ')
            ->first();

        $store->update([
            'rating_average' => round($stats->avg_rating ?? 0, 2),
            'service_rating_average' => round($stats->avg_service ?? 0, 2),
            'shipping_rating_average' => round($stats->avg_shipping ?? 0, 2),
            'communication_rating_average' => round($stats->avg_communication ?? 0, 2),
            'store_rating_count' => $stats->total_count ?? 0,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้ให้คะแนนแล้วหรือยัง
     *
     * @param int $userId
     * @param int $storeId
     * @param int|null $orderId
     * @return bool
     */
    public static function hasRated(int $userId, int $storeId, ?int $orderId = null): bool
    {
        $query = static::where('user_id', $userId)
            ->where('store_id', $storeId);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->exists();
    }

    /**
     * ดึงสถิติคะแนนของร้าน
     *
     * @param int $storeId
     * @return array
     */
    public static function getStoreStats(int $storeId): array
    {
        $ratings = static::where('store_id', $storeId)
            ->approved()
            ->get();

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($ratings as $rating) {
            $distribution[$rating->rating]++;
        }

        $total = $ratings->count();

        return [
            'average' => $total > 0 ? round($ratings->avg('rating'), 1) : 0,
            'total' => $total,
            'distribution' => $distribution,
            'distribution_percent' => array_map(
                fn($count) => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                $distribution
            ),
            'service_average' => round($ratings->avg('service_rating') ?? 0, 1),
            'shipping_average' => round($ratings->avg('shipping_rating') ?? 0, 1),
            'communication_average' => round($ratings->avg('communication_rating') ?? 0, 1),
        ];
    }

    // ===== Instance Methods =====

    /**
     * ตอบกลับรีวิว
     *
     * @param string $response
     * @return void
     */
    public function addSellerResponse(string $response): void
    {
        $this->seller_response = $response;
        $this->seller_responded_at = now();
        $this->save();
    }

    /**
     * เพิ่ม helpful count
     *
     * @return void
     */
    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }

    // ===== Accessors =====

    /**
     * ดาวแสดงผล
     *
     * @return string
     */
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating);
    }

    /**
     * คะแนนเฉลี่ยทุกด้าน
     *
     * @return float
     */
    public function getOverallRatingAttribute(): float
    {
        $ratings = array_filter([
            $this->rating,
            $this->service_rating,
            $this->shipping_rating,
            $this->communication_rating,
        ]);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
    }

    /**
     * ชื่อผู้รีวิว (ซ่อนถ้า anonymous)
     *
     * @return string
     */
    public function getReviewerNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'ผู้ใช้ไม่ระบุชื่อ';
        }

        return $this->user->name ?? 'ผู้ใช้งาน';
    }

    /**
     * ตรวจสอบว่าตอบกลับแล้วหรือยัง
     *
     * @return bool
     */
    public function hasSellerResponse(): bool
    {
        return !empty($this->seller_response);
    }
}
