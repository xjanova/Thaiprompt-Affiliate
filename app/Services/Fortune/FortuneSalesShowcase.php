<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🖼️ (2026-10-12) ชุดตัวอย่าง "สิ่งที่จะได้รับ" ตอนปิดการขาย — owner สั่ง
 *   ภาพกราฟชีวิต · ภาพไพ่ 10 ใบ · ภาพดวงดาว + "ที่นี่ไม่ได้ยกเมฆ ทำนายตามศาสตร์ ใช้ไพ่ทาโรต์เป็นตัวยืน"
 *
 * - ส่งตอนขึ้นการ์ดเลือกแพคเกจ (tier_choice) · ตอนลูกค้าถามราคา (pricing_menu)
 *   · ลูกค้านิ่งนาน (FortuneFlowNudge — แทนกล่อง "เลือกได้เลยนะคะ" ที่ยกเลิกไปแล้ว)
 *   · เปิดบิลแล้วไม่โอน (SendBillReminderJob) · กำลังจะยกเลิก (cancel_dialog_ask — แทนคำถาม "ยกเลิกเพราะอะไร")
 *   จังหวะไหนมาก่อนได้จังหวะนั้น (owner 2026-10-12)
 * - ลูกค้าเก่า = เคยดูดวงจ่ายสำเร็จ ≥ 1 บิล (is_paid) → ไม่ส่ง
 * - คนเดิมได้ครั้งเดียวตลอดไป (owner: "ต้องยังไม่เคยส่งมาก่อน") — จดในตาราง fortune_sales_showcase_sends
 *   ⚠️ ห้ามย้ายกลับไป cache: deploy.sh รัน cache:clear ทุกรอบ ธงหาย = ลูกค้าได้ซ้ำหลัง deploy
 * - ภาพเป็นไฟล์ตายตัวใน public/images/fortune/showcase วาดจากเครื่องวาดตัวจริง ข้อมูลสมมติ "ภาพตัวอย่าง"
 *   (ลบไฟล์ออก = ไม่ส่งภาพนั้น · ไม่มีภาพเหลือเลย = ไม่ส่งชุดนี้)
 */
final class FortuneSalesShowcase
{
    private const DIR = 'images/fortune/showcase';

    private const TABLE = 'fortune_sales_showcase_sends';

    /**
     * ส่งชุดตัวอย่างให้คนนี้ตอนนี้ได้ไหม — true = จองสิทธิ์แล้ว ต้องส่งเลย
     *
     * @param  string  $trigger  menu | cancel (จดไว้ดูว่าได้จากจังหวะไหน)
     */
    public static function claim(?string $userId, string $trigger = 'menu'): bool
    {
        if ($userId === null || $userId === '') {
            return false;
        }

        try {
            // เช็กลูกค้าเก่าก่อนจองสิทธิ์ — คนที่จ่ายแล้วไม่ต้องจด
            if (self::hasPaidBefore($userId)) {
                return false;
            }

            // unique(platform_user_id) → แถวแรกเท่านั้นที่ insert ได้ = ส่งครั้งเดียวจริง แม้สองโปรเซสชนกัน
            return DB::table(self::TABLE)->insertOrIgnore([
                'platform_user_id' => mb_substr($userId, 0, 191),
                'trigger' => $trigger,
                'created_at' => now(),
            ]) === 1;
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
        $lines = ['👆 นี่คือสิ่งที่จะได้รับจริงหลังดูดวง ไม่ควรพลาดเลยค่ะ (ภาพตัวอย่างใช้ข้อมูลสมมติ)', '📈 กราฟชีวิต 0–100 ปี ตามตำรามหาทักษา'];
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

    /**
     * จองสิทธิ์ + เตรียมชุดตัวอย่างจากการตั้งค่า (แพคเกจที่เปิด · ราคา) — null = คนนี้ไม่ได้ (เคยได้แล้ว/ลูกค้าเก่า/ไม่มีภาพ)
     *
     * @param  string  $trigger  idle (เงียบที่เมนู/กล่องกติกา) · bill (เปิดบิลไม่โอน) · cancel (กำลังจะยกเลิก)
     * @return array{urls: list<string>, text: string}|null
     */
    public static function prepare(string $userId, string $trigger, \App\Models\FortuneTellingSetting $settings): ?array
    {
        try {
            $celtic = (bool) ($settings->enable_celtic_cross ?? false);
            $urls = self::imageUrls($celtic);
            if ($urls === [] || ! self::claim($userId, $trigger)) {
                return null;
            }

            return [
                'urls' => $urls,
                'text' => self::text($settings->isDeepReadingEnabled(), $celtic, $settings->deep_reading_price ?? null, $settings->celtic_cross_price ?? null),
            ];
        } catch (\Throwable $e) {
            Log::warning('FortuneSalesShowcase: เตรียมชุดตัวอย่างไม่สำเร็จ', ['user_id' => $userId, 'trigger' => $trigger, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ส่งชุดที่ prepare() ได้มา — ภาพก่อน แล้วข้อความ "👆 …" (+ $after ต่อท้ายกล่องเดียวกัน)
     *   $before = ข้อความที่ต้องออกก่อนภาพ (เช่นข้อความทวงบิล) · LINE รวมเป็น reply/push ครั้งเดียว ≤ 5 กล่อง
     *
     * @param  array{urls: list<string>, text: string}  $show
     * @param  array  $options  FB/Telegram: $extra ของ ChannelManager (เพจต้นทาง ฯลฯ) — ต้องส่งต่อ ไม่งั้นออกผิดเพจ
     */
    public static function send(object $service, string $userId, array $show, ?string $before = null, ?string $after = null, ?string $replyToken = null, array $options = []): bool
    {
        $text = $after !== null && $after !== '' ? $show['text']."\n\n".$after : $show['text'];
        try {
            if ($service instanceof \App\Services\LineFortuneService) {
                $msgs = $before !== null && $before !== '' ? [$service->buildTextObject($before)] : [];
                foreach (array_slice($show['urls'], 0, 3) as $u) {
                    $msgs[] = $service->buildImageObject($u);
                }
                $msgs[] = $service->buildTextObject($text);

                return $service->sendMessagesWithReplyFallback($userId, $msgs, $replyToken);
            }
            if ($service instanceof \App\Contracts\FortuneMessengerSender) {
                if ($before !== null && $before !== '') {
                    $service->sendMessage($userId, $before, $options);
                }
                foreach ($show['urls'] as $u) {
                    $service->sendImage($userId, $u, null, $options);
                    usleep(500000);
                }

                return $service->sendMessage($userId, $text, $options);
            }
        } catch (\Throwable $e) {
            Log::warning('FortuneSalesShowcase: ส่งชุดตัวอย่างไม่สำเร็จ', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }

        return false;
    }

    private static function price(mixed $p, string $fallback): string
    {
        $n = is_numeric($p) ? (float) $p : 0.0;

        return $n > 0 ? rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.') : $fallback;
    }
}
