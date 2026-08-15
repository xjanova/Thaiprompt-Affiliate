<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ สแกนคอมเม้นต์ Facebook Page ย้อนหลัง — หาลิงค์สแปม → ซ่อน/ลบ
 *
 * Usage:
 *   php artisan fortune:scan-old-comments               # default: 30 วันย้อน, dry-run
 *   php artisan fortune:scan-old-comments --days=90 --execute
 *   php artisan fortune:scan-old-comments --posts=200 --execute
 *
 * Action ตามค่า settings:
 *   - link_comment_action='hide'  → POST {comment_id} is_hidden=true
 *   - link_comment_action='delete'→ DELETE {comment_id}
 *
 * Whitelist auto: thaiprompt.online, m.me, lin.ee, line.me, facebook.com, fb.com
 *   + admin custom จาก link_whitelist_domains
 */
class FortuneScanOldComments extends Command
{
    protected $signature = 'fortune:scan-old-comments
                            {--days=30 : ย้อนหลังกี่วัน (override โดย --since-last)}
                            {--posts=100 : จำนวนโพสสูงสุดที่จะ scan}
                            {--reels=100 : จำนวน Reels สูงสุดที่จะ scan (สแปมเยอะใน Reels ดัง)}
                            {--per-post=500 : คอมเม้นต์ต่อโพส/Reel สูงสุด (override โดย --all)}
                            {--all : สแกนทุกคอมเม้นต์ ไม่จำกัด (per-post=unlimited) — สำหรับโพสไวรัลคอมหมื่นๆ}
                            {--since-last : สแกนเฉพาะที่ใหม่กว่าครั้งก่อน (incremental — ใช้กับ schedule)}
                            {--skip-reels : ข้าม Reels (scan เฉพาะโพสปกติ)}
                            {--execute : ลงมือซ่อน/ลบจริง (default: dry-run)}';

    protected $description = 'สแกนคอมเม้นต์ Facebook ย้อนหลัง (โพส + Reels) — หาลิงค์สแปมและซ่อน/ลบ';

    use \App\Console\Commands\Concerns\RunsForEachFortunePage;

