<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🔴 (2026-11-04) สถิติสดของแม่หมอ — ป้ายตัวเลขหน้าห้องของเว็บจันทรา
 *
 * เจ้าของสั่ง: "ใช้คิวหรือตัวเลขคน dm จากทุกช่องทาง เป็นการนับคนกำลังสนทนาเพื่อเริ่มดูดวง ... เลขจริง"
 * และ "มีคนดูแล้วกี่คน ใช้บิลจริง ทั้งหมด ตั้งแต่มีบิล"
 *
 * ทุกตัวเลขต้องเป็นของจริง (หน้าเว็บเขียนว่า "ไม่เดา ไม่มโน") — ห้ามบวกเลขปลอมที่นี่:
 *   - chatting_now : ลูกค้าที่ส่งข้อความหาแม่หมอใน WINDOW_MINUTES นาทีล่าสุด (FB/LINE/Telegram/เว็บ)
 *   - bills_total  : บิลที่จ่ายแล้วทั้งหมดทุกช่องทาง (บิลเว็บจันทราอยู่ใน fortune_readings ด้วย platform=juntra)
 *                    บิลที่คืนเงิน/ยกเลิก voidApproval พลิก is_paid เป็น false จึงหลุดออกเอง
 *   - reading_queue: บิลที่จ่ายแล้วและกำลังอยู่ในขั้นเปิดไพ่/ทำนายกับบอทตอนนี้
 */
class FortuneLiveActivity
{
    /** นับว่า "กำลังสนทนา" ถ้าส่งข้อความมาภายในกี่นาที */
    public const WINDOW_MINUTES = 5;

    /** เขียน DB ไม่เกิน 1 ครั้งต่อลูกค้าต่อช่วงนี้ (วินาที) — แชทรัว ๆ ไม่ต้อง upsert ทุกข้อความ */
    private const TOUCH_EVERY_SECONDS = 60;

    /** กวาดแถวที่เก่ากว่า 1 วันทิ้ง ราว 1 ใน N ครั้งที่เขียน */
    private const PRUNE_ONE_IN = 200;

    /** แคชผลรวมกี่วินาที — เว็บดึงทุก ~15 วิ ทุกหน้าที่เปิดอยู่ ห้ามนับ DB ทุกคำขอ */
    private const STATS_TTL_SECONDS = 15;

    /** สถานะบิลที่จ่ายแล้วและยังอยู่ระหว่างเปิดไพ่/ทำนาย = คิวที่กำลังดำเนินอยู่จริง */
    public const QUEUE_STATUSES = [
        FortuneReading::STATUS_PAID,
        FortuneReading::STATUS_CELTIC_PICKING,
        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
        FortuneReading::STATUS_CELTIC_GENERATING,
        FortuneReading::STATUS_CELTIC_QA_PROMPT,
    ];

    /** บิลค้างสถานะเกินนี้ (นาที) ไม่นับเป็นคิว — ลูกค้าเดินจากไปแล้ว ไม่ใช่คนที่กำลังรอ */
    private const QUEUE_STALE_MINUTES = 30;

    /**
     * บันทึกว่าลูกค้าคนนี้เพิ่งทักมา — เรียกจากทางเข้าข้อความ (best-effort ห้ามทำให้บอทพัง)
     *
     * @param  string  $platform  facebook | line | telegram | web
     * @param  string  $userKey  id ของลูกค้าในช่องทางนั้น (PSID / LINE userId / user_ref ของเว็บ)
     */
    public function touch(string $platform, string $userKey): void
    {
        $platform = mb_substr(trim($platform), 0, 16);
        $userKey = mb_substr(trim($userKey), 0, 100);
        if ($platform === '' || $userKey === '') {
            return;
        }

        try {
            // Cache::add = atomic — คนเดิมส่งรัว ๆ ใน 1 นาที เขียน DB ครั้งเดียว
            if (! Cache::add("fortune:live:touch:{$platform}:{$userKey}", 1, self::TOUCH_EVERY_SECONDS)) {
                return;
            }

            DB::table('fortune_live_activity')->upsert(
                [['platform' => $platform, 'user_key' => $userKey, 'last_seen_at' => now()]],
                ['platform', 'user_key'],
                ['last_seen_at']
            );

            // ตารางนี้ใช้แค่ "ใครทักมาเมื่อกี้" — สุ่มกวาดแถวเก่าทิ้งเป็นครั้งคราว ไม่ให้โตตามจำนวนลูกค้าทั้งหมด
            if (random_int(1, self::PRUNE_ONE_IN) === 1) {
                DB::table('fortune_live_activity')->where('last_seen_at', '<', now()->subDay())->delete();
            }
        } catch (\Throwable $e) {
            Log::debug('FortuneLiveActivity touch skipped', ['error' => $e->getMessage()]);
        }
    }

    /** จำนวนลูกค้า (ไม่ซ้ำคน) ที่ทักมาใน WINDOW_MINUTES นาทีล่าสุด ทุกช่องทาง */
    public function chattingNow(): int
    {
        return (int) DB::table('fortune_live_activity')
            ->where('last_seen_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->count();
    }

    /** บิลที่จ่ายแล้วทั้งหมดตั้งแต่มีบิล ทุกช่องทาง */
    public function billsTotal(): int
    {
        return FortuneReading::paid()->count();
    }

    /** บิลที่จ่ายแล้วและกำลังเปิดไพ่/ทำนายอยู่กับบอทตอนนี้ */
    public function readingQueue(): int
    {
        return FortuneReading::paid()
            ->whereIn('conversation_status', self::QUEUE_STATUSES)
            ->where('updated_at', '>=', now()->subMinutes(self::QUEUE_STALE_MINUTES))
            ->count();
    }

    /**
     * ชุดตัวเลขสำหรับเว็บ (แคชสั้น ๆ) — ตัวไหนนับไม่ได้คืน null ให้เว็บซ่อนช่องนั้น ไม่ใช่เดาเลข
     *
     * @return array{chatting_now:?int, window_minutes:int, bills_total:?int, reading_queue:?int, generated_at:string}
     */
    public function stats(): array
    {
        return Cache::remember('fortune:live:stats', self::STATS_TTL_SECONDS, fn () => [
            'chatting_now' => $this->safe(fn () => $this->chattingNow()),
            'window_minutes' => self::WINDOW_MINUTES,
            'bills_total' => $this->safe(fn () => $this->billsTotal()),
            'reading_queue' => $this->safe(fn () => $this->readingQueue()),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function safe(callable $count): ?int
    {
        try {
            return (int) $count();
        } catch (\Throwable $e) {
            Log::warning('FortuneLiveActivity count failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
