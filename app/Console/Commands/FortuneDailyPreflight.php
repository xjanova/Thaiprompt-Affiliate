<?php

namespace App\Console\Commands;

use App\Models\FortuneHoroscopeContent;
use App\Models\FortuneInviteMessage;
use App\Services\Fortune\DailyArticleMirror;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortuneGreetingService;
use App\Services\FortuneHoroscopeService;
use App\Services\LineAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
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

        // ── 2b. 🪞 (2026-09-09) บทความ "เลนโพส" ที่ล้มรายใบ — ต้นทางของคำทำนายทั้งระบบ
        //
        // 🚨 ทำไมด่านข้อ 2 จับไม่ได้: มันถามว่า "เลนแชทครบ 7 ใบไหม" — ครบเสมอ
        //    เพราะเมื่อไม่มีบทความเลนโพส เลนแชทจะ **ยิง AI เอง** เป็น fallback
        //    ⇒ ลูกค้าที่อ่านโพสแล้วทักมา ได้คำทำนาย **คนละใบ** ของวันเดียวกัน
        //    ผิดคำสั่งเจ้าของ: "ดวงรายวันในแชทต้องดึงจากโพสรายวัน"
        //    (ซ้ำรอย [[rule_feature_built_but_never_wired]] — fallback ที่ดี = ความล้มเหลวที่มองไม่เห็น)
        //
        // เคสจริง: 2026-09-08 ล้ม 1 ใบ (เสาร์) · 2026-09-09 ล้ม 2 ใบ (อังคาร/พุธ)
        //    error เดียวกัน "ไม่สามารถเชื่อมต่อ AI ได้ (ลองแล้ว 0 keys)" = พูลคีย์ว่างชั่วขณะ
        //    (ใบอื่นในนาทีเดียวกันผ่านหมด ⇒ ไม่ใช่ AI ล่ม แค่จังหวะไม่มีคีย์ว่าง)
        //
        // ⚠️ ทำไมไม่แก้ที่ generateDailyContent(): `schedule_time = 00:01` ⇒ หน้าต่าง
        //    "ยังไม่ถึงเวลาโพส" ปิดตั้งแต่ 00:01 = retry ได้ **0 ครั้ง**
        //    และการเลิก stamp `last_generated_at` ตอนล้มบางใบ จะเปิดทางให้ tick 5 นาที
        //    ยิง AI ซ้ำทั้งวันถ้าใบนั้นพังถาวร — ยามตัวนี้รัน 00:20 + 06:00 = retry 2 ครั้ง
        //    มีขอบเขตชัด นับได้ ไม่วนเผาเงิน
        //
        // 🪞 generateForBirthDay() เรียก DailyArticleMirror::mirror() ให้เองหลัง markGenerated()
        //    ⇒ ซ่อมเลนโพสสำเร็จ = เลนแชทถูกทับให้ตรงทันที ไม่ต้องสั่งอะไรเพิ่ม
        $this->healFailedPostLaneArticles($heal, $problems, $alertProblems);

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
    /**
     * 🪞 ซ่อมบทความ "เลนโพส" ที่ล้มรายใบของวันนี้ — แล้ว mirror จะทับเลนแชทให้ตรงเอง
     *
     * @param  bool  $heal  ลงมือซ่อมจริงไหม (false = รายงานอย่างเดียว)
     * @param  array<int,string>  $problems  รายการปัญหาที่โชว์บนจอ (by-ref)
     * @param  array<int,string>  $alertProblems  รายการที่ส่งแจ้งแอดมิน (by-ref)
     */
    protected function healFailedPostLaneArticles(bool $heal, array &$problems, array &$alertProblems): void
    {
        try {
            $today = Carbon::now('Asia/Bangkok')->toDateString();

            // 🪞 (2026-09-09) ขั้นแรก: คัดลอกใบที่ "สำเร็จ" ลงเลนแชทซ้ำเสมอ — **ฟรี ไม่กิน AI**
            //
            //   ทำไมต้องทำแม้เลน A จะสำเร็จ: เลน B อาจเขียนทับ mirror ไปแล้ว (race)
            //   เคสจริง 2026-09-09 วันพฤหัสบดี — A สำเร็จ 00:01:45 · B ทับ 00:01:51
            //   ด่าน "ใบที่ล้ม" ข้างล่างมองไม่เห็นเคสนี้เลย เพราะ status = generated
            //
            //   mirror() เป็น updateOrCreate ⇒ idempotent · รันซ้ำได้ไม่เสียอะไร
            //   ⇒ กติกาง่ายที่สุดที่รับประกันผล: **มีของเลน A เมื่อไหร่ คัดลอกทับเสมอ**
            //   ไม่ต้องเดาว่า "ตรงกันหรือยัง" ด้วย heuristic ใด ๆ
            $this->remirrorGeneratedArticles($today);

            $failed = FortuneHoroscopeContent::whereDate('target_date', $today)
                ->where('status', FortuneHoroscopeContent::STATUS_FAILED)
                ->orderBy('birth_day')
                ->get();

            if ($failed->isEmpty()) {
                $this->line('  ✅ บทความเลนโพส : ไม่มีใบที่ล้ม (แชทดึงจากโพสได้ครบ)');

                return;
            }

            // 🏷️ ใช้คอลัมน์ `birth_day_name` ที่ถูกเขียนไว้ตอน updateOrCreate
            //    ⚠️ ห้ามใช้ FortuneHoroscopeContent::THAI_DAYS — ค่านั้นอยู่บน *Campaign*
            //    (undefined constant = Error ไม่ใช่ Exception → หลุด catch ที่ดักแค่ Exception)
            //    และตารางนั้นมี 7 ช่อง ไม่ครอบ birth_day = 7 (พุธกลางคืน)
            $dayLabel = fn ($c) => 'วัน'.($c->birth_day_name ?: $c->birth_day);
            $failedLabel = implode(', ', $failed->map($dayLabel)->all());

            $this->line("  ❌ บทความเลนโพส : ล้ม {$failed->count()} ใบ ({$failedLabel})");
            $this->line('     ผลกระทบ: แชทของวันเกิดนั้นยิง AI เอง → ได้คนละใบกับโพส');

            if (! $heal) {
                $problems[] = $alertProblems[] = "บทความเลนโพสของ {$today} ล้ม {$failed->count()} ใบ ({$failedLabel})"
                    .' — แชทจะได้คำทำนายคนละใบกับโพส';
                $this->line('     แก้: <fg=yellow>php artisan fortune:daily-preflight --heal</>');

                return;
            }

            $this->newLine();
            $this->warn("  🔧 กำลังสร้างซ้ำเฉพาะใบที่ล้ม ({$failedLabel})...");

            $service = app(FortuneHoroscopeService::class);
            $fixed = 0;
            $stillFailed = [];

            foreach ($failed as $content) {
                $campaign = $content->campaign;

                if (! $campaign) {
                    $stillFailed[] = $dayLabel($content).' (ไม่พบแคมเปญ)';

                    continue;
                }

                try {
                    // 🪞 เมธอดนี้เรียก mirror() ให้เองหลังสำเร็จ ⇒ เลนแชทถูกทับให้ตรงทันที
                    $service->generateForBirthDay(
                        $campaign,
                        Carbon::parse((string) $content->target_date),
                        (int) $content->birth_day
                    );
                    $fixed++;
                } catch (Throwable $e) {
                    $stillFailed[] = $dayLabel($content);
                    Log::error('🩺 daily-preflight: ซ่อมบทความเลนโพสไม่สำเร็จ', [
                        'content_id' => $content->id,
                        'birth_day' => $content->birth_day,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->newLine();

            if ($stillFailed === []) {
                $this->line("  ✅ บทความเลนโพส : กู้คืนครบ {$fixed} ใบ — แชทตรงกับโพสแล้ว");

                Log::warning('🩺 daily-preflight: กู้บทความเลนโพสสำเร็จ', [
                    'date' => $today,
                    'fixed' => $fixed,
                ]);

                return;
            }

            $remain = implode(', ', $stillFailed);
            $problems[] = $alertProblems[] = "บทความเลนโพสของ {$today} ยังล้ม ".count($stillFailed)." ใบ ({$remain})"
                .' — แชทของวันเกิดนั้นจะได้คำทำนายคนละใบกับโพส';
            $this->line("  ❌ บทความเลนโพส : กู้ได้ {$fixed} ใบ · ยังล้ม {$remain}");
        } catch (Throwable $e) {
            // ยามล้มต้องไม่ทำให้ด่านอื่นของ preflight ตายตาม
            $this->error('  ⚠️ ตรวจบทความเลนโพสไม่สำเร็จ: '.$e->getMessage());
            Log::error('🩺 daily-preflight: ตรวจเลนโพสล้ม (non-blocking)', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 🪞 คัดลอกบทความเลนโพสที่สำเร็จแล้วลงเลนแชทซ้ำ — ฟรี ไม่กิน AI
     *
     * รันเสมอ (ไม่ต้องรอ --heal) เพราะไม่มีผลข้างเคียงและไม่มีต้นทุน
     * `mirror()` เป็น updateOrCreate ⇒ idempotent
     *
     * ปิดช่องของ race ย้อนหลัง: เลน B ที่เขียนทับ mirror ไปแล้วจะถูกทับกลับให้ตรงกับโพส
     */
    protected function remirrorGeneratedArticles(string $today): void
    {
        try {
            $generated = FortuneHoroscopeContent::whereDate('target_date', $today)
                ->where('status', FortuneHoroscopeContent::STATUS_GENERATED)
                ->whereNotNull('ai_prediction')
                ->orderBy('birth_day')
                ->get();

            if ($generated->isEmpty()) {
                return;
            }

            $mirror = app(DailyArticleMirror::class);
            $count = 0;

            foreach ($generated as $content) {
                if ($mirror->mirror($content) !== null) {
                    $count++;
                }
            }

            if ($count > 0) {
                $this->line("  🪞 คัดลอกโพส→แชท : {$count}/{$generated->count()} ใบ (กันเลนแชทเขียนทับ)");
            }
        } catch (Throwable $e) {
            $this->error('  ⚠️ คัดลอกโพส→แชทไม่สำเร็จ: '.$e->getMessage());
            Log::error('🩺 daily-preflight: re-mirror ล้ม (non-blocking)', ['error' => $e->getMessage()]);
        }
    }

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
