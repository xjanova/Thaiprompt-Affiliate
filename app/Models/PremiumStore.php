<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PremiumStore Model
 *
 * ร้านค้า Premium ที่ได้รับการคัดเลือกจาก AI หรือ Admin
 *
 * @property int $id
 * @property int $store_id
 * @property string $selection_type ai_selected, manual, promoted
 * @property float|null $ai_score
 * @property array|null $ai_criteria
 * @property string $status active, pending, expired, rejected
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $expires_at
 * @property int|null $approved_by
 * @property float|null $rating_snapshot
 * @property int $sales_count_snapshot
 * @property int $products_count_snapshot
 * @property int $followers_count_snapshot
 * @property int $display_order
 * @property bool $is_featured
 * @property string|null $featured_badge
 * @property string|null $featured_label
 * @property string|null $rejection_reason
 *
 * @property-read VendorStore $store
 * @property-read User|null $approver
 */
class PremiumStore extends Model
{
    use HasFactory;

    protected $table = 'premium_stores';

    protected $fillable = [
        'store_id',
        'selection_type',
        'ai_score',
        'ai_criteria',
        'status',
        'approved_at',
        'expires_at',
        'approved_by',
        'rating_snapshot',
        'sales_count_snapshot',
        'products_count_snapshot',
        'followers_count_snapshot',
        'display_order',
        'is_featured',
        'featured_badge',
        'featured_label',
        'rejection_reason',
    ];

    protected $casts = [
        'ai_score' => 'decimal:4',
        'ai_criteria' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'rating_snapshot' => 'decimal:2',
        'sales_count_snapshot' => 'integer',
        'products_count_snapshot' => 'integer',
        'followers_count_snapshot' => 'integer',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
    ];

    /**
     * สถานะที่รองรับ
     */
    public const STATUSES = [
        'active' => 'ใช้งาน',
        'pending' => 'รอพิจารณา',
        'expired' => 'หมดอายุ',
        'rejected' => 'ถูกปฏิเสธ',
    ];

    /**
     * ประเภทการคัดเลือก
     */
    public const SELECTION_TYPES = [
        'ai_selected' => 'AI คัดเลือก',
        'manual' => 'Admin คัดเลือก',
        'promoted' => 'ซื้อโฆษณา',
    ];

    /**
     * ความสัมพันธ์กับ VendorStore
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ความสัมพันธ์กับ User (ผู้อนุมัติ)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope สำหรับร้าน Active
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope สำหรับร้าน Featured
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope สำหรับร้านที่ AI คัดเลือก
     */
    public function scopeAiSelected($query)
    {
        return $query->where('selection_type', 'ai_selected');
    }

    /**
     * Scope เรียงตามคะแนน AI
     */
    public function scopeByScore($query)
    {
        return $query->orderByDesc('ai_score');
    }

    /**
     * ตรวจสอบว่าหมดอายุหรือยัง
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * อนุมัติร้าน Premium
     */
    public function approve(int $approverId, ?string $expiresAt = null): bool
    {
        $this->status = 'active';
        $this->approved_at = now();
        $this->approved_by = $approverId;

        if ($expiresAt) {
            $this->expires_at = $expiresAt;
        }

        $saved = $this->save();

        if ($saved) {
            // อัพเดทสถานะ is_premium ใน VendorStore
            $this->store->update([
                'is_premium' => true,
                'premium_since' => now(),
                'ai_score' => $this->ai_score,
            ]);
        }

        return $saved;
    }

    /**
     * ปฏิเสธร้าน Premium
     */
    public function reject(string $reason): bool
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * คำนวณคะแนน AI สำหรับร้านค้า
     *
     * @param VendorStore $store
     * @return array{score: float, criteria: array}
     */
    public static function calculateAiScore(VendorStore $store): array
    {
        $criteria = [];

        // คะแนนจาก Rating (น้ำหนัก 25%)
        $ratingScore = min(($store->rating_average ?? 0) / 5 * 100, 100);
        $criteria['rating'] = [
            'value' => $store->rating_average ?? 0,
            'score' => $ratingScore,
            'weight' => 0.25,
        ];

        // คะแนนจากจำนวนรีวิว (น้ำหนัก 10%)
        $reviewScore = min(($store->rating_count ?? 0) / 100 * 100, 100);
        $criteria['reviews'] = [
            'value' => $store->rating_count ?? 0,
            'score' => $reviewScore,
            'weight' => 0.10,
        ];

        // คะแนนจากยอดขาย (น้ำหนัก 25%)
        $salesScore = min(($store->total_orders ?? 0) / 500 * 100, 100);
        $criteria['sales'] = [
            'value' => $store->total_orders ?? 0,
            'score' => $salesScore,
            'weight' => 0.25,
        ];

        // คะแนนจากผู้ติดตาม (น้ำหนัก 15%)
        $followersScore = min(($store->followers_count ?? 0) / 1000 * 100, 100);
        $criteria['followers'] = [
            'value' => $store->followers_count ?? 0,
            'score' => $followersScore,
            'weight' => 0.15,
        ];

        // คะแนนจากจำนวนสินค้า (น้ำหนัก 10%)
        $productsScore = min(($store->total_products ?? 0) / 50 * 100, 100);
        $criteria['products'] = [
            'value' => $store->total_products ?? 0,
            'score' => $productsScore,
            'weight' => 0.10,
        ];

        // คะแนนจากอายุร้าน (น้ำหนัก 5%)
        $monthsActive = $store->created_at ? now()->diffInMonths($store->created_at) : 0;
        $ageScore = min($monthsActive / 12 * 100, 100);
        $criteria['age'] = [
            'value' => $monthsActive,
            'score' => $ageScore,
            'weight' => 0.05,
        ];

        // คะแนนจาก Trophy Points (น้ำหนัก 10%)
        $trophyScore = min(($store->trophy_points ?? 0) / 500 * 100, 100);
        $criteria['trophies'] = [
            'value' => $store->trophy_points ?? 0,
            'score' => $trophyScore,
            'weight' => 0.10,
        ];

        // คำนวณคะแนนรวม
        $totalScore = 0;
        foreach ($criteria as $c) {
            $totalScore += $c['score'] * $c['weight'];
        }

        return [
            'score' => round($totalScore, 4),
            'criteria' => $criteria,
        ];
    }

    /**
     * เสนอร้านค้าเป็น Premium โดย AI
     *
     * @param VendorStore $store
     * @return static|null
     */
    public static function nominateByAi(VendorStore $store): ?static
    {
        // ตรวจสอบว่าเป็น Premium อยู่แล้วหรือไม่
        $existing = static::where('store_id', $store->id)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if ($existing) {
            return null;
        }

        // คำนวณคะแนน
        $result = static::calculateAiScore($store);

        // ต้องมีคะแนนอย่างน้อย 60 จึงจะได้รับการเสนอ
        if ($result['score'] < 60) {
            return null;
        }

        return static::create([
            'store_id' => $store->id,
            'selection_type' => 'ai_selected',
            'ai_score' => $result['score'],
            'ai_criteria' => $result['criteria'],
            'status' => 'pending',
            'rating_snapshot' => $store->rating_average,
            'sales_count_snapshot' => $store->total_orders ?? 0,
            'products_count_snapshot' => $store->total_products ?? 0,
            'followers_count_snapshot' => $store->followers_count ?? 0,
        ]);
    }
}
