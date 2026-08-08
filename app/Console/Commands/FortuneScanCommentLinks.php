<?php

namespace App\Console\Commands;

use App\Models\FortuneCommentLinkBlock;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneBanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🔎 สแกนคอมเมนต์ย้อนหลังหาลิงก์สแปม → ลงหน้าจัดการ
 *
 * ⭐ จุดสำคัญ: **ไม่ต้องใช้ Facebook permission เลย**
 *   Graph API อ่านคอมเมนต์ไม่ได้ (ขาด pages_read_user_content ติด App Review)
 *   แต่คอมเมนต์ทุกอันที่เคยวิ่งผ่าน webhook เราเก็บไว้เองแล้วในตาราง
 *   `fortune_comment_engagements` (comment_text + comment_id + post_id ครบ)
 *   → สแกนจากของตัวเองได้ทันที ไม่ต้องรอ Meta
 *
 * Usage:
 *   php artisan fortune:scan-comment-links                 # dry-run ดูก่อน
 *   php artisan fortune:scan-comment-links --save          # บันทึกลงหน้าจัดการ (ยังไม่บล็อก)
 *   php artisan fortune:scan-comment-links --save --block  # บันทึก + บล็อกคนโพสต์จริง
 *
 * @tip ค่าเริ่มต้นตั้งใจให้ปลอดภัย — ไม่บันทึก ไม่บล็อก จนกว่าจะสั่งเอง
 *      เพราะการบล็อกย้อนหลังทีเดียวหลายสิบคนเป็นเรื่องกลับยาก
 */
