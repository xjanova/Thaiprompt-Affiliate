<?php

namespace App\Services\Fortune;

use App\Models\FortunePage;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Log;

/**
 * 🏬 FortunePageContext — "ตอนนี้กำลังทำงานให้สาขาไหน"
 *
 * เป็นแหล่งความจริงเดียวของ page context ทั้งระบบแม่หมอ
 * ทุกอย่างที่ต้องรู้ว่า "เพจไหน" ให้ถามที่นี่ ห้ามอ่าน settings->facebook_page_id ตรงๆ อีก
 *
 * วิธีใช้:
 *   FortunePageContext::set($page);          // ต้นทาง webhook / ต้น job
 *   FortunePageContext::currentId();          // ใช้ติดป้าย fortune_page_id
 *   FortunePageContext::forget();             // จบงาน
 *
 * ⚠️ กับดักที่ต้องระวัง (เคยกัดมาแล้วกับ settings cache):
 *   1. queue worker เป็นโปรเซสรันยาว — context ของ job ก่อนหน้าจะค้างถ้าไม่ forget()
 *      → ทุก job ต้อง set() ที่ต้น handle() และ forget() ใน finally เสมอ
 *   2. เปลี่ยน context = ต้องล้าง memo ของ FortuneTellingSetting ทันที
 *      ไม่งั้นสาขา B จะได้ค่าของสาขา A ไปอีก 5 วินาที
 */
class FortunePageContext
{
    /** สาขาที่กำลังทำงานอยู่ (null = โหมด global เช่น หน้าแอดมิน/คอนโซล) */
    protected static ?FortunePage $current = null;

    /**
     * memo ของการ resolve เพจ: "platform:external_id" => FortunePage|false
     *
     * @var array<string, FortunePage|false>
     */
    protected static array $resolved = [];

    /** เวลาที่ memo ถูกเติมล่าสุด (unix float) */
    protected static float $resolvedAt = 0.0;

    /**
     * TTL ของ memo — สั้นเท่ากับของ FortuneTellingSetting
     * เพื่อให้แอดมินแก้ token แล้วมีผลใน worker ภายในไม่กี่วินาที โดยไม่ต้อง restart
     */
    protected const MEMO_TTL_SEC = 5;

    /**
     * ตั้งสาขาปัจจุบัน
     */
    public static function set(?FortunePage $page): void
    {
        $changed = (self::$current?->id) !== ($page?->id);

        self::$current = $page;

        if ($changed) {
            // 🔑 บังคับให้ getSettings() อ่านใหม่ตาม context ใหม่ทันที
            FortuneTellingSetting::clearSettingsCache();
        }
    }

    /**
     * ล้าง context (เรียกตอนจบงานเสมอ)
     */
    public static function forget(): void
    {
        self::set(null);
    }

    /**
     * สาขาปัจจุบัน
     */
    public static function current(): ?FortunePage
    {
        return self::$current;
    }

    /**
     * id ของสาขาปัจจุบัน — ใช้ติดป้าย fortune_page_id
     */
    public static function currentId(): ?int
    {
        return self::$current?->id;
    }

    /**
     * หาเพจจาก external id ที่ Facebook ส่งมาใน webhook (entry.id)
     *
     * @param  string|null  $externalId  Facebook Page ID
     * @param  string  $platform  facebook / line
     */
    public static function resolveByExternalId(?string $externalId, string $platform = 'facebook'): ?FortunePage
    {
        if (empty($externalId)) {
            return null;
        }

        self::expireMemoIfStale();

        $key = $platform.':'.$externalId;

        if (array_key_exists($key, self::$resolved)) {
            $hit = self::$resolved[$key];

            return $hit === false ? null : $hit;
        }

        $page = FortunePage::query()
            ->where('platform', $platform)
            ->where('external_page_id', $externalId)
            ->first();

        self::$resolved[$key] = $page ?: false;
        self::$resolvedAt = microtime(true);

        return $page;
    }