    /**
     * 🏬 (2026-08-15) สแกนคอมเมนต์ให้ครบทุกสาขา
     *
     * ไม่ต้องใช้สวิตช์ auto_post_enabled — นี่คือ "ดูแลคอมเมนต์ในเพจตัวเอง"
     * ไม่ใช่การโพสของใหม่ลงหน้าเพจ สาขาที่เปิดบอทควรได้รับการดูแลเสมอ
     *
     * แต่ละสาขาสแกนโพสของตัวเอง = คนละชุดคอมเมนต์ จึงไม่มีปัญหาทำงานซ้ำ
     */
    public function handle(): int
    {
        $stats = $this->forEachActiveFortunePage(
            'facebook',
            fn () => $this->scanForCurrentPage(app(FacebookWebhookService::class)) === self::SUCCESS
        );

        if ($stats['ran'] > 1) {
            $this->info("🏬 รวม {$stats['ran']} สาขา — สำเร็จ {$stats['ok']} · ล้มเหลว {$stats['failed']}");
        }

        return $stats['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * สแกนให้สาขาที่ context ชี้อยู่ตอนนี้ (เนื้อในเดิม ไม่แตะ)
     */
    protected function scanForCurrentPage(FacebookWebhookService $service): int
    {
        // 🏬 ⚠️ ห้ามใช้ ::query()->first() — นั่นคือแถวกลาง ไม่รู้จักสาขา
        //    จะกลายเป็นทุกสาขาไปสแกนโพสของเพจหลักซ้ำๆ ด้วย token เดียวกัน
        \App\Services\Fortune\FortunePageContext::flushMemo();
        $settings = FortuneTellingSetting::getSettings();
        if (! $settings) {
            $this->error('ไม่พบ FortuneTellingSetting');

            return self::FAILURE;
        }

        if (empty($settings->facebook_page_id) || empty($settings->facebook_page_token)) {
            $this->error('ไม่พบ Facebook page_id หรือ page_token ใน settings');

            return self::FAILURE;
        }

        $days = (int) $this->option('days');
        $postsLimit = (int) $this->option('posts');
        $reelsLimit = (int) $this->option('reels');
        $perPostLimit = $this->option('all') ? PHP_INT_MAX : (int) $this->option('per-post');
        $skipReels = (bool) $this->option('skip-reels');
        $execute = (bool) $this->option('execute');
        $sinceLast = (bool) $this->option('since-last');

        // 📌 Incremental mode — ใช้ timestamp ครั้งก่อน + กันเริ่มต้นตั้งแต่ epoch
        // 🏬 (2026-08-15) ⚠️ ต้องแยกกุญแจต่อสาขา
        //    ใช้กุญแจเดียวกันทุกสาขา = สาขาแรกสแกนเสร็จแล้วเลื่อน timestamp ไปข้างหน้า
        //    สาขาที่เหลือจะข้ามคอมเมนต์ของตัวเองทั้งหมดแบบเงียบๆ
        //    (บทเรียนเดียวกับ state ที่ผูกกับ token — ต้องแยกกุญแจต่อเพจเสมอ)
        $scanPageId = \App\Services\Fortune\FortunePageContext::currentId();
        $cacheKey = 'fortune_link_scan_last_ts'.($scanPageId ? ':'.$scanPageId : '');
        if ($sinceLast) {
            $lastTs = (int) Cache::get($cacheKey, 0);
            if ($lastTs > 0) {
                // ถอย 10 นาทีกัน edge case (คอมที่ post ตอน scan ครั้งก่อนกำลังจะเสร็จ)
                $sinceTs = max($lastTs - 600, now()->subDays($days)->timestamp);
            } else {
                // ยังไม่เคยรัน → ใช้ --days ปกติ
                $sinceTs = now()->subDays($days)->timestamp;
            }
        } else {
            $sinceTs = now()->subDays($days)->timestamp;
        }

        $action = $settings->link_comment_action ?? 'hide';
        $whitelist = is_array($settings->link_whitelist_domains ?? null)
            ? $settings->link_whitelist_domains
            : [];
        $defaults = ['thaiprompt.online', 'main.thaiprompt.online', 'm.me', 'lin.ee', 'line.me', 'facebook.com', 'fb.com'];
        $whitelist = array_unique(array_merge($whitelist, $defaults));

        $pageId = $settings->facebook_page_id;
        $sinceLabel = $sinceLast
            ? 'incremental (ตั้งแต่ '.date('Y-m-d H:i', $sinceTs).')'
            : "{$days} วันย้อนหลัง";

        $reelsTarget = $skipReels ? 0 : $reelsLimit;
        $perPostLabel = $perPostLimit === PHP_INT_MAX ? 'unlimited' : (string) $perPostLimit;
        $this->info("🛡️ Scan คอมเม้นต์เก่า — {$sinceLabel}, สูงสุด {$postsLimit} โพส + {$reelsTarget} Reels (per-post: {$perPostLabel})");
        $this->line("   Action: {$action} | Mode: ".($execute ? '⚠️  EXECUTE (ลงมือจริง)' : '🧪 DRY-RUN (ไม่ทำจริง)'));
        $this->line('   Whitelist: '.implode(', ', $whitelist));
        $this->newLine();

        $posts = $service->listRecentPosts($sinceTs, $postsLimit);
        $reels = $skipReels ? [] : $service->listRecentReels($sinceTs, $reelsLimit);

        // Merge + dedupe ตาม id (Reels บางอันอาจซ้อนใน /published_posts)
        $merged = [];
        foreach (array_merge($posts, $reels) as $row) {
            $id = $row['id'] ?? null;
            if ($id && ! isset($merged[$id])) {
                $merged[$id] = $row;
            }
        }
        $posts = array_values($merged);

        if (empty($posts)) {
            $this->warn('ไม่พบโพส/Reels ในช่วงเวลานี้');
            if ($service->lastFetchError) {
                $this->newLine();
                $this->error('🚨 Graph API error:');
                $this->line('   '.$service->lastFetchError);
                $this->newLine();
                $this->line('💡 เช็คเพิ่มเติม:');
                $this->line('   • Page Access Token หมดอายุ → ต่ออายุที่ Admin → Fortune → Settings');
                $this->line('   • Token ไม่มี scope pages_read_engagement → re-grant ที่ Facebook Login');
                $this->line('   • Page ID ผิด → ตรวจ FortuneTellingSetting.facebook_page_id');
            } else {
                $this->line('💡 เพจอาจไม่มีโพส/Reels ในช่วงเวลานี้ ลองขยาย --days');
            }

            return self::SUCCESS;
        }

        $this->info('📜 พบ '.count($posts).' โพส/Reels (รวม unique) — เริ่มสแกน...');
        $this->newLine();

        $stats = [
            'posts_scanned' => 0,
            'comments_total' => 0,
            'spam_found' => 0,
            'spam_actioned' => 0,
            'spam_failed' => 0,
            'already_hidden' => 0,
        ];

        $firstError = null;
        $emptyPostCount = 0;
        foreach ($posts as $post) {
            $stats['posts_scanned']++;
            $postId = $post['id'];
            $comments = $service->listCommentsForPost($postId, $perPostLimit);

            if (empty($comments)) {
                if ($service->lastFetchError && $firstError === null) {
                    $firstError = $service->lastFetchError;
                }
                $emptyPostCount++;

                continue;
            }

            $stats['comments_total'] += count($comments);

            foreach ($comments as $comment) {
                $commentId = $comment['id'] ?? null;
                $message = $comment['message'] ?? '';
                $fromId = $comment['from']['id'] ?? null;
                $isHidden = (bool) ($comment['is_hidden'] ?? false);

                // ข้ามคอมจากเพจเอง / ไม่มี id
                if (empty($commentId) || $fromId === $pageId) {
                    continue;
                }

                // ข้ามถ้าซ่อนอยู่แล้ว
                if ($isHidden) {
                    $stats['already_hidden']++;

                    continue;
                }

                if (! $service->containsExternalLink($message, $whitelist)) {
                    continue;
                }

                $stats['spam_found']++;
                $preview = mb_substr(str_replace(["\r", "\n"], ' ', $message), 0, 80);

                if (! $execute) {
                    $this->line("  [DRY] {$commentId} — {$preview}");

                    continue;
                }

                $ok = $action === 'delete'
                    ? $service->deleteComment($commentId)
                    : $service->hideComment($commentId);

                if ($ok) {
                    $stats['spam_actioned']++;
                    $this->info("  ✅ {$action} {$commentId} — {$preview}");
                } else {
                    $stats['spam_failed']++;
                    $this->error("  ❌ FAIL {$commentId} — {$preview}");
                }
            }
        }

        // ⚠️ ถ้าทุกโพสคอม=0 ทั้งที่ user รู้ว่ามีคอม → น่าจะ token/scope problem
        if ($stats['comments_total'] === 0 && $stats['posts_scanned'] > 0 && $firstError) {
            $this->newLine();
            $this->error('🚨 ดึง comments ไม่ได้สักโพส — Graph API error:');
            $this->line('   '.$firstError);
            $this->newLine();
            $this->line('💡 แนวโน้ม: Page Token ขาด scope pages_read_engagement');
            $this->line('   วิธีแก้: regenerate token ผ่าน /admin → Fortune → Settings → Facebook OAuth');
            $this->line('   หรือใช้ Graph Explorer: https://developers.facebook.com/tools/explorer/');
        }

        $this->newLine();
        $this->info('📊 สรุปผล:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['โพสที่สแกน', $stats['posts_scanned']],
                ['คอมเม้นต์ทั้งหมด', $stats['comments_total']],
                ['ลิงค์สแปมที่พบ', $stats['spam_found']],
                ['ที่ซ่อนอยู่แล้ว (ข้าม)', $stats['already_hidden']],
                [$execute ? "{$action} สำเร็จ" : 'จะ '.$action.' (dry-run)', $stats['spam_actioned']],
                [$execute ? "{$action} ล้มเหลว" : '-', $stats['spam_failed']],
            ]
        );

        Log::info('Fortune scan-old-comments เสร็จสิ้น', $stats + [
            'days' => $days,
            'execute' => $execute,
            'action' => $action,
            'since_last' => $sinceLast,
        ]);

        // 💾 บันทึก timestamp สำหรับ incremental ครั้งถัดไป (เฉพาะเมื่อ execute หรือ since-last)
        if ($execute || $sinceLast) {
            Cache::put($cacheKey, now()->timestamp, now()->addYear());
        }

        if (! $execute && $stats['spam_found'] > 0) {
            $this->newLine();
            $this->warn('💡 รัน command นี้พร้อม --execute เพื่อ '.$action.' คอมที่พบจริง');
        }

        return self::SUCCESS;
    }
}
