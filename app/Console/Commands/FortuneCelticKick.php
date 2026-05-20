<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 🚨 Celtic Cross Kick — กระตุ้นบอทส่งไพ่ใบล่าสุดที่ pick ไว้แล้วให้ลูกค้า
 *
 * เคสที่ใช้: pickNextCard เพิ่ม picked count แล้ว แต่ push ไม่สำเร็จ
 *           → DB มี card N แต่ลูกค้าไม่เห็น → คนต่อ rerun fortune:celtic-recover
 *             จะ pick ซ้ำเป็น card N+1 ผิด
 *
 * คำสั่งนี้ส่งเฉพาะ card ล่าสุดที่ pick ไว้แล้ว (อ่านจาก getCelticCards()) ไม่ pick ใหม่
 *
 * Usage:
 *   php artisan fortune:celtic-kick FTU-260505-J1439
 *   php artisan fortune:celtic-kick 1507 --tag=HUMAN_AGENT
 */
class FortuneCelticKick extends Command
{
    protected $signature = 'fortune:celtic-kick
                            {id : Reading ID หรือ bill_reference}
                            {--tag=HUMAN_AGENT : FB message tag (HUMAN_AGENT|POST_PURCHASE_UPDATE|CONFIRMED_EVENT_UPDATE)}
                            {--no-image : ไม่ส่งรูปไพ่ (text only — เผื่อ image push ติด policy)}';

    protected $description = 'กระตุ้นบอทส่งไพ่ใบล่าสุดที่ pick ไว้แล้ว (ใช้กรณี push ก่อนหน้า fail)';

    public function handle(): int
    {
        $id = $this->argument('id');
        $tag = $this->option('tag');
        $noImage = (bool) $this->option('no-image');

        $reading = is_numeric($id)
            ? FortuneReading::find((int) $id)
            : FortuneReading::where('bill_reference', $id)->first();

        if (! $reading) {
            $this->error("❌ ไม่พบ reading: {$id}");

            return 1;
        }

        $uid = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform ?? 'facebook';

        if (empty($uid)) {
            $this->error('❌ reading ไม่มี user_id');

            return 1;
        }

        // 🆕 (2026-05-21) รองรับทั้ง FB + LINE
        //   เคสจริง: บิล FTU-260520-Y0148 LINE — replyMessage timeout/push fail
        //   ลูกค้าเปิดไพ่ใน DB แล้ว แต่ไม่เห็นใน LINE
        if (! in_array($platform, ['facebook', 'line'], true)) {
            $this->warn("⚠️ รองรับเฉพาะ facebook/line (reading platform: {$platform})");

            return 1;
        }

        $cards = $reading->getCelticCards();
        $count = count($cards);

        $this->info("Reading #{$reading->id} | bill {$reading->bill_reference}");
        $this->info("Status: {$reading->conversation_status} | Picked: {$count}/10 | Platform: {$platform}");
        $this->info("UID: {$uid}");

        if ($count < 1) {
            $this->error('❌ ยังไม่มีไพ่ที่ pick');

            return 1;
        }

        // Clear all spam guards
        $cleared = [
            'fortune:silent',
            'fortune:rapid',
            'fortune:processing',
            'fortune:has_paid_active',
        ];
        foreach ($cleared as $k) {
            Cache::forget("{$k}:{$uid}");
        }
        $this->info('✅ Cleared spam guards');

        // อ่าน card ล่าสุด
        $last = end($cards);
        $pos = $last['position'] ?? 0;
        $cardTh = $last['card_name_th'] ?? '?';
        $cardEn = $last['card_name_en'] ?? '?';
        $reversed = ($last['is_reversed'] ?? false) ? '(กลับหัว)' : '(ตั้งตรง)';
        $meaning = mb_substr($last['meaning'] ?? '', 0, 200);
        $imageUrl = $last['image_url'] ?? null;
        $posMeta = FortuneReading::CELTIC_POSITIONS[$pos] ?? [];
        $posName = $posMeta['name'] ?? '?';

        $next = $reading->getNextCelticPosition();
        $nextMeta = $next ? (FortuneReading::CELTIC_POSITIONS[$next] ?? []) : [];
        $nextName = $nextMeta['name'] ?? '?';
        $remaining = 10 - $count;

        $message = "🔔 *ขออภัยที่ทำให้รอนะคะ — แอดมินตามให้แล้ว*\n"
            . "═══════════════════════\n\n"
            . "🃏✨ *ใบที่ {$count}/10 — ตำแหน่ง [{$posName}]*\n\n"
            . "ได้ไพ่ *{$cardTh}* {$reversed}\n({$cardEn})\n\n"
            . "📖 ความหมายไพ่นี้: {$meaning}";

        if ($next) {
            $message .= "\n\n──────────────────────\n"
                . "🃏 *ใบที่ {$next}/10 — ตำแหน่ง [{$nextName}]*\n"
                . "🧘 ตั้งจิต หลับตา 3 วินาที นึกถึงสิ่งที่อยากรู้\n"
                . "เมื่อพร้อมแล้ว พิมพ์ *'พร้อม'* เพื่อให้หมอเปิดไพ่ใบนี้ค่ะ\n\n"
                . "📌 เปิดไปแล้ว {$count}/10 — เหลืออีก *{$remaining} ใบ*";
        } else {
            $message .= "\n\n🌟 *เปิดครบ 10 ใบแล้ว* — รอแม่หมอวิเคราะห์...";
        }

        // FRESH settings — ไม่ผ่าน app() singleton
        $settings = FortuneTellingSetting::query()->first();

        // 🆕 (2026-05-21) แยก path ตาม platform
        if ($platform === 'line') {
            return $this->kickLine($settings, $uid, $message, $imageUrl, $count, $noImage);
        }

        return $this->kickFacebook($settings, $uid, $message, $imageUrl, $count, $tag, $noImage, $id);
    }

