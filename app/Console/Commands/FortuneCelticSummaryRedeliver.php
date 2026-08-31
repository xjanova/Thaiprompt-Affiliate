<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use App\Services\LineGatekeeperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🛟 (2026-08-31) ตามส่ง "บทสรุป Grand Finale 99฿" ที่ส่งไม่ออก
 *
 * ## ทำไมต้องแยกคำสั่งใหม่ ไม่ไปแปะใน fortune:celtic-redeliver
 * ตัวกู้บทสรุปของเดิมถูกวางไว้ **ข้างในลูปของคำถามที่ยังไม่ส่ง**
 * (`FortuneCelticRedeliver` บรรทัด ~109) แต่ก่อนเข้าลูปมีด่านนี้:
 *
 *     if ($candidates->isEmpty()) { return 0; }   // ← ออกตรงนี้
 *
 * ⇒ พอคำถามถูกส่งครบทุกข้อ (ซึ่งเป็นเรื่องปกติ เพราะ parked delivery ฝั่ง LINE
 *   ส่งคืนให้ฟรีตอนลูกค้าทักมา) ลูปจะว่าง → return ก่อน → **ตัวกู้บทสรุปไม่เคยทำงาน**
 *   ตาข่ายขาดตรงเคสที่ต้องใช้มันที่สุด: *คำถามครบแต่บทสรุปหาย*
 *   = ลูกค้าจ่าย 99฿ แล้วไม่ได้ของชิ้นที่สำคัญที่สุด (เคสจริง reading 11901, 2026-08-31)
 *
 * ## นโยบายโควต้า (เจ้าของสั่ง 2026-08-31)
 * "เมื่อบทสรุปเสร็จ ควรส่งให้ลูกค้า แต่ถ้าต้อง Push ก็ให้พุชไป"
 * ⇒ บทสรุปคือ **ของสำคัญ** ที่ push ถูกสงวนไว้ให้ใช้ — ยิง priority ได้เต็มที่
 *   แต่ถ้าโควต้าหมดจริง ให้ **ข้ามโดยไม่นับ attempt** เก็บสิทธิ์ไว้ใช้ตอน push กลับมา
 *   (ระหว่างนั้น `flushParkedCelticSummary()` ส่งคืนฟรีผ่าน reply ตอนลูกค้าทัก)
 *
 * ## หน้าต่างเวลา — ทำไมถึงยาวกว่า redeliver รายข้อ (2 ชม.)
 * คำตอบรายข้อเก่าเกินไปแล้วส่งไปลูกค้างง — แต่ **บทสรุปคือตัวสินค้า** ลูกค้าจ่ายมาเพื่อสิ่งนี้
 * ยังไงก็ต้องได้ ⇒ ตั้ง 3 วันให้ตรงกับหน้าต่างของ `flushParkedCelticSummary()`
 *
 * ตัวอย่าง:
 *   php artisan fortune:celtic-summary-redeliver --dry
 *   php artisan fortune:celtic-summary-redeliver --reading=11901   # ตามส่งด้วยมือ ข้ามหน้าต่างเวลา
 */
class FortuneCelticSummaryRedeliver extends Command
{
    protected $signature = 'fortune:celtic-summary-redeliver
                            {--dry : Dry run — แสดงรายการ ไม่ส่งจริง}
                            {--reading= : เจาะจง reading id (ข้ามหน้าต่างเวลา + เพดาน attempt — ใช้ตอนตามส่งด้วยมือ)}
                            {--max-days=3 : อายุสูงสุดของบิลที่จะตามส่ง}
                            {--min-seconds=60 : อายุขั้นต่ำหลังจบเซสชัน (ให้ sync push ได้ลองก่อน)}
                            {--max-attempts=5 : จำนวนครั้งสูงสุดที่พยายามส่ง}
                            {--limit=20 : จำนวน reading สูงสุดต่อรอบ}';

    protected $description = 'ตามส่งบทสรุป Grand Finale (99฿) ที่ push ไม่ออก — แยกจาก redeliver รายข้อ';

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry');
        $only = $this->option('reading');
        $maxDays = max(1, (int) $this->option('max-days'));
        $minSeconds = max(0, (int) $this->option('min-seconds'));
        $maxAttempts = max(1, (int) $this->option('max-attempts'));
        $limit = max(1, (int) $this->option('limit'));

        $query = FortuneReading::query()->where('is_paid', true);

        if ($only) {
            // 🔧 โหมดมือ: ยึด id อย่างเดียว — ไม่กรองหน้าต่างเวลา/attempt
            //   ใช้ตอนแอดมินรู้ตัวว่าลูกค้ารายนี้ไม่ได้บทสรุป แล้วอยากยิงให้เดี๋ยวนี้
            $query->whereKey((int) $only);
        } else {
            $query->where('conversation_state->celtic_summary_delivered', false)
                ->where('created_at', '>=', now()->subDays($maxDays))
                ->latest()
                ->limit($limit);
        }

