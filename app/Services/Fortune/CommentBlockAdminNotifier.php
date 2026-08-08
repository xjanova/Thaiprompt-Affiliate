<?php

namespace App\Services\Fortune;

use App\Models\FortuneCommentLinkBlock;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * แจ้งเตือนแอดมินเรื่อง "คอมเมนต์แปะลิงก์ถูกบล็อก" ทาง Facebook Messenger
 *
 * ทำไม Messenger ไม่ใช่ LINE:
 * LINE OA คิดโควต้าทุก push และ LINE Notify ปิดบริการไปแล้ว (ไม่มี push ฟรีเหลือ)
 * ส่วน Messenger ส่งฟรีไม่จำกัด ติดแค่กรอบ 24 ชม. นับจากที่แอดมินคุยกับเพจครั้งล่าสุด
 *
 * กลไก 2 ชั้น (ตามที่เจ้าของกำหนด — ประหยัดที่สุด):
 * 1. วันไหนมีการบล็อก → ยิงข้อความ "กริ่ง" สั้นๆ **วันละไม่เกิน 1 ครั้ง** ต่อให้บล็อกเพิ่มอีกกี่ราย
 * 2. แอดมินพิมพ์คำสั่งกลับมา → บอทตอบรายละเอียด (ยาวแค่ไหนก็ได้)
 *    การตอบกลับนี้ยัง **ต่ออายุกรอบ 24 ชม.** ให้กริ่งวันถัดไปส่งได้ — วงจรเลี้ยงตัวเอง
 *
 * ⚠️ PSID ของแอดมินต้องผูกก่อนใช้งาน — Facebook ไม่เคยบอกว่า "ใคร" ตอบในนามเพจ
 *    (echo webhook คืน Page ID เสมอ · RAG 2,765 แถวจึงมี admin_user_id = NULL ทั้งหมด)
 *    จึงต้องให้แอดมินทักเพจจากบัญชีส่วนตัวพร้อมรหัสผูกที่ออกจากหน้าแอดมิน
 */
class CommentBlockAdminNotifier
{
    /**
     * คีย์ cache ของรหัสผูก (ออกจากหน้าแอดมิน ใช้ครั้งเดียว)
     */
    public const BIND_CACHE_KEY = 'fortune:admin_notify_bind_code';

    /**
     * อายุรหัสผูก (นาที)
     */
    public const BIND_TTL_MINUTES = 10;

    /**
     * คำสั่งที่แอดมินพิมพ์เพื่อขอดูรายการ (ตอบด้วย reply = ไม่เสียอะไร)
     */
    public const COMMAND_KEYWORDS = ['สแปม', 'spam', 'ลิงก์', 'ลิ้งค์', 'บล็อก', 'บล๊อก'];

    public function __construct(
        protected FacebookWebhookService $fb,
    ) {}

    /**
     * ออกรหัสผูกใหม่ (เรียกจากหน้าแอดมิน)
     *
     * @return string เช่น 'TPADMIN-7K2Q9X'
     */
    public function generateBindCode(): string
    {
        $code = 'TPADMIN-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        Cache::put(self::BIND_CACHE_KEY, $code, now()->addMinutes(self::BIND_TTL_MINUTES));

        return $code;
    }

