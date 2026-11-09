<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ⏱️ (2026-11-09) คุมจังหวะตอบใต้คอมเมนต์ — หน่วงเวลาแบบสุ่ม + เว้นระยะต่อเพจ + เพดานต่อโพสต์/ต่อเพจ
 *
 * ทำไม: บอทเดิมตอบภายในไม่กี่วินาทีหลังคนคอมเมนต์ ทุกอัน ทุกโพสต์
 * พอโพสต์ไหนคนแห่คอมเมนต์ (วัดบน prod 7 วัน: สูงสุด 4 อัน/นาที/เพจ, 16 อัน/ชม./โพสต์)
 * = เพจยิง POST /{comment}/comments รัว ๆ ในหลักวินาที ⇒ ดูเป็นบอท/สแปมในสายตา Facebook
 *
 * กติกา:
 * - คำตอบแรกของเพจ: รอ 20-75 วินาที (สุ่ม)
 * - คำตอบถัดไปบนเพจเดียวกัน: ห่างจากคิวก่อนหน้าอีก 25-55 วินาที (สุ่ม) — คอมเมนต์รัวก็ทยอยตอบ
 * - คิวยาวเกิน 30 นาที ⇒ ไม่ตอบใต้คอมเมนต์ (ตอบช้าขนาดนั้นไม่มีความหมาย และแปลว่าคอมเมนต์ท่วมจริง)
 * - เพดาน 20 คำตอบ/โพสต์/ชม. และ 80 คำตอบ/เพจ/ชม. ⇒ เกินแล้วข้ามคำตอบสาธารณะ (DM ยังส่งตามปกติ)
 *
 * ค่าเพดานตั้งให้สูงกว่าทราฟฟิกจริงพอสมควร ⇒ วันปกติยังตอบทุกคน (ตามที่เจ้าของสั่ง 2026-11-05)
 * จะข้ามเฉพาะตอนคอมเมนต์ท่วมผิดปกติ ซึ่งเป็นช่วงที่ Facebook จับตาที่สุด
 */
class CommentReplyPacer
{
    /** หน่วงคำตอบแรกของเพจ (วินาที) */
    public const FIRST_DELAY_MIN = 20;

    public const FIRST_DELAY_MAX = 75;

    /** ระยะห่างระหว่างคำตอบบนเพจเดียวกัน (วินาที) */
    public const GAP_MIN = 25;

    public const GAP_MAX = 55;

    /** คิวยาวสุดที่ยอมรอ (วินาที) */
    public const MAX_DEFER = 1800;

    /** เพดานคำตอบต่อโพสต์ต่อชั่วโมง */
    public const POST_CAP_PER_HOUR = 20;

    /** เพดานคำตอบต่อเพจต่อชั่วโมง */
    public const PAGE_CAP_PER_HOUR = 80;

    /**
     * ตัวระบุเพจสำหรับความจำกันซ้ำ/คิวตอบ — สาขาจาก context ก่อน ไม่งั้นใช้ page id ของ settings
     *
     * @param  string|null  $fallbackPageId  facebook_page_id ของ settings ตอนนี้
     */
    public static function pageKey(?string $fallbackPageId = null): string
    {
        try {
            $id = FortunePageContext::currentId();
        } catch (\Throwable $e) {
            $id = null;
        }

        if ($id !== null) {
            return 'page:'.$id;
        }

        return 'fb:'.((string) $fallbackPageId !== '' ? (string) $fallbackPageId : 'default');
    }

    /**
     * จองคิวตอบ 1 คำตอบ
     *
     * @param  string  $pageKey  ตัวระบุเพจ (สาขา)
     * @param  string|null  $postId  id โพสต์
     * @return int|null จำนวนวินาทีที่ต้องรอก่อนตอบ — null = ห้ามตอบรอบนี้ (เกินเพดาน/คิวยาวเกิน)
     *
     * @example
     * $delay = CommentReplyPacer::reserve('107173337600346', '107173337600346_123');
     * // 42 (คิวว่าง) หรือ 95 (มีคำตอบรอคิวอยู่ก่อน) หรือ null (โพสต์นี้ตอบครบเพดานชั่วโมงนี้แล้ว)
     */
    public static function reserve(string $pageKey, ?string $postId): ?int
    {
        try {
            $lock = Cache::lock('fcr:pace:lock:'.md5($pageKey), 5);

            return $lock->block(2, fn () => self::reserveUnlocked($pageKey, $postId));
        } catch (\Throwable $e) {
            // ล็อกไม่ได้ → หน่วงแบบสุ่มอย่างเดียว (ยังดีกว่าตอบทันที) ไม่นับเพดาน
            Log::debug('⏱️ CommentReplyPacer: ล็อกไม่ได้ ใช้หน่วงสุ่มอย่างเดียว', ['error' => $e->getMessage()]);

            return random_int(self::FIRST_DELAY_MIN, self::FIRST_DELAY_MAX);
        }
    }

    /**
     * ตัวจองจริง (เรียกภายใต้ล็อกต่อเพจ)
     */
    protected static function reserveUnlocked(string $pageKey, ?string $postId): ?int
    {
        $hour = now()->format('YmdH');
        $pageCountKey = 'fcr:pace:page:'.md5($pageKey).':'.$hour;
        $postCountKey = $postId ? 'fcr:pace:post:'.md5($postId).':'.$hour : null;

        $pageCount = (int) Cache::get($pageCountKey, 0);
        if ($pageCount >= self::PAGE_CAP_PER_HOUR) {
            Log::info('⏱️ ตอบคอมเมนต์: ข้าม — เพจตอบครบเพดานชั่วโมงนี้แล้ว', [
                'page' => $pageKey,
                'count' => $pageCount,
            ]);

            return null;
        }

        $postCount = $postCountKey ? (int) Cache::get($postCountKey, 0) : 0;
        if ($postCountKey && $postCount >= self::POST_CAP_PER_HOUR) {
            Log::info('⏱️ ตอบคอมเมนต์: ข้าม — โพสต์นี้ตอบครบเพดานชั่วโมงนี้แล้ว', [
                'page' => $pageKey,
                'post_id' => $postId,
                'count' => $postCount,
            ]);

            return null;
        }

        // ใช้นาฬิกาของ Laravel (ไม่ใช่ time()) — เทสต์เลื่อนเวลาได้
        $now = now()->getTimestamp();
        $nextKey = 'fcr:pace:next:'.md5($pageKey);
        $lastSlot = (int) Cache::get($nextKey, 0);

        $slot = max(
            $now + random_int(self::FIRST_DELAY_MIN, self::FIRST_DELAY_MAX),
            $lastSlot + random_int(self::GAP_MIN, self::GAP_MAX)
        );
        $delay = $slot - $now;

        if ($delay > self::MAX_DEFER) {
            Log::info('⏱️ ตอบคอมเมนต์: ข้าม — คิวตอบของเพจยาวเกิน 30 นาที (คอมเมนต์ท่วม)', [
                'page' => $pageKey,
                'post_id' => $postId,
                'delay' => $delay,
            ]);

            return null;
        }

        Cache::put($nextKey, $slot, self::MAX_DEFER + 3600);
        Cache::put($pageCountKey, $pageCount + 1, 3700);
        if ($postCountKey) {
            Cache::put($postCountKey, $postCount + 1, 3700);
        }

        return $delay;
    }
}
