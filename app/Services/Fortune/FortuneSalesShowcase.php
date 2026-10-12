<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🖼️ (2026-10-12) ชุดตัวอย่าง "สิ่งที่จะได้รับ" ตอนปิดการขาย — owner สั่ง
 *   ภาพกราฟชีวิต · ภาพไพ่ 10 ใบ · ภาพดวงดาว + "ที่นี่ไม่ได้ยกเมฆ ทำนายตามศาสตร์ ใช้ไพ่ทาโรต์เป็นตัวยืน"
 *
 * - ส่งตอนขึ้นการ์ดเลือกแพคเกจ (tier_choice) และตอนลูกค้าถามราคา (pricing_menu) เท่านั้น
 * - ลูกค้าเก่า = เคยดูดวงจ่ายสำเร็จ ≥ 1 บิล (is_paid) → ไม่ส่ง
 * - คนเดิมได้ไม่เกิน 1 ครั้ง / 7 วัน — เมนูขึ้นใหม่ทุกครั้งที่พิมพ์ "ดูดวง"
 * - ภาพเป็นไฟล์ตายตัวใน public/images/fortune/showcase วาดจากเครื่องวาดตัวจริง ข้อมูลสมมติ "ภาพตัวอย่าง"
 *   (ลบไฟล์ออก = ไม่ส่งภาพนั้น · ไม่มีภาพเหลือเลย = ไม่ส่งชุดนี้)
 */
final class FortuneSalesShowcase
{
    private const DIR = 'images/fortune/showcase';

    private const COOLDOWN_DAYS = 7;

    /** ส่งชุดตัวอย่างให้คนนี้ตอนนี้ได้ไหม — true = จองสิทธิ์แล้ว ต้องส่งเลย */
    public static function claim(?string $userId): bool
    {
        if ($userId === null || $userId === '') {
            return false;
        }

        try {
            // เช็กลูกค้าเก่าก่อนจองสิทธิ์ — คนที่จ่ายแล้วไม่กินสิทธิ์ 7 วัน
            if (self::hasPaidBefore($userId)) {
                return false;
            }

            return Cache::add('fortune:sales_showcase:'.$userId, 1, now()->addDays(self::COOLDOWN_DAYS));
        } catch (\Throwable $e) {
            Log::warning('FortuneSalesShowcase: เช็กสิทธิ์ไม่สำเร็จ — ข้ามชุดตัวอย่าง', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** เคยดูดวงจ่ายสำเร็จอย่างน้อย 1 บิล (นับบิลที่ถูกลบแบบ soft delete ด้วย) */
    public static function hasPaidBefore(string $userId): bool
    {
        return FortuneReading::withTrashed()
            ->where(fn ($q) => $q->where('facebook_user_id', $userId)->orWhere('platform_user_id', $userId))
            ->where('is_paid', true)
            ->exists();
    }

    /**
     * @return list<string> https URL ตามลำดับ กราฟชีวิต · ไพ่ 10 ใบ (เฉพาะเมื่อเปิดแพคเกจ Celtic) · ภาพดวงดาว
     */
    public static function imageUrls(bool $celticEnabled): array
    {
        $urls = [];
        foreach ($celticEnabled ? ['lifegraph', 'celtic', 'chart'] : ['lifegraph', 'chart'] as $name) {
            $rel = self::DIR.'/'.$name.'.jpg';
            $full = public_path($rel);
            if (! is_file($full)) {
                continue;
            }
            $url = asset($rel);
            if (str_starts_with($url, 'http://')) {
                $url = 'https://'.substr($url, 7);
            }
            if (str_starts_with($url, 'https://')) {
                $urls[] = $url.'?v='.filemtime($full); // เปลี่ยนภาพ = LINE/FB ดึงใหม่
            }
        }

        return $urls;
    }

    /** ข้อความตามหลังภาพ — บอกตรงว่าแพคเกจไหนได้อะไร (ไพ่ 10 ใบมีเฉพาะ Celtic) */
    public static function text(bool $deepEnabled, bool $celticEnabled, mixed $deepPrice = null, mixed $celticPrice = null): string
    {
        $lines = ['👆 ตัวอย่างภาพที่จะได้รับจริงหลังดูดวงค่ะ (ภาพนี้ใช้ข้อมูลสมมติ)', '📈 กราฟชีวิต 0–100 ปี ตามตำรามหาทักษา'];
        if ($celticEnabled) {
            $lines[] = $deepEnabled
                ? '🃏 ภาพไพ่ทาโรต์ 10 ใบ (แพคเกจ '.self::price($celticPrice, '99').' บาท · แพคเกจ '.self::price($deepPrice, '39').' บาท ได้ไพ่ 1 ใบ)'
                : '🃏 ภาพไพ่ทาโรต์ 10 ใบ';
        }
        $lines[] = '🪐 ภาพดวงดาววันเกิด';
        $lines[] = 'กราฟชีวิตกับภาพดวงดาวทำจากวันเกิดของเจ้าชะตาเองค่ะ';

        return implode("\n", $lines)."\n\n"
            .'ที่นี่ไม่ได้ยกเมฆนะคะ แม่หมอทำนายตามศาสตร์การดูดวง และใช้ไพ่ทาโรต์เป็นตัวยืน ถ้าคิดว่าเหมือนที่อื่น ไม่ใช่แบบนั้นแน่นอนค่ะ 🙏';
    }

    private static function price(mixed $p, string $fallback): string
    {
        $n = is_numeric($p) ? (float) $p : 0.0;

        return $n > 0 ? rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.') : $fallback;
    }
}