    /**
     * สาขาหลัก (is_default) — ใช้เป็น fallback
     */
    public static function default(?string $platform = 'facebook'): ?FortunePage
    {
        self::expireMemoIfStale();

        $key = 'default:'.$platform;

        if (array_key_exists($key, self::$resolved)) {
            $hit = self::$resolved[$key];

            return $hit === false ? null : $hit;
        }

        $page = FortunePage::query()
            ->where('platform', $platform)
            ->where('is_default', true)
            ->first()
            ?? FortunePage::query()->where('platform', $platform)->orderBy('id')->first();

        self::$resolved[$key] = $page ?: false;
        self::$resolvedAt = microtime(true);

        return $page;
    }

    /**
     * ตั้ง context จาก external id พร้อม fallback ไปสาขาหลัก
     *
     * คืนค่า: สาขาที่ตั้งได้ (null = ไม่เจอเลย)
     */
    public static function bindFromExternalId(?string $externalId, string $platform = 'facebook'): ?FortunePage
    {
        $page = self::resolveByExternalId($externalId, $platform);

        if (! $page) {
            $page = self::default($platform);

            if ($page && ! empty($externalId)) {
                // เพจส่ง webhook มาแต่ยังไม่ได้เพิ่มในระบบสาขา — แอดมินต้องรู้
                Log::warning('🏬 ไม่พบสาขาสำหรับ page id นี้ ใช้สาขาหลักแทน', [
                    'external_page_id' => $externalId,
                    'platform' => $platform,
                    'fallback_page_id' => $page->id,
                ]);
            }
        }

        self::set($page);

        return $page;
    }

    /**
     * ตั้ง context จากแถวข้อมูลที่มี fortune_page_id อยู่แล้ว (เช่น FortuneReading)
     *
     * ใช้ใน queue job ที่รับมาแค่ reading_id
     */
    public static function bindFromModel(?object $model): ?FortunePage
    {
        $pageId = $model->fortune_page_id ?? null;

        if (! $pageId) {
            return null;
        }

        return self::bindFromId((int) $pageId);
    }

    /**
     * ตั้ง context จาก fortune_pages.id ตรงๆ (ใช้ใน job ที่ serialize id มา)
     */
    public static function bindFromId(?int $pageId): ?FortunePage
    {
        if (! $pageId) {
            self::forget();

            return null;
        }

        self::expireMemoIfStale();

        $key = 'id:'.$pageId;

        if (array_key_exists($key, self::$resolved)) {
            $page = self::$resolved[$key] ?: null;
        } else {
            $page = FortunePage::find($pageId);
            self::$resolved[$key] = $page ?: false;
            self::$resolvedAt = microtime(true);
        }

        self::set($page);

        return $page;
    }

    /**
     * รันโค้ดภายใต้ context ของสาขาหนึ่ง แล้วคืน context เดิมเสมอ
     *
     * ใช้กับ cron ที่ต้องวนโพสต์ทีละเพจ:
     *   foreach ($pages as $page) { FortunePageContext::run($page, fn () => $this->postTo($page)); }
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(?FortunePage $page, callable $callback)
    {
        $previous = self::$current;

        self::set($page);

        try {
            return $callback();
        } finally {
            self::set($previous);
        }
    }

    /**
     * ล้าง memo ทั้งหมด (เรียกหลังแอดมินแก้ข้อมูลสาขา)
     */
    public static function flushMemo(): void
    {
        self::$resolved = [];
        self::$resolvedAt = 0.0;
        FortuneTellingSetting::clearSettingsCache();
    }

    /**
     * ทิ้ง memo ถ้าเกิน TTL
     */
    protected static function expireMemoIfStale(): void
    {
        if (self::$resolved !== [] && (microtime(true) - self::$resolvedAt) >= self::MEMO_TTL_SEC) {
            self::$resolved = [];
        }
    }
}
