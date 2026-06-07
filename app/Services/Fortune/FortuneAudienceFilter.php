<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneLocaleService;

/**
 * 🌍 FortuneAudienceFilter (2026-06-07)
 *
 * ตัวกรอง "กลุ่มเป้าหมาย" ของ DM กลับอัตโนมัติ (ตอบคนคอมเมนต์/กดไลก์)
 * แอดมินเลือกได้ว่าจะส่ง / ไม่ส่ง ตาม:
 *   1. สัญชาติ  — ส่งให้คนต่างชาติด้วยไหม (dm_send_to_foreigners)
 *   2. อายุ     — ส่งเฉพาะช่วงอายุที่กำหนด (dm_filter_age_enabled + min/max)
 *
 * ⚠️ ข้อจำกัดสำคัญของ Facebook:
 *   - FB "ไม่บอกสัญชาติ/อายุ" ของคนที่คอมเมนต์หรือกดไลก์
 *   - สัญชาติ → เดาจาก "สคริปต์ตัวอักษรในชื่อ + ข้อความ" (heuristic)
 *   - อายุ → รู้เฉพาะลูกค้าที่เคยกรอกวันเกิดตอนดูดวงมาก่อน (FortuneReading.birth_date)
 *           ลูกค้าใหม่ที่ไม่เคยดูดวง = ไม่รู้อายุ → ใช้นโยบาย dm_age_unknown_action
 *
 * Fail-open ทุกกรณี: ถ้า detect ล้มเหลว/ไม่ชัด → "อนุญาตให้ส่ง" (ไม่บล็อกลูกค้าผิดพลาด)
 */
class FortuneAudienceFilter
{
    /**
     * วิเคราะห์ว่าควรส่ง DM กลับให้ user คนนี้ไหม
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $userId  platform user id (PSID)
     * @param  string|null  $name  ชื่อโปรไฟล์ (สัญญาณหลักของ "ต่างชาติ")
     * @param  string|null  $text  ข้อความล่าสุด เช่น คอมเมนต์ (reaction ไม่มี → null)
     * @param  FortuneTellingSetting|null  $settings  ส่ง instance ที่โหลดสด ๆ มาได้ (กัน cache เก่าบน worker)
     * @return array{allow: bool, reason: string|null}
     */
    public static function evaluate(
        string $platform,
        string $userId,
        ?string $name = null,
        ?string $text = null,
        ?FortuneTellingSetting $settings = null,
    ): array {
        try {
            $settings = $settings ?? FortuneTellingSetting::getSettings();

            // 1) 🌍 ตัวกรองสัญชาติ — ปิด "ส่งให้ต่างชาติ" + ตรวจพบว่าเป็นต่างชาติ → ไม่ส่ง
            if (! (bool) ($settings->dm_send_to_foreigners ?? true)) {
                $basis = (string) ($settings->dm_foreigner_detect_basis ?? 'script');
                if (self::isForeigner($platform, $userId, $name, $text, $basis)) {
                    return ['allow' => false, 'reason' => 'foreigner'];
                }
            }

            // 2) 🎂 ตัวกรองอายุ
            if ((bool) ($settings->dm_filter_age_enabled ?? false)) {
                $age = self::resolveAge($userId);

                if ($age === null) {
                    // ไม่รู้อายุ → ใช้นโยบายที่แอดมินตั้งไว้
                    if (($settings->dm_age_unknown_action ?? 'send') === 'skip') {
                        return ['allow' => false, 'reason' => 'age_unknown'];
                    }
                    // 'send' → ปล่อยผ่าน
                } else {
                    $min = $settings->dm_age_min !== null ? (int) $settings->dm_age_min : null;
                    $max = $settings->dm_age_max !== null ? (int) $settings->dm_age_max : null;

                    if (($min !== null && $age < $min) || ($max !== null && $age > $max)) {
                        return ['allow' => false, 'reason' => 'age_out_of_range'];
                    }
                }
            }

            return ['allow' => true, 'reason' => null];
        } catch (\Throwable $e) {
            // 🛡️ Fail-open — ระบบกรองห้ามทำให้ DM พัง
            return ['allow' => true, 'reason' => null];
        }
    }

    /**
     * 🌍 สคริปต์ "ต่างภาษา" ที่ถือว่าเป็นต่างชาติ (ลาว/พม่า/เขมร/จีน/ญี่ปุ่น/เกาหลี/อาหรับ ฯลฯ)
     *   ❌ ไม่รวม Latin (อังกฤษ) และ Thai
     */
    private const FOREIGN_SCRIPT_REGEX = '/['
        .'\x{0E80}-\x{0EFF}'  // Lao (ลาว)
        .'\x{1000}-\x{109F}'  // Myanmar (พม่า)
        .'\x{1780}-\x{17FF}'  // Khmer (เขมร)
        .'\x{4E00}-\x{9FFF}'  // CJK (จีน)
        .'\x{3040}-\x{30FF}'  // Hiragana + Katakana (ญี่ปุ่น)
        .'\x{AC00}-\x{D7AF}'  // Hangul (เกาหลี)
        .'\x{0600}-\x{06FF}'  // Arabic (อาหรับ)
        .'\x{0400}-\x{04FF}'  // Cyrillic (รัสเซีย)
        .'\x{0900}-\x{097F}'  // Devanagari (ฮินดี)
        .'\x{0980}-\x{09FF}'  // Bengali
        .']/u';

