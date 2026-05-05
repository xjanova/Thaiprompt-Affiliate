<?php

namespace App\Services;

use App\Models\FortuneUserLanguage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FortuneLocaleService
 *
 * จัดการภาษาของผู้ใช้ Fortune Bot ต่อ platform+user
 *
 * ออกแบบ:
 * - Facebook → auto-detect จาก message text + profile name
 * - LINE → auto-detect จาก display name (ลาวล้วน → 'lo'); default 'th'
 * - manual override (picker) → priority สูงกว่า auto ทุก platform
 * - มี static current() สำหรับเข้าถึงจาก template/builder ระหว่าง request
 *
 * ⚠️ ใช้แบบ additive — ผู้ใช้เดิมที่ชื่อไม่ใช่ลาวยังคงได้ 'th' เหมือนเดิม
 *    ถ้า table ยังไม่ migrate / DB error → fallback 'th' เสมอ (safe)
 */
class FortuneLocaleService
{
    public const LOCALE_TH = 'th';
    public const LOCALE_LO = 'lo';

    public const SOURCE_AUTO = 'auto';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_ADMIN = 'admin';

    /**
     * 🛑 (2026-05-05) KILL SWITCH — ปิดทั้งระบบ Lao
     *
     * User decision: ระบบเสถียรกว่าก่อนเพิ่ม Lao + Pay-Later — เอา Lao ออกหมด
     * ทุก method return 'th' เสมอ ไม่ persist anything
     * เปลี่ยนเป็น true เมื่อพร้อม re-enable
     */
    public const ENABLED = false;

    /**
     * Locale ปัจจุบันของ request (set ระหว่าง processMessage)
     */
    protected static string $current = self::LOCALE_TH;

    /**
     * Detect locale จาก text — Lao majority + threshold (กัน flip จาก noise)
     *
     * เกณฑ์ Lao:
     * - มีตัวลาวอย่างน้อย 2 ตัว (กัน accent stray char เดียวจาก keyboard ผิด)
     * - Lao มากกว่า Thai (strict >, ไม่ใช่ >=)
     * - หรือ มี Lao และไม่มี Thai เลย
     */
    public static function detectFromText(?string $text): string
    {
        if (empty($text)) {
            return self::LOCALE_TH;
        }

        // U+0E80–U+0EFF = Lao block
        $laoCount = preg_match_all('/[\x{0E80}-\x{0EFF}]/u', $text);
        // U+0E00–U+0E7F = Thai block
        $thaiCount = preg_match_all('/[\x{0E00}-\x{0E7F}]/u', $text);

        // ไม่มีตัวลาว → ไทย
        if ($laoCount === 0) {
            return self::LOCALE_TH;
        }

        // ไม่มีไทย + มีลาว ≥ 1 → ลาว
        if ($thaiCount === 0) {
            return self::LOCALE_LO;
        }

        // มีทั้งคู่: ต้อง Lao ≥ 2 และ Lao > Thai (ป้องกัน flip จาก noise)
        if ($laoCount >= 2 && $laoCount > $thaiCount) {
            return self::LOCALE_LO;
        }

        return self::LOCALE_TH;
    }

    /**
     * Detect locale จากชื่อ (display name / FB profile name)
     *
     * ชื่อเป็น signal ที่เสถียร (ไม่ค่อยเปลี่ยน) → ใช้ persist ทันทีได้
     * เกณฑ์เข้มงวด — ต้องมีตัวลาวและไม่มีไทยเลย ถึงจะ flag Lao
     *
     * - ลาวล้วน (มี Lao char ≥ 1 + ไม่มีไทย) → 'lo'
     * - ไทยล้วน (มี Thai char ≥ 1 + ไม่มีลาว) → 'th'
     * - English / mixed / empty → null (ไม่มี signal — ปล่อยให้ logic อื่นตัดสิน)
     *
     * ตัวอย่างชื่อลาว: "ສົມຊາຍ ວົງສາ", "ໂກນ ສຸກສະຫວັນ"
     * ตัวอย่างชื่อไทย: "สมชาย ใจดี"
     * ตัวอย่าง mixed/null: "John Smith", "ABC123", "" → null
     */
    public static function detectFromName(?string $name): ?string
    {
        if (empty($name)) {
            return null;
        }

        $laoCount = preg_match_all('/[\x{0E80}-\x{0EFF}]/u', $name);
        $thaiCount = preg_match_all('/[\x{0E00}-\x{0E7F}]/u', $name);

        // ลาวล้วน (มี Lao + ไม่มีไทย) → confident Lao
        if ($laoCount > 0 && $thaiCount === 0) {
            return self::LOCALE_LO;
        }

        // ไทยล้วน (มี Thai + ไม่มีลาว) → confident Thai
        if ($thaiCount > 0 && $laoCount === 0) {
            return self::LOCALE_TH;
        }

        // English / mixed / unknown → ไม่ตัดสิน
        return null;
    }

