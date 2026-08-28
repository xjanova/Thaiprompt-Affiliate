<?php

namespace App\Console\Commands;

use App\Models\FortuneInviteMessage;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortuneGreetingService;
use App\Services\LineAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 🩺 ตรวจความพร้อมของ "โหมด DM ดูดวงรายวัน" ก่อนเปิดใช้จริง
 *
 * 🌙 (2026-07-31) คู่กับ fortune:transfer-preflight ของโหมด transfer
 *
 * เหตุผลที่ต้องมี: โหมดนี้ไป "สัญญา" กับลูกค้าว่าจะทำนายให้ฟรี
 * ถ้าเปิดโหมดแล้วของไม่พร้อม (บทความยังไม่ถูกสร้าง / ไม่มีข้อความชวน)
 * ลูกค้าจะตอบกลับมาแล้วเจอความเงียบ = ความเสียหายที่แย่ที่สุดของบอทตัวนี้
 *
 * ใช้:
 *   php artisan fortune:daily-preflight                  ตรวจอย่างเดียว (เดิม)
 *   php artisan fortune:daily-preflight --heal           ขาดบทความ → สั่งสร้างซ้ำให้
 *   php artisan fortune:daily-preflight --heal --alert   + แจ้งแอดมิน (ใช้ใน cron)
 *
 * 🚨 (2026-08-08) เพิ่ม --heal/--alert หลังเคสจริง:
 *   บทความของวันนั้นไม่ถูกสร้างเลย (0 แถว) → `isDailyServing()` = false →
 *   DM คอมเมนต์/กดไลก์ตกไปใช้ชุดขายแบบเก่า **อย่างเงียบ ๆ** ยิงไปแล้ว 465 ราย
 *   กว่าเจ้าของจะสังเกตเห็นเองก็บ่ายแล้ว — คำสั่งนี้เดิมเป็นเครื่องมือมือเปล่า
 *   ที่ไม่มีใครเรียก จึงยกให้เป็น "ยามเฝ้าประตู" ใน routes/console.php ข้อ 14b
 */
class FortuneDailyPreflight extends Command
{
    protected $signature = 'fortune:daily-preflight
        {--heal : ขาดบทความของวันนี้ → สั่ง horoscope:generate-daily ซ้ำให้อัตโนมัติ}
        {--alert : แจ้งแอดมิน (LINE OA + Log::error) เมื่อพบปัญหา — ใช้กับ cron}';

    protected $description = 'ตรวจความพร้อมของโหมด DM ดูดวงรายวัน (บทความวันนี้ + ข้อความชวน + สวิตช์)';

