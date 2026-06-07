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
     * เดาว่า user คนนี้ "เป็นคนต่างชาติ" ไหม จากชื่อ + ข้อความ
     *
     * วิธีตรวจ (basis):
     *   - 'script'   (แนะนำ) นับเฉพาะสคริปต์ต่างภาษาชัดเจน (ลาว/จีน/ญี่ปุ่น/เกาหลี/อาหรับ/
     *                ซีริลลิก/เทวนาครี ฯลฯ) = ต่างชาติ ; ชื่ออังกฤษล้วน = "ไม่ชัด" → ไม่ถือว่าต่างชาติ
     *                (กันบล็อกคนไทยที่ตั้งชื่อ FB เป็นอังกฤษ)
     *   - 'no_thai'  ไม่มีอักษรไทยเลย (อังกฤษ/ลาว/จีน ฯลฯ) = ต่างชาติ — เข้มสุด
     *   - 'lao_only' เฉพาะคนลาว (ใช้ FortuneLocaleService ที่มีอยู่)
     *
     * กฎร่วม: ถ้าพบ "อักษรไทย" ในชื่อหรือข้อความ → ถือเป็นคนไทยเสมอ (target audience) ไม่ใช่ต่างชาติ
     */
    public static function isForeigner(
        string $platform,
        string $userId,
        ?string $name,
        ?string $text,
        string $basis = 'script',
    ): bool {
        try {
            // มีอักษรไทยที่ไหนสักที่ → คนไทย (ไม่ใช่ต่างชาติ)
            if (self::hasThai($name) || self::hasThai($text)) {
                return false;
            }

            $blob = trim((string) $name.' '.(string) $text);

            if ($basis === 'lao_only') {
                $lo = FortuneLocaleService::LOCALE_LO;
                if (FortuneLocaleService::detectFromText($text) === $lo) {
                    return true;
                }
                if (FortuneLocaleService::detectFromName($name) === $lo) {
                    return true;
                }
                if (FortuneLocaleService::getStored($platform, $userId) === $lo) {
                    return true;
                }

                return false;
            }

            if ($basis === 'no_thai') {
                // ต้องมีตัวอักษร (letter) อย่างน้อย 1 ตัว — กัน emoji/ตัวเลข/ว่าง → "ไม่ชัด"
                if ($blob === '' || ! preg_match('/\p{L}/u', $blob)) {
                    return false;
                }

                // ไม่มีอักษรไทย (เช็คด้านบนแล้ว) + มีตัวอักษร → ต่างชาติ
                return true;
            }

            // default 'script' — เฉพาะสคริปต์ต่างภาษาชัดเจน (ไม่นับ Latin/อังกฤษ)
            if ($blob === '') {
                return false;
            }

            $foreignScript = '/['
                .'\x{0E80}-\x{0EFF}'  // Lao
                .'\x{4E00}-\x{9FFF}'  // CJK (จีน)
                .'\x{3040}-\x{30FF}'  // Hiragana + Katakana (ญี่ปุ่น)
                .'\x{AC00}-\x{D7AF}'  // Hangul (เกาหลี)
                .'\x{0600}-\x{06FF}'  // Arabic
                .'\x{0400}-\x{04FF}'  // Cyrillic
                .'\x{0900}-\x{097F}'  // Devanagari (ฮินดี)
                .'\x{0980}-\x{09FF}'  // Bengali
                .'\x{1000}-\x{109F}'  // Myanmar (พม่า)
                .'\x{1780}-\x{17FF}'  // Khmer (เขมร)
                .']/u';

            return (bool) preg_match($foreignScript, $blob);
        } catch (\Throwable $e) {
            return false; // fail-open — ไม่ถือว่าต่างชาติเมื่อ detect ล้ม
        }
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