class FortuneScanCommentLinks extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:scan-comment-links
                            {--days=0 : ย้อนหลังกี่วัน (0 = ทั้งหมด)}
                            {--limit=1000 : จำนวนคอมเมนต์สูงสุดที่ประมวลผล}
                            {--save : บันทึกผลลงตาราง fortune_comment_link_blocks (หน้าจัดการ)}
                            {--block : บล็อกคนโพสต์บนเพจจริง + แบนระดับบอท (ต้องใช้คู่กับ --save)}';

    /**
     * @var string
     */
    protected $description = 'สแกนคอมเมนต์ย้อนหลังจาก fortune_comment_engagements หาลิงก์สแปม → ลงหน้าจัดการ';

    /**
     * TLD prefilter ฝั่ง SQL — คัด 430k แถวให้เหลือหลักสิบก่อนเข้า PHP
     *
     * ⚠️ เป็นแค่ตะแกรงหยาบ ตัวตัดสินจริงคือ firstExternalDomain() ใน PHP
     *    (ตัวนี้ยังไม่กรอง whitelist)
     */
    protected function sqlPrefilter(): string
    {
        return '(https?://|www[.]|[a-z0-9-]+[.]('
            .FacebookWebhookService::LINK_TLD_PATTERN
            .')([/]|$|[^a-z0-9]))';
    }

    /**
     * รันคำสั่ง
     */
    public function handle(FacebookWebhookService $fb, FortuneBanService $banService): int
    {
        $days = (int) $this->option('days');
        $limit = max(1, (int) $this->option('limit'));
        $save = (bool) $this->option('save');
        $block = (bool) $this->option('block');

        if ($block && ! $save) {
            $this->error('--block ต้องใช้คู่กับ --save');

            return self::FAILURE;
        }

        $settings = FortuneTellingSetting::getSettings();
        $pageId = $settings->facebook_page_id ?? null;

        $whitelist = is_array($settings->link_whitelist_domains ?? null)
            ? $settings->link_whitelist_domains
            : [];
        $defaults = ['thaiprompt.online', 'main.thaiprompt.online', 'm.me', 'lin.ee', 'line.me', 'facebook.com', 'fb.com'];
        $whitelist = array_unique(array_merge($whitelist, $defaults));

        $this->info('🔎 สแกนคอมเมนต์ย้อนหลัง — '.($days > 0 ? "{$days} วัน" : 'ทั้งหมด')." / สูงสุด {$limit} รายการ");
        $this->line('   โหมด: '.($block ? '⚠️  บันทึก + บล็อกจริง' : ($save ? '💾 บันทึกอย่างเดียว (ไม่บล็อก)' : '🧪 DRY-RUN')));
        $this->line('   Whitelist: '.implode(', ', $whitelist));
        $this->newLine();

        $query = DB::table('fortune_comment_engagements')
            ->whereNotNull('comment_text')
            ->where('comment_text', '!=', '')
            ->whereRaw('LOWER(comment_text) REGEXP ?', [$this->sqlPrefilter()]);

        if ($days > 0) {
            $query->where('engaged_at', '>=', now()->subDays($days));
        }

        $rows = $query->orderByDesc('engaged_at')
            ->limit($limit)
            ->get(['facebook_user_id', 'facebook_post_id', 'facebook_comment_id', 'comment_text', 'user_profile', 'engaged_at']);

        $this->info('ผ่านตะแกรง SQL: '.$rows->count().' รายการ — กรองละเอียดต่อ...');
        $this->newLine();

        $stats = ['scanned' => 0, 'whitelisted' => 0, 'hit' => 0, 'saved' => 0, 'dup' => 0, 'blocked' => 0, 'block_failed' => 0];
        $table = [];
        $seenPsid = [];

        foreach ($rows as $r) {
            $stats['scanned']++;

            $domain = $fb->firstExternalDomain((string) $r->comment_text, $whitelist);
            if ($domain === null) {
                $stats['whitelisted']++;

                continue;
            }

            $stats['hit']++;
            $commentId = (string) $r->facebook_comment_id;
            $psid = (string) $r->facebook_user_id;
            $name = $this->extractName($r->user_profile);

            $table[] = [
                substr((string) $r->engaged_at, 0, 10),
                mb_substr($name ?: '-', 0, 16),
                $domain,
                mb_substr(preg_replace('/\s+/', ' ', (string) $r->comment_text), 0, 46),
            ];

            if (! $save) {
                continue;
            }

            // idempotent — สแกนซ้ำได้ ไม่สร้างแถวซ้ำ
            if (FortuneCommentLinkBlock::where('comment_id', $commentId)->exists()) {
                $stats['dup']++;

                continue;
            }

            $blockedOk = false;
            $blockError = null;
            $botBanned = false;

            // บล็อกครั้งเดียวต่อคน ถึงจะเจอหลายคอมเมนต์
            if ($block && ! isset($seenPsid[$psid])) {
                $seenPsid[$psid] = true;
                try {
                    $blockedOk = $fb->blockPageUser($psid);
                    if (! $blockedOk) {
                        $blockError = $fb->lastFetchError ?? 'unknown';
                        $stats['block_failed']++;
                    } else {
                        $stats['blocked']++;
                    }
                } catch (\Throwable $e) {
                    $blockError = $e->getMessage();
                    $stats['block_failed']++;
                }

                try {
                    $banService->ban('facebook', $psid, null, 'auto (ย้อนหลัง): โพสต์ลิงก์ภายนอกในคอมเมนต์ ('.$domain.')', null, $name);
                    $botBanned = true;
                } catch (\Throwable $e) {
                    Log::warning('scan-comment-links: ban ล้ม: '.$e->getMessage());
                }
            }

            FortuneCommentLinkBlock::create([
                'platform' => 'facebook',
                'platform_user_id' => $psid,
                'display_name' => $name,
                'comment_id' => $commentId,
                'post_id' => $r->facebook_post_id,
                'permalink' => FortuneCommentLinkBlock::buildPermalink($r->facebook_post_id, $commentId, $pageId),
                'message' => mb_substr((string) $r->comment_text, 0, 2000),
                'matched_domain' => $domain,
                'detected_from' => 'text',
                'page_blocked' => $blockedOk,
                'block_error' => $blockError ? mb_substr($blockError, 0, 500) : null,
                'bot_banned' => $botBanned,
                'hide_succeeded' => false,
                // ไม่ได้บล็อก = detect_only เพื่อให้หน้าจัดการแยกออกจากเคสที่บอทจัดการเองแล้ว
                'status' => $block ? 'blocked' : 'detect_only',
                'is_read' => false,
                'blocked_at' => $block ? now() : null,
            ]);
            $stats['saved']++;
        }

        if (! empty($table)) {
            $this->table(['วันที่', 'ชื่อ', 'โดเมน', 'ข้อความ'], $table);
        }

        $this->newLine();
        $this->info('📊 สรุปผล');
        $this->table(['Metric', 'Count'], [
            ['ผ่านตะแกรง SQL', $stats['scanned']],
            ['เป็น whitelist (ข้าม)', $stats['whitelisted']],
            ['ลิงก์ภายนอกจริง', $stats['hit']],
            ['บันทึกใหม่', $stats['saved']],
            ['มีอยู่แล้ว (ข้าม)', $stats['dup']],
            ['บล็อกสำเร็จ', $stats['blocked']],
            ['บล็อกล้มเหลว', $stats['block_failed']],
        ]);

        Log::info('FortuneScanCommentLinks: scan', $stats + ['save' => $save, 'block' => $block, 'days' => $days]);

        if (! $save && $stats['hit'] > 0) {
            $this->newLine();
            $this->warn('🧪 DRY-RUN — เพิ่ม --save เพื่อลงหน้าจัดการ (ยังไม่บล็อก) หรือ --save --block เพื่อบล็อกด้วย');
        }

        return self::SUCCESS;
    }

    /**
     * ดึงชื่อจากคอลัมน์ user_profile (เก็บเป็น JSON หรือข้อความธรรมดาแล้วแต่ยุค)
     */
    protected function extractName(mixed $profile): ?string
    {
        if (empty($profile)) {
            return null;
        }

        if (is_string($profile)) {
            $decoded = json_decode($profile, true);
            if (is_array($decoded)) {
                $profile = $decoded;
            } else {
                return mb_substr($profile, 0, 200);
            }
        }

        if (is_array($profile)) {
            $name = $profile['name']
                ?? trim(($profile['first_name'] ?? '').' '.($profile['last_name'] ?? ''));

            return $name !== '' ? mb_substr($name, 0, 200) : null;
        }

        return null;
    }
}