    public function handle(): int
    {
        $heal = (bool) $this->option('heal');
        $alert = (bool) $this->option('alert');

        $this->info('🩺 ตรวจความพร้อมโหมดดูดวงรายวัน');
        $this->newLine();

        // ⚠️ แยก 2 กอง โดยตั้งใจ:
        //    $problems      = คำตัดสินของ CLI (exit code) — ต้องเข้มเหมือนเดิมทุกประการ
        //                     เพราะคนเรียกมือคือคนที่กำลังจะ "เปิดโหมด daily" แล้วอยากรู้ว่า
        //                     พร้อมไหม การไฟเขียวทั้งที่ไม่มีข้อความชวน = หลอกให้เปิดโหมดพัง
        //    $alertProblems = สิ่งที่คุ้มค่าจะปลุกแอดมินตอนตี 0 — ตัดเรื่องที่ยังไม่กระทบ
        //                     ลูกค้าจริงออก ไม่งั้นเตือนทุกคืนจนไม่มีใครอ่าน แล้ววันที่
        //                     บทความหายจริงจะถูกกลืนไปกับเสียงรบกวน
        $problems = [];
        $alertProblems = [];

        // ── 1. โหมดปัจจุบัน
        $mode = new FortuneBotMode;
        $current = $mode->mode();

        if ($mode->isDaily()) {
            $this->line('  ✅ โหมดปัจจุบัน : <fg=green>daily</> (ทำงานอยู่)');
        } else {
            $this->line("  ℹ️  โหมดปัจจุบัน : {$current} (ยังไม่ได้เปิดโหมด daily)");
            $this->line('     เปิดได้ที่ Admin → ตั้งค่าดูดวง → โหมดบอท');
        }

        // 🎁 (2026-08-28) สวิตช์แอดมิน "ระบบชวนรับดวงรายวันฟรี"
        //   ต้องโชว์ตรงนี้ ไม่งั้นแอดมินปิดสวิตช์แล้วมาถามว่า "โหมด daily เปิดอยู่ ทำไม DM ไม่ชวน"
        //   แล้วไล่หาสาเหตุจากบทความ/cron แทนที่จะเห็นว่าเป็นค่าที่ตัวเองตั้งไว้
        //   ⚠️ ไม่นับเป็น problem — ปิดเองคือความตั้งใจ ไม่ใช่ระบบพัง
        if ($mode->dailyFreeOutboundEnabled()) {
            $this->line('  ✅ ชวนดวงฟรี  : เปิดอยู่ (DM ชวนบอกวันเกิด + การ์ด 🎁 รับดวงฟรี)');
        } else {
            $this->line('  ⛔ ชวนดวงฟรี  : <fg=yellow>ปิดโดยแอดมิน</> — DM ใช้ชุดข้อความชวนดูดวงชุดแรกอย่างเดียว');
            $this->line('     ลูกค้าที่พิมพ์ "ดูดวงฟรี" เองยังได้ของครบเหมือนเดิม (ปิดแค่ฝั่งชวน)');
            $this->line('     เปิดคืนได้ที่ Admin → ตั้งค่าดูดวง → โหมดบอท → 🎁 ระบบชวนรับดวงรายวันฟรี');
        }

        // ── 2. บทความดวงรายวันของวันนี้ (ตัวที่ horoscope:generate-daily สร้าง 00:01)
        //
        // ⚠️ ตรวจตัวนี้ **ทุกโหมด** ไม่ใช่เฉพาะ daily — ปุ่ม "รับดวงประจำวันเกิด"
        //    ถูกยื่นในโหมด classic ด้วย (ตอนลูกค้าขอดูฟรีแต่สิทธิ์หมด)
        //    ดู rule: ขอฟรีแล้วสิทธิ์หมด ห้ามเด้งเมนูราคา → dailyReplyAllowedFor()
        $greeting = app(FortuneGreetingService::class);
        $pre = $greeting->dailyPreflight();
        $healed = false;

        if (! $pre['ready'] && $heal) {
            $this->newLine();
            $this->warn("  🔧 ขาดบทความ {$pre['found']}/7 → กำลังสั่งสร้างซ้ำ (horoscope:generate-daily)...");

            try {
                // idempotent — command ข้ามวันเกิดที่มีอยู่แล้ว (ไม่มี --force = ไม่เผา AI ซ้ำ)
                $this->call('horoscope:generate-daily');
            } catch (Throwable $e) {
                $this->error('  ❌ สั่งสร้างซ้ำไม่สำเร็จ: '.$e->getMessage());
                Log::error('🩺 daily-preflight: heal ล้ม', ['error' => $e->getMessage()]);
            }

            // 🔄 ล้างแคช 5 นาทีของ dailyArticlesReadyToday() ไม่งั้นด่านขาออก
            //    จะยังเห็นค่าเก่า (false) ต่ออีกหลายนาทีทั้งที่บทความมาแล้ว
            $greeting->forgetDailyArticlesReadyCache();

            $pre = $greeting->dailyPreflight();
            $healed = $pre['ready'];
            $this->newLine();
        }

        if ($pre['ready']) {
            $suffix = $healed ? ' <fg=yellow>(กู้คืนโดย --heal)</>' : '';
            $this->line("  ✅ บทความวันนี้ : ครบ 7 วันเกิด ({$pre['today']}){$suffix}");
        } else {
            $problems[] = $alertProblems[] = "บทความดวงรายวันของ {$pre['today']} มีแค่ {$pre['found']}/7"
                .' — ขาด '.implode(', ', array_map(fn ($d) => 'วัน'.$d, $pre['missing']));
            $this->line("  ❌ บทความวันนี้ : มี {$pre['found']}/7 ({$pre['today']})");
            $this->line('     ขาด: '.implode(', ', array_map(fn ($d) => 'วัน'.$d, $pre['missing'])));
            $this->line('     แก้: <fg=yellow>php artisan horoscope:generate-daily</>');
        }

        // ── 3. ข้อความชวนชุดโหมด daily
        $inviteCount = FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_DAILY)
            ->where('is_active', true)
            ->count();

