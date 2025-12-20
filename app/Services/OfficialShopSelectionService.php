<?php

namespace App\Services;

use App\Models\BestSellerMonthly;
use App\Models\NewProductPromotion;
use App\Models\OfficialShopProduct;
use App\Models\OfficialShopSetting;
use App\Models\OfficialShopWarning;
use App\Models\Product;
use App\Models\VendorStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OfficialShopSelectionService
 *
 * จัดการระบบ AI Selection สำหรับ Official Shop
 * - คัดเลือกสินค้าจากร้านค้าต่างๆ ตามคะแนน AI
 * - ระบบเตือน 3 วันก่อนถอดสินค้า
 * - ระบบโปรโมทสินค้าใหม่ 7 วัน
 * - ระบบสินค้าขายดีรายเดือน
 */
class OfficialShopSelectionService
{
    /**
     * คำนวณคะแนน AI สำหรับสินค้า
     *
     * คะแนนคำนวณจาก:
     * - Rating: คะแนนรีวิว (25%)
     * - Reviews: จำนวนรีวิว (10%)
     * - Sales: ยอดขาย (25%)
     * - Store Followers: ผู้ติดตามร้าน (15%)
     * - Store Products: จำนวนสินค้าในร้าน (10%)
     * - Store Age: อายุร้าน (5%)
     * - Store Trophies: Trophy ของร้าน (10%)
     *
     * @param Product $product
     * @return array ['score' => float, 'breakdown' => array]
     */
    public function calculateProductScore(Product $product): array
    {
        $weights = OfficialShopSetting::getScoreWeights();
        $store = $product->store ?? $product->seller?->vendorStore;

        $breakdown = [];
        $totalScore = 0;

        // 1. Rating Score (0-100)
        $ratingScore = 0;
        if ($product->rating_average && $product->rating_average > 0) {
            $ratingScore = ($product->rating_average / 5) * 100;
        }
        $breakdown['rating'] = [
            'raw' => $product->rating_average ?? 0,
            'score' => round($ratingScore, 2),
            'weight' => $weights['rating'],
            'weighted_score' => round($ratingScore * $weights['rating'], 2),
        ];
        $totalScore += $ratingScore * $weights['rating'];

        // 2. Reviews Score (0-100)
        $reviewCount = $product->reviews()->where('is_approved', true)->count();
        $reviewScore = min(100, ($reviewCount / 50) * 100); // 50+ reviews = 100%
        $breakdown['reviews'] = [
            'raw' => $reviewCount,
            'score' => round($reviewScore, 2),
            'weight' => $weights['reviews'],
            'weighted_score' => round($reviewScore * $weights['reviews'], 2),
        ];
        $totalScore += $reviewScore * $weights['reviews'];

        // 3. Sales Score (0-100)
        $salesCount = $product->sales_count ?? 0;
        $salesScore = min(100, ($salesCount / 100) * 100); // 100+ sales = 100%
        $breakdown['sales'] = [
            'raw' => $salesCount,
            'score' => round($salesScore, 2),
            'weight' => $weights['sales'],
            'weighted_score' => round($salesScore * $weights['sales'], 2),
        ];
        $totalScore += $salesScore * $weights['sales'];

        // คะแนนจากร้านค้า (ถ้ามี)
        if ($store) {
            // 4. Followers Score (0-100)
            // ใช้ relationship count หรือ attribute (ถ้ามี)
            $followersCount = method_exists($store, 'followers')
                ? $store->followers()->count()
                : ($store->followers_count ?? 0);
            $followersScore = min(100, ($followersCount / 500) * 100); // 500+ followers = 100%
            $breakdown['followers'] = [
                'raw' => $followersCount,
                'score' => round($followersScore, 2),
                'weight' => $weights['followers'],
                'weighted_score' => round($followersScore * $weights['followers'], 2),
            ];
            $totalScore += $followersScore * $weights['followers'];

            // 5. Products Score (0-100)
            $productsCount = $store->products()->where('is_active', true)->count();
            $productsScore = min(100, ($productsCount / 50) * 100); // 50+ products = 100%
            $breakdown['products'] = [
                'raw' => $productsCount,
                'score' => round($productsScore, 2),
                'weight' => $weights['products'],
                'weighted_score' => round($productsScore * $weights['products'], 2),
            ];
            $totalScore += $productsScore * $weights['products'];

            // 6. Store Age Score (0-100)
            $storeAgeDays = $store->created_at ? now()->diffInDays($store->created_at) : 0;
            $ageScore = min(100, ($storeAgeDays / 365) * 100); // 1+ year = 100%
            $breakdown['age'] = [
                'raw' => $storeAgeDays,
                'score' => round($ageScore, 2),
                'weight' => $weights['age'],
                'weighted_score' => round($ageScore * $weights['age'], 2),
            ];
            $totalScore += $ageScore * $weights['age'];

            // 7. Trophies Score (0-100)
            // นับจำนวน trophy achievements หรือใช้ attribute (ถ้ามี)
            $trophyPoints = method_exists($store, 'trophyAchievements')
                ? $store->trophyAchievements()->count() * 10 // 10 คะแนนต่อ 1 trophy
                : ($store->trophy_points ?? 0);
            $trophyScore = min(100, ($trophyPoints / 100) * 100); // 100+ points = 100%
            $breakdown['trophies'] = [
                'raw' => $trophyPoints,
                'score' => round($trophyScore, 2),
                'weight' => $weights['trophies'],
                'weighted_score' => round($trophyScore * $weights['trophies'], 2),
            ];
            $totalScore += $trophyScore * $weights['trophies'];
        } else {
            // ถ้าไม่มีร้านค้า ให้คะแนน 0 ในส่วนนี้
            $breakdown['followers'] = ['raw' => 0, 'score' => 0, 'weight' => $weights['followers'], 'weighted_score' => 0];
            $breakdown['products'] = ['raw' => 0, 'score' => 0, 'weight' => $weights['products'], 'weighted_score' => 0];
            $breakdown['age'] = ['raw' => 0, 'score' => 0, 'weight' => $weights['age'], 'weighted_score' => 0];
            $breakdown['trophies'] = ['raw' => 0, 'score' => 0, 'weight' => $weights['trophies'], 'weighted_score' => 0];
        }

        return [
            'score' => round($totalScore, 2),
            'breakdown' => $breakdown,
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * รัน AI Selection อัตโนมัติ
     *
     * @param int $maxProducts จำนวนสินค้าสูงสุดที่จะเลือก
     * @return array ผลลัพธ์การ selection
     */
    public function runAiSelection(?int $maxProducts = null): array
    {
        if (!OfficialShopSetting::get('ai_selection_enabled', true)) {
            return [
                'success' => false,
                'message' => 'ระบบ AI Selection ถูกปิดอยู่',
            ];
        }

        $maxProducts = $maxProducts ?? (int) OfficialShopSetting::get('max_featured_products', 50);
        $minScore = OfficialShopSetting::getMinAiScore();

        Log::info('เริ่มรัน AI Selection', [
            'max_products' => $maxProducts,
            'min_score' => $minScore,
        ]);

        return DB::transaction(function () use ($maxProducts, $minScore) {
            $selected = [];
            $warnings = [];
            $removed = [];

            // 1. ตรวจสอบสินค้าที่อยู่ใน Official Shop อยู่แล้ว
            $existingProducts = OfficialShopProduct::aiFeatured()
                ->active()
                ->with('product')
                ->get();

            foreach ($existingProducts as $entry) {
                if (!$entry->product) {
                    // สินค้าถูกลบแล้ว - ถอดออก
                    $entry->removeFromOfficialShop('สินค้าถูกลบแล้ว');
                    $removed[] = [
                        'product_id' => $entry->product_id,
                        'reason' => 'product_deleted',
                    ];
                    continue;
                }

                // คำนวณคะแนนใหม่
                $scoreData = $this->calculateProductScore($entry->product);
                $entry->update([
                    'ai_score' => $scoreData['score'],
                    'score_breakdown' => $scoreData['breakdown'],
                ]);

                // ตรวจสอบว่าคะแนนต่ำกว่าเกณฑ์หรือไม่
                if ($scoreData['score'] < $minScore) {
                    // สร้าง warning (ถ้ายังไม่มี)
                    $warning = OfficialShopWarning::createWarning(
                        $entry,
                        $scoreData['score'],
                        $minScore,
                        OfficialShopSetting::getWarningDays()
                    );

                    $warnings[] = [
                        'product_id' => $entry->product_id,
                        'current_score' => $scoreData['score'],
                        'required_score' => $minScore,
                        'warning_id' => $warning->id,
                    ];
                }
            }

            // 2. หาสินค้าใหม่ที่มีคะแนนสูง
            $currentCount = OfficialShopProduct::aiFeatured()->active()->count();
            $slotsAvailable = max(0, $maxProducts - $currentCount);

            if ($slotsAvailable > 0) {
                // ดึงสินค้าที่มีคุณสมบัติ
                $candidates = Product::where('is_active', true)
                    ->whereNotNull('store_id')
                    ->where('stock_status', '!=', 'out_of_stock')
                    ->whereDoesntHave('officialShopProducts', function ($q) {
                        $q->where('selection_type', OfficialShopProduct::TYPE_AI_FEATURED)
                            ->where('is_active', true);
                    })
                    ->with(['store', 'reviews'])
                    ->limit($slotsAvailable * 3) // ดึงมา 3 เท่าเพื่อกรอง
                    ->get();

                // คำนวณและเรียงตามคะแนน
                $scoredCandidates = collect();
                foreach ($candidates as $product) {
                    $scoreData = $this->calculateProductScore($product);
                    if ($scoreData['score'] >= $minScore) {
                        $scoredCandidates->push([
                            'product' => $product,
                            'score' => $scoreData['score'],
                            'breakdown' => $scoreData['breakdown'],
                        ]);
                    }
                }

                // เรียงตามคะแนนและเลือก
                $topCandidates = $scoredCandidates
                    ->sortByDesc('score')
                    ->take($slotsAvailable);

                foreach ($topCandidates as $candidate) {
                    $entry = OfficialShopProduct::addProduct(
                        $candidate['product'],
                        OfficialShopProduct::TYPE_AI_FEATURED,
                        $candidate['score'],
                        $candidate['breakdown']
                    );

                    $selected[] = [
                        'product_id' => $candidate['product']->id,
                        'product_name' => $candidate['product']->name,
                        'score' => $candidate['score'],
                        'entry_id' => $entry->id,
                    ];
                }
            }

            // 3. ประมวลผล Warnings ที่หมดเวลา
            $expiredWarnings = OfficialShopWarning::expired()->get();
            foreach ($expiredWarnings as $warning) {
                $warning->markAsExpired();

                // ถอดสินค้าออก
                $ospProduct = $warning->officialShopProduct;
                if ($ospProduct && $ospProduct->is_active) {
                    $ospProduct->removeFromOfficialShop('คะแนนต่ำกว่าเกณฑ์และหมดเวลาปรับปรุง');
                    $removed[] = [
                        'product_id' => $ospProduct->product_id,
                        'reason' => 'warning_expired',
                    ];
                }
            }

            $result = [
                'success' => true,
                'selected' => $selected,
                'warnings' => $warnings,
                'removed' => $removed,
                'stats' => [
                    'total_featured' => OfficialShopProduct::aiFeatured()->active()->count(),
                    'new_selections' => count($selected),
                    'new_warnings' => count($warnings),
                    'removed_count' => count($removed),
                ],
                'run_at' => now()->toDateTimeString(),
            ];

            Log::info('AI Selection เสร็จสิ้น', $result);

            return $result;
        });
    }

    /**
     * ตรวจสอบและปรับปรุง Warnings
     *
     * @return array
     */
    public function processWarnings(): array
    {
        $minScore = OfficialShopSetting::getMinAiScore();

        return DB::transaction(function () use ($minScore) {
            $improved = [];
            $expired = [];
            $removed = [];

            // ตรวจสอบ warnings ที่รอดำเนินการ
            $pendingWarnings = OfficialShopWarning::pending()
                ->with(['product', 'officialShopProduct'])
                ->get();

            foreach ($pendingWarnings as $warning) {
                if (!$warning->product) {
                    $warning->markAsRemoved(null, 'สินค้าถูกลบ');
                    $removed[] = $warning->id;
                    continue;
                }

                // คำนวณคะแนนใหม่
                $scoreData = $this->calculateProductScore($warning->product);

                if ($scoreData['score'] >= $minScore) {
                    // ปรับปรุงสำเร็จ
                    $warning->markAsImproved($scoreData['score']);
                    $improved[] = [
                        'warning_id' => $warning->id,
                        'product_id' => $warning->product_id,
                        'new_score' => $scoreData['score'],
                    ];

                    // อัพเดทคะแนนใน Official Shop Product
                    if ($warning->officialShopProduct) {
                        $warning->officialShopProduct->update([
                            'ai_score' => $scoreData['score'],
                            'score_breakdown' => $scoreData['breakdown'],
                        ]);
                    }
                } elseif ($warning->deadline_at->isPast()) {
                    // หมดเวลา
                    $warning->markAsExpired();
                    $expired[] = [
                        'warning_id' => $warning->id,
                        'product_id' => $warning->product_id,
                    ];

                    // ถอดสินค้าออก
                    if ($warning->officialShopProduct && $warning->officialShopProduct->is_active) {
                        $warning->officialShopProduct->removeFromOfficialShop('หมดเวลาปรับปรุงคะแนน');
                        $removed[] = $warning->product_id;
                    }
                }
            }

            return [
                'improved' => $improved,
                'expired' => $expired,
                'removed' => $removed,
                'processed_at' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * ตรวจสอบและปิดโปรโมทสินค้าใหม่ที่หมดอายุ
     *
     * @return array
     */
    public function processExpiredNewProductPromotions(): array
    {
        return DB::transaction(function () {
            $expired = [];

            $expiredPromotions = NewProductPromotion::where('status', NewProductPromotion::STATUS_ACTIVE)
                ->where('ends_at', '<=', now())
                ->with('product')
                ->get();

            foreach ($expiredPromotions as $promo) {
                $promo->endPromotion();
                $expired[] = [
                    'promotion_id' => $promo->id,
                    'product_id' => $promo->product_id,
                    'views' => $promo->views_during_promo,
                    'sales' => $promo->sales_during_promo,
                ];
            }

            Log::info('ประมวลผลโปรโมทสินค้าใหม่ที่หมดอายุ', [
                'expired_count' => count($expired),
            ]);

            return [
                'expired' => $expired,
                'processed_at' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * คำนวณสินค้าขายดีรายเดือน
     *
     * @param int|null $month
     * @param int|null $year
     * @return array
     */
    public function calculateMonthlyBestSellers(?int $month = null, ?int $year = null): array
    {
        if (!OfficialShopSetting::get('best_seller_enabled', true)) {
            return [
                'success' => false,
                'message' => 'ระบบสินค้าขายดีถูกปิดอยู่',
            ];
        }

        $limit = OfficialShopSetting::getBestSellerCount();

        return BestSellerMonthly::calculateBestSellers($month, $year, $limit);
    }

    /**
     * ขอโปรโมทสินค้าใหม่
     *
     * @param Product $product
     * @param VendorStore $store
     * @param int|null $userId
     * @return array
     */
    public function requestNewProductPromotion(Product $product, VendorStore $store, ?int $userId = null): array
    {
        if (!OfficialShopSetting::get('new_product_promo_enabled', true)) {
            return [
                'success' => false,
                'message' => 'ระบบโปรโมทสินค้าใหม่ถูกปิดอยู่',
            ];
        }

        // ตรวจสอบสิทธิ์
        $check = NewProductPromotion::canStorePromote($store->id);
        if (!$check['can_promote']) {
            return [
                'success' => false,
                'message' => $check['reason'],
                'available_at' => $check['available_at']?->toDateTimeString(),
            ];
        }

        // ตรวจสอบว่าสินค้าเคยโปรโมทแล้วหรือยัง
        if (NewProductPromotion::where('product_id', $product->id)->exists()) {
            return [
                'success' => false,
                'message' => 'สินค้านี้เคยใช้สิทธิ์โปรโมทสินค้าใหม่แล้ว',
            ];
        }

        // ตรวจสอบว่าเป็นสินค้าของร้านนี้จริง
        if ($product->store_id !== $store->id) {
            return [
                'success' => false,
                'message' => 'สินค้านี้ไม่ใช่ของร้านค้าคุณ',
            ];
        }

        // สร้างโปรโมท
        $promo = NewProductPromotion::requestPromotion($product, $store->id, $userId);

        if (!$promo) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถสร้างโปรโมทได้',
            ];
        }

        Log::info('สร้างโปรโมทสินค้าใหม่สำเร็จ', [
            'promotion_id' => $promo->id,
            'product_id' => $product->id,
            'store_id' => $store->id,
        ]);

        return [
            'success' => true,
            'message' => 'สินค้าของคุณจะปรากฏใน Official Shop เป็นเวลา 7 วัน',
            'promotion' => [
                'id' => $promo->id,
                'starts_at' => $promo->starts_at->toDateTimeString(),
                'ends_at' => $promo->ends_at->toDateTimeString(),
            ],
        ];
    }

    /**
     * จัดการเมื่อสินค้าถูกแก้ไข/ลบ
     *
     * @param Product $product
     * @param string $action 'edit' | 'delete'
     * @return array
     */
    public function handleProductChange(Product $product, string $action): array
    {
        // ตรวจสอบว่าสินค้าอยู่ใน Official Shop หรือไม่
        $ospProducts = OfficialShopProduct::where('product_id', $product->id)
            ->active()
            ->get();

        if ($ospProducts->isEmpty()) {
            return [
                'in_official_shop' => false,
                'action_allowed' => true,
            ];
        }

        // ตรวจสอบการล็อค
        $lockedEntry = $ospProducts->first(fn($entry) => $entry->is_locked);

        if ($lockedEntry) {
            return [
                'in_official_shop' => true,
                'action_allowed' => false,
                'is_locked' => true,
                'lock_type' => $lockedEntry->selection_type,
                'lock_reason' => 'สินค้านี้กำลังอยู่ในช่วงโปรโมท ไม่สามารถแก้ไข/ลบได้ หากต้องการแก้ไขกรุณาติดต่อแอดมินผ่าน Ticket',
                'locked_until' => $lockedEntry->expires_at?->toDateTimeString(),
            ];
        }

        // สินค้าขายดี - แสดง warning และถอดออกทันที
        $bestSellerEntry = $ospProducts->first(fn($entry) =>
            $entry->selection_type === OfficialShopProduct::TYPE_BEST_SELLER
        );

        if ($bestSellerEntry && $action === 'delete') {
            $bestSellerEntry->removeFromOfficialShop('สินค้าถูกลบโดยเจ้าของร้าน');
        }

        return [
            'in_official_shop' => true,
            'action_allowed' => true,
            'warning' => 'สินค้านี้อยู่ใน Official Shop การแก้ไข/ลบจะทำให้ถูกถอดออกจาก Official Shop',
            'entries' => $ospProducts->map(fn($entry) => [
                'id' => $entry->id,
                'type' => $entry->selection_type,
                'badge' => $entry->badge_label,
            ])->toArray(),
        ];
    }

    /**
     * ดึงสถิติ Official Shop
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'ai_featured' => [
                'total' => OfficialShopProduct::aiFeatured()->active()->count(),
                'avg_score' => OfficialShopProduct::aiFeatured()->active()->avg('ai_score') ?? 0,
            ],
            'new_products' => [
                'total' => OfficialShopProduct::newProducts()->active()->notExpired()->count(),
            ],
            'best_sellers' => [
                'total' => OfficialShopProduct::bestSellers()->active()->count(),
            ],
            'warnings' => [
                'pending' => OfficialShopWarning::pending()->count(),
                'expiring_soon' => OfficialShopWarning::pending()
                    ->where('deadline_at', '<=', now()->addDay())
                    ->count(),
            ],
            'settings' => [
                'min_score' => OfficialShopSetting::getMinAiScore(),
                'best_seller_count' => OfficialShopSetting::getBestSellerCount(),
                'warning_days' => OfficialShopSetting::getWarningDays(),
            ],
        ];
    }
}
