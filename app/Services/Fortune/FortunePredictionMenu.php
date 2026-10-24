<?php

namespace App\Services\Fortune;

use App\Jobs\SyncFortunePredictionMenuJob;
use App\Models\FortuneReading;
use App\Models\LineRichMenu;
use App\Services\FortuneRichMenuService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🔮 (2026-10-24) สลับ Rich Menu LINE รายคน: กำลังทำนาย ↔ เมนูหลัก
 *
 * owner: "เอาไอ้ปุ่ม ดูดวง ในระหว่างทำนายออกไปเลย มัน งง"
 *   เคสจริง reading 14367: บอทถามเวลาเกิด → ลูกค้ากดปุ่ม "ดูดวง" บนเมนู → บอทเงียบ → ค้าง 22 นาที
 *
 * กติกา:
 *   - มีบิลกำลังทำนาย (FortuneReading::findInPredictionFor — แหล่งเดียวกับ Hard Guard)
 *     → ผูกเมนูช่วงทำนาย (ปุ่มดูดวงกลายเป็น "กำลังทำนาย" ส่ง STEP_REQUEST_TEXT)
 *   - ไม่มี → ผูกเมนูหลักคืน
 *
 * จังหวะที่เรียก:
 *   1. ทุกครั้งที่บอทส่งข้อความหา LINE (FortuneChannelManager::sendResponse → queueSync)
 *      — งานวิ่งใน queue ไม่หน่วงคำตอบลูกค้า
 *   2. fortune:prediction-menu-sweep ทุก 5 นาที — เก็บคนที่ทำนายจบโดยไม่มีข้อความตามมา
 *
 * การผูกเมนูไม่ใช่การส่งข้อความ ⇒ ไม่กินโควต้า push 300/เดือน
 */
class FortunePredictionMenu
{
    /** ข้อความที่ปุ่ม "กำลังทำนาย" ส่งเข้ามา — Hard Guard ตอบเตือนขั้นที่รออยู่ */
    public const STEP_REQUEST_TEXT = 'ขั้นตอนตอนนี้';

    /** rich menu id ที่ผูกให้ลูกค้าคนนี้ล่าสุด (กันยิง API ซ้ำทุกข้อความ) */
    protected const LINKED_KEY = 'fortune:line-menu-linked:';

    /** กันเข้าคิวรัว — บอทส่งหลายกล่องติดกันใน 1 เทิร์น */
    protected const QUEUED_KEY = 'fortune:line-menu-sync-queued:';

    protected const LINKED_TTL_DAYS = 30;

    public function __construct(protected FortuneRichMenuService $menus) {}

    public static function isStepRequest(string $text): bool
    {
        return trim($text) === self::STEP_REQUEST_TEXT;
    }

    public static function isLineUserId(?string $id): bool
    {
        return is_string($id) && preg_match('/^U[0-9a-f]{32}$/i', $id) === 1;
    }

    /**
     * เข้าคิวเช็คเมนูของลูกค้า (หน่วง 3 วิ ให้ state ของเทิร์นนี้ลง DB ครบก่อน)
     */
    public static function queueSync(string $lineUserId): void
    {
        if (! self::isLineUserId($lineUserId)) {
            return;
        }

        if (! Cache::add(self::QUEUED_KEY.$lineUserId, 1, 5)) {
            return;
        }

        SyncFortunePredictionMenuJob::dispatch($lineUserId)->delay(now()->addSeconds(3));
    }

