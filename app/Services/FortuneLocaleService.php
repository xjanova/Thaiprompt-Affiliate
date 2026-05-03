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
     * Logic:
     * - Reset static $current ก่อน (กัน leak ข้าม queue job)
     * - Manual choice (picker) ชนะเสมอ
     * - LINE: เปิด detect เฉพาะ "ชื่อเป็นลาวชัดเจน" → switch เป็น lo + persist
     *         (ผู้ใช้เดิมไม่กระทบ — ชื่อไทย/อังกฤษยังคงได้ 'th')
     * - FB: stored manual → ใช้ stored
     * - FB: detect จาก text + name → ใช้ Lao ถ้า text หรือ name ชี้ว่าเป็นลาว
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

        // Manual choice ชนะเสมอ (ทุก platform)
        if ($row && $row->source === self::SOURCE_MANUAL) {
            return $row->locale ?: self::LOCALE_TH;
        }

        $stored = $row?->locale;

        // 🇱🇦 LINE: detect จากชื่ออย่างเดียว (text-based detect ปิดอยู่เดิม)
        //    เกณฑ์เข้ม — ต้องเป็นลาวล้วน ถึงจะ switch (กันชื่อไทยที่ปนตัวอักษรลาวเฉยๆ)
        if ($platform !== 'facebook') {
            // ถ้าเคย persist auto ไว้แล้ว — เคารพค่าเดิม (กัน flip ตอน profile API ล้มเหลว)
            if ($stored === self::LOCALE_LO) {
                return self::LOCALE_LO;
            }

            if ($nameDetected === self::LOCALE_LO) {
                self::set($platform, $userId, self::LOCALE_LO, self::SOURCE_AUTO);

                return self::LOCALE_LO;
            }

            return self::LOCALE_TH;
        }

        // 📘 Facebook: combine text + name signals
        $textDetected = self::detectFromText($messageText);
        $detected = $textDetected;

        // ถ้า text ดูเป็นไทย แต่ชื่อเป็นลาวชัดเจน → ใช้ลาว (ชื่อ stable กว่า)
        if ($detected === self::LOCALE_TH && $nameDetected === self::LOCALE_LO) {
            $detected = self::LOCALE_LO;
        }

        // 🛡️ Persist:
        //   - signal จากชื่อ (Lao) → persist ทันที (no min text length — ชื่อ stable)
        //   - signal จาก text → ใช้กฎเดิม (text ≥ 3 อักขระ)
        if ($detected !== $stored) {
            if ($nameDetected === self::LOCALE_LO && $detected === self::LOCALE_LO) {
                self::set($platform, $userId, self::LOCALE_LO, self::SOURCE_AUTO);
            } else {
                $textLen = $messageText ? mb_strlen(trim($messageText)) : 0;
                if ($textLen >= 3) {
                    self::set($platform, $userId, $detected, self::SOURCE_AUTO);
                }
            }
        }

        return $detected;
    }

    /**
     * Locale ปัจจุบันของ request (อ่านจากที่ ChannelManager ตั้งไว้)
     */
    public static function current(): string
    {
        return self::$current;
    }

    /**
     * ตั้ง locale ปัจจุบัน (ChannelManager เรียกก่อนเข้า conversationService)
     */
    public static function setCurrent(string $locale): void
    {
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
        return self::current() === self::LOCALE_LO ? $loText : $thText;
    }
}
