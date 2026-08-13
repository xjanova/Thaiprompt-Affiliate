<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * 🔎 (2026-08-13) ตรวจว่า Facebook Message Tag ตัวไหน "ยังใช้ได้" — ไม่ต้องเดา
 *
 * ที่มา (เจ้าของ: "ไม่ต้องการเห็น @meta"):
 *   ปุ่ม "🎁 รับดวงฟรีประจำวัน" ยังหลุดเป็น quick reply ให้ @Meta AI แทรก
 *   ราก 2 ชั้น:
 *     ชั้น 1 (แก้แล้ว `dab819269`) — subcode 1545041 ตกลิสต์ → ไม่ retry MESSAGE_TAG เลย
 *     ชั้น 2 (ตัวนี้)            — พอ retry แล้ว Meta ตอบกลับว่า
 *                                  error_subcode 1893061
 *                                  "ไม่อนุญาตให้ใช้แท็กข้อความที่เลิกใช้แล้ว"
 *                                  = แท็ก POST_PURCHASE_UPDATE ที่โค้ดใช้อยู่ **ถูกยกเลิกแล้ว**
 *
 * 🕰️ ของเก่าที่ค้างไว้ 2 เดือน — เรื่องนี้ "เคยรู้แล้ว" แต่ไม่เคยกระจายออกไป:
 *   FacebookWebhookService:819 (2026-06-20) เขียนไว้ตรง ๆ ว่า
 *     "RESPONSE-first เสมอ — FB เลิกแท็ก POST_PURCHASE_UPDATE แล้ว (subcode 1893061)"
 *   แต่แก้แค่ sendAudio ตัวเดียว (ตอนนั้นอาการคือลูกค้า FB ไม่เคยได้ไฟล์เสียงเลย)
 *   แล้วเลี่ยงด้วยการยิง RESPONSE ก่อน เพราะเคสนั้นลูกค้าอยู่ใน 24 ชม.เสมอ
 *   → **ไม่มีใครไปหาว่าแท็กไหนใช้แทนได้** เมธอดอื่นอีก 4 ตัวยังใช้แท็กที่ตายแล้วต่อมา 2 เดือน
 *   คำสั่งนี้คือการไปตอบคำถามที่ค้างไว้ตรงนั้น
 *
 * ทำไมต้องมีคำสั่งนี้แทนการแก้แท็กมั่ว ๆ:
 *   แท็กนี้ถูกใช้ 20+ จุดทั่วระบบสำหรับ push นอกกรอบ 24 ชม.
 *   (ยืนยันบิล · FortuneCelticRedeliver · ProSessionNudge · auto-finalize)
 *   เดาผิด = ลูกค้าที่จ่ายเงินแล้วไม่ได้คำทำนาย → ต้อง "ยิงถามจริง" ก่อนแก้
 *   (บทเรียนเดียวกับกฎ verify AI model IDs — ห้ามเชื่อชื่อที่จำมา ต้องยิงทดสอบ)
 *
 * 🔒 ปลอดภัย: โหมดปกติยิงหา recipient id ปลอม ("0") → **ไม่มีข้อความถึงใครทั้งสิ้น**
 *    Meta ตรวจความถูกต้องของแท็ก *ก่อน* หา recipient จึงแยกได้ว่าแท็กตายหรือไม่
 *    (ยืนยันจาก prod: เคสจริงคืน 1893061 มาแทนที่จะคืน error เรื่องกรอบเวลา)
 *
 * Usage:
 *   php artisan fortune:fb-tag-probe                    # ปลอดภัย ไม่ส่งถึงใคร
 *   php artisan fortune:fb-tag-probe --to=<PSID>        # ⚠️ ส่งข้อความจริงถึง PSID นั้น
 *   php artisan fortune:fb-tag-probe --tags=HUMAN_AGENT,ACCOUNT_UPDATE
 */
class FortuneFbTagProbe extends Command
{
    protected $signature = 'fortune:fb-tag-probe
                            {--to= : PSID จริงที่จะยิงทดสอบ (⚠️ ส่งข้อความจริง — ใช้ PSID ของตัวเองเท่านั้น)}
                            {--tags= : รายชื่อแท็กคั่นด้วย comma (ไม่ระบุ = ใช้ชุดมาตรฐาน)}';

    protected $description = '🔎 ตรวจว่า FB Message Tag ตัวไหนยังใช้ได้ (แท็กที่ถูกยกเลิก = push นอก 24 ชม. ตายเงียบ)';

    /** recipient ปลอม — PSID จริงเป็นตัวเลข 15-17 หลัก "0" จึงไม่มีทางชนใคร */
    private const PROBE_RECIPIENT = '0';

    /** แท็กที่ Meta เคยรองรับ — เรียง "น่าจะรอดสุด" ก่อน */
    private const DEFAULT_TAGS = [
        'HUMAN_AGENT',
        'CONFIRMED_EVENT_UPDATE',
        'ACCOUNT_UPDATE',
        'POST_PURCHASE_UPDATE', // ตัวที่โค้ดใช้อยู่ — ใส่ไว้เป็นตัวควบคุม คาดว่าตาย
    ];