    /**
     * ผูกเมนูที่ถูกต้องให้ลูกค้าคนนี้
     *
     * @return string|null rich menu id ที่ลูกค้าควรเห็นตอนนี้ / null = ไม่มีเมนูหลักให้ผูก
     */
    public function sync(string $lineUserId): ?string
    {
        if (! self::isLineUserId($lineUserId)) {
            return null;
        }

        $mainId = LineRichMenu::where('name', FortuneRichMenuService::MAIN_MENU_NAME)
            ->where('is_active', true)
            ->latest('id')
            ->value('rich_menu_id');
        if (! $mainId) {
            return null;
        }

        $desired = $mainId;
        if (FortuneReading::findInPredictionFor($lineUserId) !== null) {
            // สร้างเมนูช่วงทำนายไม่ได้ (custom image / LINE ล้ม) → อยู่เมนูหลักต่อ ข้อความเตือนยังทำงาน
            $desired = $this->menus->ensurePredictingMenu() ?? $mainId;
        }

        $linked = Cache::get(self::LINKED_KEY.$lineUserId);
        if ($linked === $desired) {
            return $desired;
        }

        // ไม่รู้ว่าผูกอะไรอยู่ (ลูกค้าใหม่ / cache ถูกล้างตอน deploy) และควรอยู่เมนูหลัก
        //   ทุกคนถูก bulk link เมนูหลักไว้แล้ว (FortuneRichMenuService::pushMenuToLinkedUsers)
        //   ⇒ ยิง API ถามเฉพาะคนที่เพิ่งมีบิลจ่ายเงิน (อาจค้างเมนูช่วงทำนายจากก่อนล้าง cache)
        if ($linked === null && $desired === $mainId) {
            if (! $this->hadRecentPaidReading($lineUserId)) {
                return $mainId;
            }

            // null = ไม่มี per-user link (404) *หรือ* API ล้ม — แยกไม่ได้ ⇒ ห้ามเดาว่าอยู่เมนูหลัก
            //   (เดาผิด = cache main 30 วัน ทั้งที่ค้างเมนูช่วงทำนาย) — ตกไปผูกเมนูหลักซ้ำแทน ปลอดภัยเสมอ
            $actual = $this->menus->getLineService()->getUserRichMenuId($lineUserId);
            if ($actual !== null && ! $this->isPredictingMenuId($actual)) {
                Cache::put(self::LINKED_KEY.$lineUserId, $mainId, now()->addDays(self::LINKED_TTL_DAYS));

                return $mainId;
            }
        }

        if (! $this->menus->getLineService()->linkRichMenuToUser($lineUserId, $desired)) {
            return $linked;
        }

        Cache::put(self::LINKED_KEY.$lineUserId, $desired, now()->addDays(self::LINKED_TTL_DAYS));

        Log::info('FortunePredictionMenu: สลับเมนู', [
            'user_id' => $lineUserId,
            'menu' => $desired === $mainId ? 'main' : 'predicting',
            'rich_menu_id' => $desired,
        ]);

        return $desired;
    }

    /**
     * เก็บคนที่ทำนายจบแล้วแต่ยังค้างเมนูช่วงทำนาย (จบโดยไม่มีข้อความตามมา)
     *
     * @return int จำนวนลูกค้าที่ตรวจ
     */
    public function sweep(int $lookbackHours = 6): int
    {
        $ids = [];
        FortuneReading::where('is_paid', true)
            ->where('updated_at', '>=', now()->subHours($lookbackHours))
            ->whereNotIn('conversation_status', FortuneReading::IN_PREDICTION_STATUSES)
            ->select(['facebook_user_id', 'platform_user_id'])
            ->get()
            ->each(function ($r) use (&$ids) {
                foreach ([$r->platform_user_id, $r->facebook_user_id] as $id) {
                    if (self::isLineUserId($id)) {
                        $ids[$id] = true;
                    }
                }
            });

        foreach (array_keys($ids) as $id) {
            try {
                $this->sync($id);
            } catch (\Throwable $e) {
                Log::warning('FortunePredictionMenu: sweep sync ล้ม (ข้ามคนนี้)', [
                    'user_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return count($ids);
    }

    protected function hadRecentPaidReading(string $lineUserId): bool
    {
        return FortuneReading::where(function ($q) use ($lineUserId) {
            $q->where('facebook_user_id', $lineUserId)
                ->orWhere('platform_user_id', $lineUserId);
        })
            ->where('is_paid', true)
            ->where('updated_at', '>=', now()->subDays(3))
            ->exists();
    }

    protected function isPredictingMenuId(?string $richMenuId): bool
    {
        return $richMenuId !== null && LineRichMenu::where('name', FortuneRichMenuService::PREDICTING_MENU_NAME)
            ->where('rich_menu_id', $richMenuId)
            ->exists();
    }
}