    /**
     * 🔗 ลองผูก PSID แอดมินจากข้อความที่ทักเข้ามา
     *
     * ปลอดภัยเพราะรหัสสุ่ม + หมดอายุ 10 นาที + ใช้ได้ครั้งเดียว (ลบทันทีที่ใช้)
     * ลูกค้าทั่วไปเดาไม่ได้ และต่อให้เดาได้ก็ต้องตรงกับรหัสที่เพิ่งออกจากหน้าแอดมิน
     *
     * @return string|null ข้อความตอบกลับ (null = ข้อความนี้ไม่ใช่รหัสผูก)
     */
    public function tryBind(string $psid, string $text): ?string
    {
        $text = trim($text);
        if (! preg_match('/^TPADMIN-[A-Z0-9]{6}$/i', $text)) {
            return null;
        }

        $expected = Cache::get(self::BIND_CACHE_KEY);
        if (empty($expected) || strcasecmp($text, $expected) !== 0) {
            return '❌ รหัสผูกไม่ถูกต้องหรือหมดอายุแล้ว — กดออกรหัสใหม่ที่หน้าแอดมิน';
        }

        Cache::forget(self::BIND_CACHE_KEY); // ใช้ครั้งเดียว

        $settings = FortuneTellingSetting::getSettings();
        $settings->admin_notify_psid = $psid;
        $settings->admin_notify_enabled = true;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        Log::info('📩 ผูก PSID แอดมินสำหรับแจ้งเตือนสำเร็จ', ['psid' => $psid]);

        return "✅ ผูกเรียบร้อย!\n\nต่อไปวันไหนมีการบล็อกคอมเมนต์แปะลิงก์ ระบบจะทักมาบอกที่นี่ วันละไม่เกิน 1 ครั้ง\n\nพิมพ์ \"สแปม\" เมื่อไหร่ก็ได้ เพื่อดูรายการล่าสุด";
    }

    /**
     * 💬 ตอบคำสั่งของแอดมิน (ฟรี — ไม่กินโควต้าเหมือน LINE)
     *
     * @return string|null ข้อความตอบกลับ (null = ไม่ใช่คำสั่ง/ไม่ใช่แอดมิน)
     */
    public function tryHandleCommand(string $psid, string $text): ?string
    {
        $settings = FortuneTellingSetting::getSettings();
        $adminPsid = $settings->admin_notify_psid ?? null;

        // ต้องเป็นแอดมินที่ผูกไว้เท่านั้น — ลูกค้าคนอื่นพิมพ์คำเดียวกันต้องไม่โดน
        if (empty($adminPsid) || $psid !== $adminPsid) {
            return null;
        }

        $normalized = mb_strtolower(trim($text));
        $isCommand = false;
        foreach (self::COMMAND_KEYWORDS as $kw) {
            if ($normalized === mb_strtolower($kw)) {
                $isCommand = true;
                break;
            }
        }

        if (! $isCommand) {
            return null;
        }

        return $this->buildSummary();
    }

    /**
     * 🔔 ยิงกริ่งแจ้งเตือน — วันละไม่เกิน 1 ครั้ง
     *
     * ใช้ Cache::add() ซึ่ง atomic → ถ้ามีคอมเมนต์สแปมเข้ามาพร้อมกันหลายอัน
     * จะมีแค่ตัวเดียวที่ได้ lock (กันยิงซ้ำจาก race condition)
     *
     * @return bool true = ยิงจริงในรอบนี้
     */
    public function notifyDailyOnce(): bool
    {
        $settings = FortuneTellingSetting::getSettings();

        if (! ($settings->admin_notify_enabled ?? true)) {
            return false;
        }

        $psid = $settings->admin_notify_psid ?? null;
        if (empty($psid)) {
            Log::info('📩 ข้ามแจ้งเตือนแอดมิน — ยังไม่ได้ผูก PSID (ไปกดผูกที่หน้าคอมเมนต์แปะลิงก์)');

            return false;
        }

        // 🔒 lock รายวัน — atomic, หมดอายุสิ้นวัน
        $key = 'fortune:comment_block_notified:'.now()->format('Y-m-d');
        if (! Cache::add($key, 1, now()->endOfDay())) {
            return false; // วันนี้ยิงไปแล้ว
        }

        // ⚠️ ต้องนับเฉพาะ status=blocked — ไม่งั้นแถวจากการสแกนย้อนหลัง (detect_only)
        //    จะถูกรายงานว่า "บล็อกไปแล้ว" ทั้งที่ไม่มีใครโดนบล็อกสักคน
        $todayCount = FortuneCommentLinkBlock::whereDate('created_at', today())
            ->where('status', 'blocked')
            ->count();
        $pending = FortuneCommentLinkBlock::where('comment_deleted', false)
            ->where('hide_succeeded', false)
            ->count();

        $text = "🚫 วันนี้มีการบล็อกคอมเมนต์แปะลิงก์\n\n"
            ."• วันนี้บล็อกไป {$todayCount} ราย\n"
            ."• คอมเมนต์รอลบทั้งหมด {$pending} รายการ\n\n"
            ."พิมพ์ \"สแปม\" เพื่อดูรายการ + ลิงก์กดไปลบ\n"
            .'(แจ้งวันละครั้งเท่านั้น ถึงจะบล็อกเพิ่มก็ไม่ทักซ้ำ)';

        $ok = false;
        try {
            $ok = $this->fb->sendMessage($psid, $text);
        } catch (\Throwable $e) {
            Log::warning('📩 ส่งแจ้งเตือนแอดมินล้มเหลว: '.$e->getMessage());
        }

        if (! $ok) {
            // ⚠️ ส่งไม่ผ่านมักเพราะเลยกรอบ 24 ชม. — ปลด lock ให้ลองใหม่ได้ในวันเดียวกัน
            Cache::forget($key);
            Log::warning('📩 แจ้งเตือนแอดมินไม่สำเร็จ (น่าจะเลยกรอบ 24 ชม.) — แอดมินต้องทักเพจสักครั้งเพื่อเปิดกรอบใหม่', [
                'psid' => $psid,
                'error' => $this->fb->lastFetchError ?? null,
            ]);
        }

        return $ok;
    }