    /**
     * Push บน LINE — sendImage (push) + sendQuickReplies (push)
     */
    protected function kickLine(FortuneTellingSetting $settings, string $uid, string $message, ?string $imageUrl, int $count, bool $noImage): int
    {
        $tokenLen = mb_strlen($settings->line_channel_access_token ?? '');
        $this->info("LINE token len: {$tokenLen}");
        if ($tokenLen === 0) {
            $this->error('❌ LINE Channel Access Token ใน settings ว่าง');

            return 1;
        }

        $line = new LineFortuneService($settings);

        if (! $noImage && $imageUrl) {
            $this->info('Sending LINE image (push)...');
            try {
                $imgOk = $line->sendImage($uid, $imageUrl);
                $this->info('Image: ' . ($imgOk ? '✅' : '❌'));
            } catch (\Throwable $e) {
                $this->warn('Image error: ' . $e->getMessage());
            }
        }

        $this->info('Sending LINE text + quick replies (push)...');
        $quickReplies = [
            ['label' => $count >= 10 ? '🌟 รอแม่หมอ' : '🃏 เปิดไพ่ใบถัดไป', 'text' => 'พร้อม'],
            ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
        ];
        $txtOk = $line->sendQuickReplies($uid, $message, $quickReplies);
        $this->info('Text: ' . ($txtOk ? '✅' : '❌'));

        if (! $txtOk) {
            $this->newLine();
            $this->warn('⚠️ LINE push fail — ตรวจ:');
            $this->line('  - LINE token หมดอายุหรือยัง (admin/fortune/channels)');
            $this->line('  - LINE Push API quota เกินหรือเปล่า (ฟรี 500/เดือน)');
            $this->line('  - userId ถูกต้องไหม: ' . $uid);

            return 1;
        }

        $this->newLine();
        $this->info("✅ ส่งใบที่ {$count}/10 สำเร็จบน LINE");

        return 0;
    }

    /**
     * Push บน Facebook — sendImageMessage + sendMessage (original behavior)
     */
    protected function kickFacebook(FortuneTellingSetting $settings, string $uid, string $message, ?string $imageUrl, int $count, string $tag, bool $noImage, string $id): int
    {
        $tokenLen = mb_strlen($settings->facebook_page_token ?? '');
        $this->info("Settings token len: {$tokenLen}");

        if ($tokenLen === 0) {
            $this->error('❌ Page Access Token ใน settings ว่าง — ตั้งใน admin ก่อน');

            return 1;
        }

        $fb = new FacebookWebhookService($settings);
        $sendOpts = ['message_tag' => $tag, 'from_admin' => true];

        if (! $noImage && $imageUrl) {
            $this->info("Sending image with tag={$tag}...");
            try {
                $imgOk = $fb->sendImageMessage($uid, $imageUrl, $sendOpts);
                $this->info('Image: ' . ($imgOk ? '✅' : '❌'));
            } catch (\Throwable $e) {
                $this->warn('Image error: ' . $e->getMessage());
            }
        }

        $this->info("Sending text with tag={$tag}...");
        $txtOk = $fb->sendMessage($uid, $message, $sendOpts);
        $this->info('Text: ' . ($txtOk ? '✅' : '❌'));

        if (! $txtOk) {
            $this->newLine();
            $this->warn('⚠️ ส่งไม่สำเร็จ — ลองลอง tag อื่น:');
            $this->line('  php artisan fortune:celtic-kick ' . $id . ' --tag=POST_PURCHASE_UPDATE');
            $this->line('  php artisan fortune:celtic-kick ' . $id . ' --tag=CONFIRMED_EVENT_UPDATE');
            $this->line('  php artisan fortune:celtic-kick ' . $id . ' --no-image');

            return 1;
        }

        $this->newLine();
        $this->info("✅ ส่งใบที่ {$count}/10 สำเร็จ — ลูกค้าควรเห็นแล้ว");

        return 0;
    }
}