    public function handle(): int
    {
        $settings = FortuneTellingSetting::getSettings();
        $token = $settings->facebook_page_token ?? null;

        if (empty($token)) {
            $this->error('❌ ไม่มี facebook_page_token ใน settings — ตรวจไม่ได้');

            return 1;
        }

        $tags = $this->option('tags')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('tags')))))
            : self::DEFAULT_TAGS;

        $to = (string) ($this->option('to') ?: '');
        $realSend = $to !== '';
        $recipient = $realSend ? $to : self::PROBE_RECIPIENT;

        if ($realSend) {
            $this->warn("⚠️ โหมดส่งจริง — จะมีข้อความถึง PSID {$recipient} จำนวน ".count($tags).' ข้อความ');
        } else {
            $this->info('🔒 โหมดปลอดภัย — ยิงหา recipient ปลอม ไม่มีข้อความถึงใคร');
        }

        $this->newLine();
        $this->line(sprintf('%-24s %-10s %s', 'TAG', 'ผล', 'รายละเอียด'));
        $this->line(str_repeat('─', 84));

        $alive = [];
        $dead = [];

        foreach ($tags as $tag) {
            [$verdict, $detail] = $this->probeTag($token, $recipient, $tag, $realSend);

            if ($verdict === 'alive') {
                $alive[] = $tag;
                $this->line(sprintf('%-24s %-10s %s', $tag, '✅ ใช้ได้', $detail));
            } elseif ($verdict === 'dead') {
                $dead[] = $tag;
                $this->line(sprintf('%-24s %-10s %s', $tag, '❌ ถูกยกเลิก', $detail));
            } else {
                $this->line(sprintf('%-24s %-10s %s', $tag, '❓ ไม่ชัด', $detail));
            }
        }

        $this->newLine();
        $this->line(str_repeat('─', 84));

        if (! empty($alive)) {
            $this->info('✅ แท็กที่ยังใช้ได้: '.implode(', ', $alive));
            $this->line('   → เอาตัวแรกไปแทน POST_PURCHASE_UPDATE ใน FacebookWebhookService');
            $this->line('     (sendMessage · sendImage · sendAudio · sendQuickReplies · sendButtonTemplate)');
            $this->line('     และคำสั่ง console ที่ push นอก 24 ชม. อีก 20+ จุด');
        } else {
            $this->error('❌ ไม่มีแท็กไหนใช้ได้เลย');
            $this->line('   → แปลว่า Meta ปิดทาง MESSAGE_TAG ของเพจนี้ทั้งหมด');
            $this->line('     ทางเดียวที่เหลือคือ Private Reply (recipient.comment_id, 7 วันหลังคอมเมนต์)');
            $this->line('     ซึ่งครอบได้เฉพาะคนที่มาจากคอมเมนต์/รีแอค ไม่ครอบคนที่เงียบไปเฉย ๆ');
        }

        if (! empty($dead)) {
            $this->warn('❌ แท็กที่ตายแล้ว: '.implode(', ', $dead));
        }

        return empty($alive) ? 1 : 0;
    }

    /**
     * ยิงทดสอบแท็กเดียว แล้วแปลผลจาก error ที่ Meta ตอบกลับ
     *
     * @return array{0: string, 1: string} [verdict, รายละเอียด] · verdict = alive|dead|unknown
     */
    private function probeTag(string $token, string $recipient, string $tag, bool $realSend): array
    {
        $text = $realSend
            ? "🔎 ทดสอบระบบ (tag={$tag}) — ข้อความนี้ส่งจากคำสั่งตรวจสอบ ไม่ต้องตอบกลับค่ะ"
            : 'probe';

        try {
            $response = Http::timeout(20)->post(
                'https://graph.facebook.com/'.FacebookWebhookService::GRAPH_API_VERSION.'/me/messages',
                [
                    'recipient' => ['id' => $recipient],
                    'message' => ['text' => $text],
                    'messaging_type' => 'MESSAGE_TAG',
                    'tag' => $tag,
                    'access_token' => $token,
                ]
            );
        } catch (\Throwable $e) {
            return ['unknown', 'ยิงไม่ออก: '.mb_substr($e->getMessage(), 0, 60)];
        }

        if ($response->successful()) {
            // สำเร็จจริง = แท็กใช้ได้แน่นอน (เกิดได้เฉพาะโหมด --to ที่ PSID ถูกต้อง)
            return ['alive', 'ส่งสำเร็จจริง'];
        }

        $error = $response->json('error', []);
        $subcode = (int) ($error['error_subcode'] ?? 0);
        $userTitle = (string) ($error['error_user_title'] ?? '');
        $message = (string) ($error['message'] ?? '');

        // 1893061 = "ไม่อนุญาตให้ใช้แท็กข้อความที่เลิกใช้แล้ว" — คำตอบตรงตัวว่าแท็กตาย
        if ($subcode === 1893061 || str_contains($userTitle, 'เลิกใช้') || stripos($userTitle, 'deprecat') !== false) {
            return ['dead', "subcode {$subcode} · ".($userTitle ?: $message)];
        }

        // 2018001 = "No matching user found" — ผ่านด่านตรวจแท็กมาแล้วถึงจะมาตายที่ recipient
        //   = สัญญาณว่า "แท็กนี้ Meta ยังรับอยู่" (โหมดปลอดภัยคาดหวังผลนี้)
        if ($subcode === 2018001 || stripos($message, 'no matching user') !== false) {
            return ['alive', "ผ่านด่านแท็ก (ตายที่ recipient ปลอม — subcode {$subcode})"];
        }

        // 551/1545041 = ผู้รับอยู่นอกกรอบ/ติดต่อไม่ได้ — แปลว่าแท็กผ่าน แต่คนนี้ส่งไม่ถึง
        if ($subcode === 1545041) {
            return ['alive', 'ผ่านด่านแท็ก แต่ผู้รับติดต่อไม่ได้ (551) — ลอง PSID อื่น'];
        }

        return ['unknown', "subcode {$subcode} · ".mb_substr($userTitle ?: $message, 0, 60)];
    }
}