    /**
     * อ่าน locale ที่บันทึกไว้ของ user (จาก DB)
     * ถ้าไม่มี → 'th'
     */
    public static function getStored(string $platform, string $userId): ?string
    {
        try {
            $cacheKey = "fortune:locale:{$platform}:{$userId}";

            return Cache::remember($cacheKey, 300, function () use ($platform, $userId) {
                $row = FortuneUserLanguage::where('platform', $platform)
                    ->where('platform_user_id', $userId)
                    ->first();

                return $row?->locale;
            });
        } catch (\Throwable $e) {
            Log::warning('FortuneLocaleService::getStored error (fallback null)', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * บันทึก locale (auto-detect หรือ manual)
     *
     * Manual จะไม่ถูก auto override — เคารพการเลือกของ user
     */
    public static function set(string $platform, string $userId, string $locale, string $source = self::SOURCE_AUTO): void
    {
        if (! in_array($locale, [self::LOCALE_TH, self::LOCALE_LO], true)) {
            return;
        }

        try {
            $row = FortuneUserLanguage::where('platform', $platform)
                ->where('platform_user_id', $userId)
                ->first();

            // ถ้า user เคยเลือกเองแล้ว (manual) → auto ห้าม override
            if ($row && $row->source === self::SOURCE_MANUAL && $source === self::SOURCE_AUTO) {
                return;
            }

            // ถ้า locale ตรงกับที่บันทึกอยู่แล้ว — ไม่ต้อง update
            if ($row && $row->locale === $locale && $row->source === $source) {
                return;
            }

            FortuneUserLanguage::updateOrCreate(
                ['platform' => $platform, 'platform_user_id' => $userId],
                ['locale' => $locale, 'source' => $source]
            );

            Cache::forget("fortune:locale:{$platform}:{$userId}");
            Cache::forget("fortune:locale:row:{$platform}:{$userId}");
        } catch (\Throwable $e) {
            Log::warning('FortuneLocaleService::set error (silent)', [
                'platform' => $platform,
                'user_id' => $userId,
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve locale ที่ใช้จริงสำหรับ user คนนี้
     *
     * 🩹 (2026-05-05) Refactor — Text wins over name
     *   user spec: "ถ้าชื่อลาว แต่พิมพ์ไทย ให้ใช้ภาษาไทยเป็นหลัก"
     *
     * Logic ใหม่ (เหมือนทั้ง LINE + FB):
     *   1. Manual choice (picker) → ชนะเสมอ
     *   2. Text มี Thai chars + ไม่มี Lao chars → ไทย (ชื่อ Lao ก็ไม่ override)
     *   3. Text มี Lao chars + ไม่มี Thai chars → ลาว
     *   4. Text mixed → ใช้ threshold เดิม (Lao ≥ 2 + Lao > Thai → ลาว, ไม่งั้น ไทย)
     *   5. Text empty/English (ไม่มี script) → fallback ไป name detection
     *
     * เปลี่ยนแปลงจากของเดิม:
     *   - ❌ ลบ "name overrides text" rule (line 246) — text Thai + name Lao เคยถูก force เป็น Lao
     *   - ✅ Unify LINE + FB ใช้ text + name fallback เหมือนกัน
     *
     * @param  string|null  $messageText  ข้อความล่าสุดของ user
     * @param  string|null  $profileName  ชื่อ user (FB display name / LINE displayName)
     */
    public static function resolveForMessage(
        string $platform,
        string $userId,
        ?string $messageText = null,
        ?string $profileName = null,
    ): string {
        // 🛑 KILL SWITCH — ปิด Lao detection + persistence
        if (! self::ENABLED) {
            self::$current = self::LOCALE_TH;
            return self::LOCALE_TH;
        }

        // 🛡️ Reset stale state — จัดการ queue worker reuse
        self::$current = self::LOCALE_TH;

        $nameDetected = self::detectFromName($profileName);

        // อ่าน row ครั้งเดียว — เช็คทั้ง manual override + stored auto
        try {
            $cacheKey = "fortune:locale:row:{$platform}:{$userId}";
            $row = Cache::remember($cacheKey, 300, function () use ($platform, $userId) {
                return FortuneUserLanguage::where('platform', $platform)
                    ->where('platform_user_id', $userId)
                    ->first();
            });
        } catch (\Throwable $e) {
            // DB error / table ยังไม่ migrate → fallback ไทย
            return self::LOCALE_TH;
        }

        // 🔒 1. Manual choice ชนะเสมอ (ทุก platform)
        if ($row && $row->source === self::SOURCE_MANUAL) {
            return $row->locale ?: self::LOCALE_TH;
        }

        $stored = $row?->locale;

        // 🔍 2. Text-first detection — script-aware
        $textHasThai = $messageText && preg_match('/[\x{0E00}-\x{0E7F}]/u', $messageText);
        $textHasLao = $messageText && preg_match('/[\x{0E80}-\x{0EFF}]/u', $messageText);

        $detected = null;

        if ($textHasThai && ! $textHasLao) {
            // ✅ Pure Thai → ไทย (ชื่อ Lao ก็ไม่ override per user spec)
            $detected = self::LOCALE_TH;
        } elseif ($textHasLao && ! $textHasThai) {
            // ✅ Pure Lao → ลาว (ตัวเดียวก็พอ)
            $detected = self::LOCALE_LO;
        } elseif ($textHasThai && $textHasLao) {
            // ⚖️ Mixed → ใช้ threshold (Lao ≥ 2 + Lao > Thai → ลาว, ไม่งั้น ไทย)
            $detected = self::detectFromText($messageText);
        }
        // ถ้า $detected ยังเป็น null → text ไม่มี script signal (English/empty)
        // → fallback ไป name detection หรือ stored

        // 🔄 3. ถ้าไม่มี text signal → ใช้ stored ก่อน (เคารพประวัติ) แล้ว name
        if ($detected === null) {
            if ($stored !== null) {
                $detected = $stored;
            } elseif ($nameDetected === self::LOCALE_LO) {
                $detected = self::LOCALE_LO;
            } else {
                $detected = self::LOCALE_TH; // default
            }
        }

        // 🛡️ Persist auto-detect (text signal มีน้ำหนักมากที่สุด)
        //   - ถ้า text ชัดเจน (≥ 3 อักขระ) + detected ต่างจาก stored → persist
        //   - ถ้าไม่มี text แต่ name = Lao → persist Lao (จากชื่อ)
        if ($detected !== $stored) {
            $textLen = $messageText ? mb_strlen(trim($messageText)) : 0;
            $hasScriptSignal = $textHasThai || $textHasLao;

            if ($hasScriptSignal && $textLen >= 3) {
                // text มี script + ยาวพอ → persist (ลูกค้าเปลี่ยนภาษาจริง)
                self::set($platform, $userId, $detected, self::SOURCE_AUTO);
            } elseif (! $hasScriptSignal && $nameDetected === self::LOCALE_LO && $detected === self::LOCALE_LO) {
                // ไม่มี text signal + name Lao → persist Lao (default ลาว)
                self::set($platform, $userId, self::LOCALE_LO, self::SOURCE_AUTO);
            }
        }

        return $detected;
    }

    /**
     * Locale ปัจจุบันของ request (อ่านจากที่ ChannelManager ตั้งไว้)
     */
    public static function current(): string
    {
        // 🛑 KILL SWITCH — บังคับ TH เสมอ
        if (! self::ENABLED) {
            return self::LOCALE_TH;
        }

        return self::$current;
    }

    /**
     * ตั้ง locale ปัจจุบัน (ChannelManager เรียกก่อนเข้า conversationService)
     */
    public static function setCurrent(string $locale): void
    {
        // 🛑 KILL SWITCH — no-op (signature เก็บไว้ backwards compat)
        if (! self::ENABLED) {
            self::$current = self::LOCALE_TH;
            return;
        }

        if (in_array($locale, [self::LOCALE_TH, self::LOCALE_LO], true)) {
            self::$current = $locale;
        }
    }

    /**
     * Helper เลือกข้อความตาม current locale
     *
     * ใช้: FortuneLocaleService::lo('สวัสดี', 'ສະບາຍດີ')
     */
    public static function lo(string $thText, string $loText): string
    {
        // 🛑 KILL SWITCH — return Thai เสมอ ไม่สนใจ $loText
        if (! self::ENABLED) {
            return $thText;
        }

        return self::current() === self::LOCALE_LO ? $loText : $thText;
    }
}
