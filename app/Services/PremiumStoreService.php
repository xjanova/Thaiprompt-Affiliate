<?php

namespace App\Services;

use App\Models\PremiumStore;
use App\Models\StoreTrophy;
use App\Models\StoreTrophyAchievement;
use App\Models\VendorStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PremiumStoreService
 *
 * บริการจัดการร้าน Premium และ AI Selection
 */
class PremiumStoreService
{
    /**
     * ค่าคะแนนขั้นต่ำสำหรับการเป็น Premium
     */
    protected const MIN_PREMIUM_SCORE = 60;

    /**
     * จำนวนร้าน Premium สูงสุดที่แนะนำต่อรอบ
     */
    protected const MAX_NOMINATIONS_PER_RUN = 50;

    /**
     * รันการคัดเลือกร้าน Premium โดย AI
     *
     * @param bool $autoApprove อนุมัติอัตโนมัติ
     * @return array{nominated: int, approved: int, skipped: int}
     */
    public function runAiSelection(bool $autoApprove = false): array
    {
        $result = [
            'nominated' => 0,
            'approved' => 0,
            'skipped' => 0,
        ];

        // ดึงร้านค้าที่ active และยังไม่เป็น Premium
        $stores = VendorStore::where('is_active', true)
            ->where('is_premium', false)
            ->where('status', 'active')
            ->whereDoesntHave('premiumStore', function ($q) {
                $q->whereIn('status', ['active', 'pending']);
            })
            ->with(['products', 'orders'])
            ->get();

        Log::info("🏪 AI Premium Selection: พบร้านค้า {$stores->count()} ร้านที่จะตรวจสอบ");

        $nominations = [];

        foreach ($stores as $store) {
            // คำนวณคะแนน AI
            $scoreResult = PremiumStore::calculateAiScore($store);

            // ข้ามร้านที่คะแนนไม่ถึง
            if ($scoreResult['score'] < self::MIN_PREMIUM_SCORE) {
                $result['skipped']++;
                continue;
            }

            $nominations[] = [
                'store' => $store,
                'score' => $scoreResult['score'],
                'criteria' => $scoreResult['criteria'],
            ];
        }

        // เรียงตามคะแนนและเลือกเฉพาะ top
        usort($nominations, fn($a, $b) => $b['score'] <=> $a['score']);
        $nominations = array_slice($nominations, 0, self::MAX_NOMINATIONS_PER_RUN);

        // สร้าง nomination records
        foreach ($nominations as $nomination) {
            try {
                DB::beginTransaction();

                $premium = PremiumStore::create([
                    'store_id' => $nomination['store']->id,
                    'selection_type' => 'ai_selected',
                    'ai_score' => $nomination['score'],
                    'ai_criteria' => $nomination['criteria'],
                    'status' => $autoApprove ? 'active' : 'pending',
                    'approved_at' => $autoApprove ? now() : null,
                    'rating_snapshot' => $nomination['store']->rating_average,
                    'sales_count_snapshot' => $nomination['store']->total_orders ?? 0,
                    'products_count_snapshot' => $nomination['store']->total_products ?? 0,
                    'followers_count_snapshot' => $nomination['store']->followers_count ?? 0,
                ]);

                if ($autoApprove) {
                    $nomination['store']->update([
                        'is_premium' => true,
                        'premium_since' => now(),
                        'ai_score' => $nomination['score'],
                    ]);
                    $result['approved']++;
                }

                $result['nominated']++;

                // ให้ Premium Trophy ถ้ามี
                $this->awardPremiumTrophy($nomination['store']);

                DB::commit();

                Log::info("✅ ร้าน {$nomination['store']->store_name} ได้รับการเสนอเป็น Premium (คะแนน: {$nomination['score']})");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("❌ Error nominating store {$nomination['store']->id}: " . $e->getMessage());
            }
        }

        Log::info("🎯 AI Premium Selection สรุป: เสนอ {$result['nominated']}, อนุมัติ {$result['approved']}, ข้าม {$result['skipped']}");

        return $result;
    }

    /**
     * ให้ Premium Trophy กับร้านค้า
     */
    protected function awardPremiumTrophy(VendorStore $store): void
    {
        $premiumTrophies = StoreTrophy::where('is_premium', true)
            ->where('is_active', true)
            ->get();

        foreach ($premiumTrophies as $trophy) {
            $trophy->awardTo($store);
        }
    }