    /**
     * สร้างข้อความสรุปรายการล่าสุด พร้อมลิงก์กดไปลบ
     */
    public function buildSummary(int $limit = 5): string
    {
        $pending = FortuneCommentLinkBlock::where('comment_deleted', false)
            ->where('hide_succeeded', false)
            ->latest()
            ->limit($limit)
            ->get();

        $totalPending = FortuneCommentLinkBlock::where('comment_deleted', false)
            ->where('hide_succeeded', false)
            ->count();

        // แยก "บล็อกจริง" ออกจาก "เจอจากสแกนย้อนหลัง" — คนละความหมายกันคนละเรื่อง
        $today = FortuneCommentLinkBlock::whereDate('created_at', today())
            ->where('status', 'blocked')
            ->count();
        $detectOnly = FortuneCommentLinkBlock::where('status', 'detect_only')
            ->where('comment_deleted', false)
            ->count();

        if ($pending->isEmpty()) {
            return "✅ ไม่มีคอมเมนต์ค้างให้ลบ\n\nวันนี้บล็อกไป {$today} ราย";
        }

        $lines = ["🔗 คอมเมนต์แปะลิงก์ที่ยังไม่ได้ลบ ({$totalPending} รายการ)", "วันนี้บล็อกไป {$today} ราย"];
        if ($detectOnly > 0) {
            $lines[] = "(ใน {$totalPending} นี้ {$detectOnly} รายการมาจากสแกนย้อนหลัง — ยังไม่ได้บล็อกใคร)";
        }
        $lines[] = '';

        foreach ($pending as $i => $b) {
            $no = $i + 1;
            $name = $b->display_name ?: 'ไม่ทราบชื่อ';
            $lines[] = "{$no}. {$name}";
            $lines[] = '   โดเมน: '.($b->matched_domain ?: '-');
            $lines[] = '   '.($b->permalink ?: '(ไม่มีลิงก์ — ค้นจากชื่อในเพจ)');
            $lines[] = '';
        }

        if ($totalPending > $limit) {
            $lines[] = 'และอีก '.($totalPending - $limit).' รายการ — ดูครบที่หน้าแอดมิน';
        }

        $lines[] = 'ลบเสร็จแล้วกด "ลบแล้ว" ในหน้าแอดมิน จะได้ไม่ค้างในรายการนี้';

        return implode("\n", $lines);
    }
}