        $readings = $query->get();

        if ($readings->isEmpty()) {
            $this->info('✅ ไม่มีบทสรุป Celtic ที่ค้างส่ง');

            return 0;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $noText = 0;

        foreach ($readings as $reading) {
            $tag = "reading {$reading->id} (".($reading->bill_reference ?? $reading->order_number ?? '-').')';

            // ส่งไปแล้ว → ไม่ต้องส่งซ้ำ (โหมดมือก็ห้ามยิงซ้ำ — ลูกค้าจะเห็นบทสรุป 2 รอบ)
            // ⚠️ ต้อง cast bool ห้ามเทียบ `=== true` — ค่าใน JSON อาจกลับมาเป็น int 1
            //    ("จ่ายแล้ว" เทียบไม่ติด = ยิงบทสรุปซ้ำใส่ลูกค้า)
            //    ส่วน reading เก่าที่ไม่มีธง = null → default false → ไหลต่อไปโดนด่าน
            //    "ไม่มี celtic_finale_text" ข้างล่างดักอีกชั้น ปลอดภัยทั้งสองทาง
            if ((bool) $reading->getConversationState('celtic_summary_delivered', false)) {
                $this->line("  {$tag} ข้าม — บทสรุปส่งถึงแล้ว");
                $skipped++;

                continue;
            }

            // ลูกค้ากดขอดูบทสรุปซ้ำเองไปแล้ว → ถือว่าถึงมือแล้ว
            if ($reading->getConversationState('celtic_finale_replayed', false)) {
                $this->line("  {$tag} ข้าม — ลูกค้าเปิดดูบทสรุปซ้ำเองแล้ว");
                $skipped++;

                continue;
            }

            $text = trim((string) $reading->getConversationState('celtic_finale_text', ''));

            if ($text === '') {
                // ⚠️ บิลก่อน 2026-08-26 ไม่ได้เก็บ finale text ไว้ → ส่งซ้ำไม่ได้ ต้องสร้างใหม่
                //   ไม่นับเป็น failed เพราะไม่ใช่ความผิดของการส่ง — แต่ต้องโผล่ในรายงานให้เห็น
                $this->warn("  {$tag} ⚠️ ไม่มี celtic_finale_text เก็บไว้ — ส่งซ้ำไม่ได้ (ต้อง regenerate)");
                $noText++;

                continue;
            }

            // อายุขั้นต่ำ — ให้เส้น sync ตอนจบเซสชันได้ลองก่อน กัน cron ตัดหน้า
            // 📌 หมายเหตุ: `setConversationState()` เรียก `update()` ⇒ แตะ `updated_at` ทุกครั้ง
            //    ด่านนี้จึงมีผลเป็น "เว้น 60 วิ นับจากความพยายามครั้งก่อน" ไม่ใช่นับจากจบเซสชัน
            //    ซึ่งเป็นพฤติกรรมที่ต้องการอยู่แล้ว (backoff ธรรมชาติ ไม่รัวทุกนาที)
            $endedAt = $reading->updated_at;
            if (! $only && $endedAt && $endedAt->gt(now()->subSeconds($minSeconds))) {
                $this->line("  {$tag} ข้าม — เพิ่งจบเซสชัน รอ sync push ลองก่อน");
                $skipped++;

                continue;
            }

            $attempts = (int) $reading->getConversationState('celtic_summary_attempts', 0);
            if (! $only && $attempts >= $maxAttempts) {
                $this->warn("  {$tag} ข้าม — ครบเพดาน attempt แล้ว ({$attempts})");
                $skipped++;

                continue;
            }

            // ⚠️ platform field ก่อน แล้วค่อยเดาจากรูปแบบ id
            //   `fortune_readings` **ไม่มีคอลัมน์ line_user_id** — LINE userId เก็บใน
            //   platform_user_id / facebook_user_id ⇒ ห้ามใช้ "มี facebook_user_id ไหม"
            //   ตัดสินว่าเป็นลูกค้า FB (บั๊กเดิมของ sendCelticThinkingAck)
            $platform = $reading->platform;
            if (! $platform || ! in_array($platform, ['facebook', 'line'], true)) {
                $candidateId = $reading->platform_user_id ?: $reading->facebook_user_id ?: '';
                $platform = preg_match('/^U[a-f0-9]{32}$/i', (string) $candidateId) ? 'line' : 'facebook';
            }

            $userId = $platform === 'line'
                ? (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '')
                : (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');

            if ($userId === '') {
                $this->warn("  {$tag} ข้าม — ไม่มี user id");
                $skipped++;

                continue;
            }

            $this->info("  {$tag} [{$platform}] บทสรุป ".mb_strlen($text).' ตัวอักษร (attempt '.($attempts + 1).')');

            if ($isDry) {
                $this->line('    [DRY] '.mb_substr($text, 0, 90).'...');

                continue;
            }

            // 🚫 โควต้า LINE หมด → ยิงไปก็ไม่ออก ข้ามโดย **ไม่นับ attempt**
            //   เก็บสิทธิ์ retry ไว้ใช้ตอน push กลับมาจริง
            //   ระหว่างนี้ flushParkedCelticSummary() ส่งคืนฟรีผ่าน reply ตอนลูกค้าทักมา
            if ($platform === 'line' && LineGatekeeperService::isQuotaExhausted()) {
                $this->warn('    ⏸️ ข้าม — โควต้า LINE หมด (รอ push กลับมา / ลูกค้าทักมาแล้วส่งฟรีผ่าน reply)');
                $skipped++;

                continue;
            }

            // นับ attempt ก่อนส่ง — แม้ exception ก็ถือว่าใช้ไป 1 ครั้ง (กัน loop ไม่รู้จบ)
            $reading->setConversationState('celtic_summary_attempts', $attempts + 1);

            try {
                $ok = $platform === 'line'
                    ? $this->sendLine($userId, $text, $reading)
                    : $this->sendFacebook($userId, $text, $reading);

                if ($ok) {
                    $reading->setConversationState('celtic_summary_delivered', true);
                    $reading->setConversationState('celtic_summary_delivered_at', now()->toIso8601String());
                    $sent++;
                    $this->info('    ✅ ส่งบทสรุปถึงลูกค้าแล้ว');

                    Log::info('FortuneCelticSummaryRedeliver: ส่งบทสรุปสำเร็จ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'attempt' => $attempts + 1,
                        'bill_reference' => $reading->bill_reference ?? null,
                    ]);
                } else {
                    $failed++;
                    $this->error('    ❌ ส่งไม่สำเร็จ — คงธงค้างไว้ให้รอบหน้า');

                    Log::warning('FortuneCelticSummaryRedeliver: ส่งบทสรุปไม่สำเร็จ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'attempt' => $attempts + 1,
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("    ❌ exception: {$e->getMessage()}");

                Log::error('FortuneCelticSummaryRedeliver: exception', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: ส่งแล้ว={$sent} ข้าม={$skipped} ล้มเหลว={$failed} ไม่มีเนื้อบทสรุป={$noText}");

        if ($noText > 0) {
            $this->warn('⚠️ มีบิลที่ไม่มี celtic_finale_text เก็บไว้ — ส่งซ้ำไม่ได้ ต้องสร้างบทสรุปใหม่จากไพ่+ประวัติคำถาม');
        }

        return $failed > 0 ? 1 : 0;
    }

    /**
     * ส่งบทสรุปฝั่ง LINE — เนื้อหาก่อน แล้วค่อยเติมรูปถ้ายังไม่ครบ 5 objects
     *
     * รูปแบบเดียวกับ `flushParkedCelticSummary()` เพื่อให้ลูกค้าเห็นของหน้าตาเดิม
     * ไม่ว่าจะได้จากทางฟรี (reply) หรือทางนี้ (push)
     */
    protected function sendLine(string $userId, string $text, FortuneReading $reading): bool
    {
        $lineService = app(LineFortuneService::class);

        $messages = [];
        foreach ($lineService->splitTextForFlexPublic($text, 4500) as $chunk) {
            $messages[] = ['type' => 'text', 'text' => $chunk];
        }
        $messages = array_slice($messages, 0, 5);

        foreach (['celtic_finale_chart_url', 'celtic_finale_image_url'] as $key) {
            if (count($messages) >= 5) {
                break;
            }
            $url = trim((string) $reading->getConversationState($key, ''));
            if ($url !== '') {
                $messages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $url,
                    'previewImageUrl' => $url,
                ];
            }
        }

        // ✅ บทสรุป = ของที่ลูกค้าจ่ายเงินแล้ว → เข้าเงื่อนไข push ตามนโยบาย (priority)
        return $lineService->pushPaidDeliverable($userId, $messages, true);
    }

    /**
     * ส่งบทสรุปฝั่ง Facebook — POST_PURCHASE_UPDATE ส่งได้แม้นอกกรอบ 24 ชม. (จ่ายแล้ว)
     */
    protected function sendFacebook(string $userId, string $text, FortuneReading $reading): bool
    {
        $fbService = app(FacebookWebhookService::class);

        $ok = (bool) $fbService->sendMessage($userId, $text, [
            'from_admin' => true,
            'message_tag' => 'POST_PURCHASE_UPDATE',
        ]);

        if (! $ok) {
            return false;
        }

        // รูปเป็นของแถม — ส่งไม่ได้ก็ไม่ถือว่าล้มเหลว (เนื้อหาถึงลูกค้าแล้ว)
        foreach (['celtic_finale_chart_url', 'celtic_finale_image_url'] as $key) {
            $url = trim((string) $reading->getConversationState($key, ''));
            if ($url === '') {
                continue;
            }
            try {
                $fbService->sendImage($userId, $url);
            } catch (\Throwable $e) {
                Log::debug('FortuneCelticSummaryRedeliver: FB image fail (non-blocking)', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }
}
