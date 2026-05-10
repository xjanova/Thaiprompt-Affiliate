<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use Illuminate\Console\Command;
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
                            {--days=30 : ย้อนหลังกี่วัน}
                            {--posts=100 : จำนวนโพสสูงสุดที่จะ scan}
                            {--per-post=200 : คอมเม้นต์ต่อโพสสูงสุด}
                            {--execute : ลงมือซ่อน/ลบจริง (default: dry-run)}';

    protected $description = 'สแกนคอมเม้นต์ Facebook ย้อนหลัง — หาลิงค์สแปมและซ่อน/ลบตาม settings';

    public function handle(FacebookWebhookService $service): int
    {
        $settings = FortuneTellingSetting::query()->first();
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
        $perPostLimit = (int) $this->option('per-post');
        $execute = (bool) $this->option('execute');

        $action = $settings->link_comment_action ?? 'hide';
        $whitelist = is_array($settings->link_whitelist_domains ?? null)
            ? $settings->link_whitelist_domains
            : [];
        $defaults = ['thaiprompt.online', 'main.thaiprompt.online', 'm.me', 'lin.ee', 'line.me', 'facebook.com', 'fb.com'];
        $whitelist = array_unique(array_merge($whitelist, $defaults));

        $sinceTs = now()->subDays($days)->timestamp;
        $pageId = $settings->facebook_page_id;

        $this->info("🛡️ Scan คอมเม้นต์เก่า — ย้อน {$days} วัน, สูงสุด {$postsLimit} โพส");
        $this->line("   Action: {$action} | Mode: ".($execute ? '⚠️  EXECUTE (ลงมือจริง)' : '🧪 DRY-RUN (ไม่ทำจริง)'));
        $this->line('   Whitelist: '.implode(', ', $whitelist));
        $this->newLine();

        $posts = $service->listRecentPosts($sinceTs, $postsLimit);
        if (empty($posts)) {
            $this->warn('ไม่พบโพสในช่วงเวลานี้ (หรือ token ขาด pages_read_engagement)');

            return self::SUCCESS;
        }

        $this->info('📜 พบ '.count($posts).' โพส — เริ่มสแกน...');
        $this->newLine();

        $stats = [
            'posts_scanned' => 0,
            'comments_total' => 0,
            'spam_found' => 0,
            'spam_actioned' => 0,
            'spam_failed' => 0,
            'already_hidden' => 0,
        ];

        foreach ($posts as $post) {
            $stats['posts_scanned']++;
            $postId = $post['id'];
            $comments = $service->listCommentsForPost($postId, $perPostLimit);

            if (empty($comments)) {
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
        ]);

        if (! $execute && $stats['spam_found'] > 0) {
            $this->newLine();
            $this->warn('💡 รัน command นี้พร้อม --execute เพื่อ '.$action.' คอมที่พบจริง');
        }

        return self::SUCCESS;
    }
}