    /**
     * ตรวจสอบและให้ Trophy ทั้งหมดที่ร้านค้าผ่านเกณฑ์
     *
     * @param VendorStore $store
     * @return array รายการ Trophy ที่ได้รับใหม่
     */
    public function checkAndAwardTrophies(VendorStore $store): array
    {
        $awarded = [];

        $trophies = StoreTrophy::where('is_active', true)
            ->where('is_premium', false) // Trophy ปกติ (ไม่ใช่ Premium only)
            ->orderBy('tier')
            ->orderBy('requirement_value')
            ->get();

        foreach ($trophies as $trophy) {
            $achievement = $trophy->awardTo($store);
            if ($achievement) {
                $awarded[] = $trophy;
            }
        }

        return $awarded;
    }

    /**
     * ดึงร้าน Premium ที่แนะนำ
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecommendedPremiumStores(int $limit = 10): Collection
    {
        return PremiumStore::active()
            ->with(['store' => function ($q) {
                $q->with(['products' => function ($pq) {
                    $pq->where('is_active', true)->limit(4);
                }]);
            }])
            ->orderByDesc('ai_score')
            ->orderByDesc('is_featured')
            ->limit($limit)
            ->get()
            ->pluck('store')
            ->filter();
    }

    /**
     * ดึงร้านที่รอพิจารณาเป็น Premium
     *
     * @return Collection
     */
    public function getPendingNominations(): Collection
    {
        return PremiumStore::where('status', 'pending')
            ->with('store')
            ->orderByDesc('ai_score')
            ->get();
    }

    /**
     * สร้างข้อความแนะนำสำหรับผู้ขาย
     *
     * @param VendorStore $store
     * @return array{eligible: bool, score: float, message: string, tips: array}
     */
    public function getPremiumEligibilityReport(VendorStore $store): array
    {
        $scoreResult = PremiumStore::calculateAiScore($store);

        $tips = [];
        foreach ($scoreResult['criteria'] as $key => $criterion) {
            if ($criterion['score'] < 70) {
                $tips[] = match ($key) {
                    'rating' => 'เพิ่มคะแนนรีวิว: ตอบกลับลูกค้าอย่างรวดเร็วและจัดส่งสินค้าคุณภาพดี',
                    'reviews' => 'เพิ่มจำนวนรีวิว: ขอให้ลูกค้าเขียนรีวิวหลังซื้อสินค้า',
                    'sales' => 'เพิ่มยอดขาย: จัดโปรโมชั่นและลงโฆษณาสินค้า',
                    'followers' => 'เพิ่มผู้ติดตาม: สร้างเนื้อหาน่าสนใจและโปรโมทร้าน',
                    'products' => 'เพิ่มจำนวนสินค้า: เพิ่มความหลากหลายของสินค้าในร้าน',
                    'age' => 'ร้านใหม่: สะสมผลงานต่อไปเรื่อยๆ',
                    'trophies' => 'สะสม Trophy: ทำตามเป้าหมายเพื่อรับ Trophy',
                    default => 'ปรับปรุง ' . $key,
                };
            }
        }

        $eligible = $scoreResult['score'] >= self::MIN_PREMIUM_SCORE;

        return [
            'eligible' => $eligible,
            'score' => $scoreResult['score'],
            'criteria' => $scoreResult['criteria'],
            'message' => $eligible
                ? '🎉 ยินดีด้วย! ร้านของคุณมีคุณสมบัติครบถ้วนเพื่อเป็นร้าน Premium'
                : '📈 อีกนิดเดียว! ปรับปรุงตามคำแนะนำเพื่อเข้าสู่ร้าน Premium',
            'tips' => $tips,
            'min_score' => self::MIN_PREMIUM_SCORE,
            'progress' => min(100, ($scoreResult['score'] / self::MIN_PREMIUM_SCORE) * 100),
        ];
    }

    /**
     * หมดอายุร้าน Premium ที่เลยกำหนด
     */
    public function expireOldPremiumStores(): int
    {
        $expired = PremiumStore::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $premium) {
            $premium->update(['status' => 'expired']);
            $premium->store->update(['is_premium' => false]);
            $count++;
        }

        return $count;
    }
}