    /**
     * เดาว่า user คนนี้ "เป็นคนต่างชาติ" ไหม
     *
     * 🎯 (2026-06-07) ดู "ชื่อ" เป็นหลัก (per owner: "ต้องดูชื่อเป็นหลัก")
     *   — ข้อความคอมเมนต์ไม่ override ชื่อ. คนลาว/พม่า/เขมร/จีน ที่คอมเมนต์เป็นไทย ("สาธุ")
     *   ก็ยังถูกตรวจเป็นต่างชาติจาก "ชื่อ". ดูข้อความเป็น fallback เฉพาะเมื่อชื่อไม่มีสัญญาณ
     *
     * วิธีตรวจ (basis):
     *   - 'script'   (แนะนำ) ชื่อเป็นสคริปต์ต่างภาษา (ลาว/พม่า/เขมร/จีน/ญี่ปุ่น/เกาหลี/อาหรับ ฯลฯ)
     *                = ต่างชาติ ; **ชื่ออังกฤษล้วน = ไม่ถือว่าต่างชาติ** (ยกเว้นอังกฤษ ตามที่เจ้าของขอ)
     *   - 'no_thai'  ชื่อไม่มีอักษรไทยเลย (รวมอังกฤษ) = ต่างชาติ — เข้มสุด
     *   - 'lao_only' เฉพาะคนลาว
     */
    public static function isForeigner(
        string $platform,
        string $userId,
        ?string $name,
        ?string $text,
        string $basis = 'script',
    ): bool {
        try {
            // 1) ชื่อเป็นหลัก — ถ้าชื่อให้คำตอบชัด (ไทย→false / ต่างชาติ→true / อังกฤษ→false) ใช้เลย
            $byName = self::classifyScript($name, $basis, $platform, $userId);
            if ($byName !== null) {
                return $byName;
            }

            // 2) ชื่อไม่มีสัญญาณ (ว่าง/ตัวเลข/อีโมจิ/ไม่มีชื่อ เช่น reaction) → fallback ดูข้อความ
            return self::classifyScript($text, $basis, $platform, $userId) === true;
        } catch (\Throwable $e) {
            return false; // fail-open — ไม่ถือว่าต่างชาติเมื่อ detect ล้ม
        }
    }

    /**
     * จำแนกสคริปต์ของสตริงเดียว (ชื่อ หรือ ข้อความ) ตาม basis
     *
     * @return bool|null true = ต่างชาติ / false = ไทยหรืออังกฤษ(ตาม basis) / null = ไม่มีสัญญาณ
     */
    protected static function classifyScript(?string $value, string $basis, string $platform, string $userId): ?bool
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // มีอักษรไทย → คนไทยเสมอ (ไม่ใช่ต่างชาติ)
        if (self::hasThai($value)) {
            return false;
        }

        if ($basis === 'lao_only') {
            $lo = FortuneLocaleService::LOCALE_LO;
            if (FortuneLocaleService::detectFromText($value) === $lo
                || FortuneLocaleService::detectFromName($value) === $lo) {
                return true;
            }

            // ไม่ใช่ลาว: มีตัวอักษรอื่น (จีน/อังกฤษ ฯลฯ) → ไม่ถือว่าต่างชาติ (lao_only) ; ไม่มีตัวอักษร → ไม่มีสัญญาณ
            return preg_match('/\p{L}/u', $value) ? false : null;
        }

        // มีสคริปต์ต่างภาษาชัดเจน (ลาว/พม่า/เขมร/จีน ฯลฯ) → ต่างชาติ
        if (preg_match(self::FOREIGN_SCRIPT_REGEX, $value)) {
            return true;
        }

        // ไม่มีไทย ไม่มีสคริปต์ต่างภาษา → เหลือ Latin/ตัวเลข/อีโมจิ
        if (! preg_match('/\p{L}/u', $value)) {
            return null; // ไม่มีตัวอักษรเลย (ตัวเลข/อีโมจิ) → ไม่มีสัญญาณ
        }

        // Latin ล้วน (อังกฤษ ฯลฯ)
        if ($basis === 'no_thai') {
            return true; // เข้มสุด: ไม่มีไทย = ต่างชาติ (รวมอังกฤษ)
        }

        return false; // 'script' — ยกเว้นอังกฤษ → ไม่ถือว่าต่างชาติ
    }

    /**
     * หาอายุของ user จากวันเกิดที่เคยกรอกตอนดูดวง (ถ้ามี)
     *
     * @return int|null อายุ (ปี) หรือ null ถ้าไม่เคยกรอกวันเกิด / ค่าผิดปกติ
     */
    public static function resolveAge(string $userId): ?int
    {
        try {
            $birthDate = FortuneReading::findLatestBirthdate($userId);
            if (! $birthDate) {
                return null;
            }

            $age = $birthDate->age;

            // sanity check — กันวันเกิดผิดรูปแบบ/อนาคต
            if ($age < 0 || $age > 120) {
                return null;
            }

            return $age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * มีอักษรไทย (U+0E00–U+0E7F) อยู่ในสตริงไหม
     */
    protected static function hasThai(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (bool) preg_match('/[\x{0E00}-\x{0E7F}]/u', $value);
    }
}