        if ($inviteCount > 0) {
            $this->line("  ✅ ข้อความชวน  : {$inviteCount} ข้อความ (mode=daily)");
        } else {
            $this->line('  ❌ ข้อความชวน  : ไม่มีเลย — DM จะไปหยิบชุดกลางที่ไม่ได้ขอวันเกิด');
            $this->line('     แก้: <fg=yellow>php artisan db:seed --class=FortuneDailyInviteMessageSeeder --force</>');

            // CLI นับเป็นปัญหาเสมอ (พฤติกรรมเดิม — คนเรียกมือกำลังจะเปิดโหมด)
            $problems[] = 'ไม่มีข้อความชวนชุด mode=daily ที่เปิดใช้งานเลย';

            // 🔕 แต่ปลุกแอดมินเฉพาะตอนโหมด daily เปิดอยู่จริง — โหมด classic
            //    ไม่ได้ใช้ชุดข้อความนี้เลย จะเตือนทุกคืนไปทำไม
            if ($mode->isDaily()) {
                $alertProblems[] = 'ไม่มีข้อความชวนชุด mode=daily ที่เปิดใช้งานเลย';
            }
        }

        $this->newLine();

        // ── 4. แจ้งเตือน (เฉพาะ --alert) — ทั้งตอนกู้ไม่สำเร็จ และตอนกู้สำเร็จ
        if ($alert && ($alertProblems !== [] || $healed)) {
            $this->pushAlert($alertProblems, $healed, $pre['today']);
        }

        if ($problems !== []) {
            $count = count($problems);
            $this->error("❌ ยังไม่พร้อม — มี {$count} เรื่องต้องแก้ก่อนเปิดโหมด");

            return self::FAILURE;
        }

        $this->info($healed ? '✅ พร้อมใช้งาน (หลังกู้คืนอัตโนมัติ)' : '✅ พร้อมใช้งาน');

        return self::SUCCESS;
    }

    /**
     * 📢 แจ้งแอดมิน — LINE OA + Log::error
     *
     * ⚠️ Log::error ต้องมาก่อนเสมอ และ LINE ต้องอยู่ใน try/catch:
     *    ช่องทาง LINE พึ่งพา (1) แอดมินที่ผูก line_user_id ไว้ และ (2) log channel 'line'
     *    ถ้าอย่างใดอย่างหนึ่งหาย การแจ้งเตือนต้องไม่หายตามไปด้วย — และห้าม throw
     *    ออกมาจนทำให้ตัว preflight เองล้ม (ยามที่ตายเพราะกริ่งเสีย = แย่กว่าไม่มียาม)
     *
     * @param  array<int, string>  $problems
     */
    protected function pushAlert(array $problems, bool $healed, string $today): void
    {
        $title = $problems !== []
            ? '🔮 ดวงรายวันไม่พร้อม — DM จะตกไปใช้ชุดขายแบบเก่า'
            : '🔮 ดวงรายวันกู้คืนสำเร็จ (รอบ 00:01 มีปัญหา)';

        $detail = "วันที่: {$today}\n";
        $detail .= $healed ? "สถานะ: กู้คืนอัตโนมัติสำเร็จแล้ว\n" : '';

        foreach ($problems as $i => $problem) {
            $detail .= '- '.($i + 1).') '.$problem."\n";
        }

        if ($problems !== []) {
            $detail .= "\nผลกระทบ: isDailyServing()=false → DM คอมเมนต์/กดไลก์"
                ." จะไม่ส่งชุดดูดวงฟรีรายวัน\nแก้มือ: php artisan horoscope:generate-daily";
        }

        Log::error('🩺 '.$title, [
            'today' => $today,
            'healed' => $healed,
            'problems' => $problems,
        ]);

        try {
            app(LineAlertService::class)->alertSystemError($title, ['detail' => $detail]);
        } catch (Throwable $e) {
            Log::warning('🩺 daily-preflight: แจ้งเตือน LINE ไม่สำเร็จ (log ไว้แล้ว)', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
