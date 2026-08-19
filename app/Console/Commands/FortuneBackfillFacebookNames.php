<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * กู้ชื่อลูกค้า Facebook ที่บิลบันทึกไว้เป็น "คุณ" / ว่าง / code pattern
 *
 * 🪪 (2026-08-19) ที่มา: บิล FTU-260819-Z4534 — แอดมินเปิดบิลแล้วไม่เห็นชื่อลูกค้า
 *   `GET /{PSID}` ของ Graph คืน 400 "does not exist / missing permissions" เฉพาะ "บางบัญชี"
 *   (prod 30 วัน พัง 55 จาก 1,304 = 4.2% · สะสมทั้งหมด 754 บิล / 653 คน / 26 บิลจ่ายเงินแล้ว)
 *
 *   ⚠️ ไม่ใช่ token หมดอายุ ไม่ใช่ App Review — token+เพจเดียวกัน resolve คนอื่นได้ปกติ 96%
 *   ทางออก: `FacebookWebhookService::fetchNameViaConversations()` (conversations API)
 *
 * @example
 *   php artisan fortune:backfill-fb-names --dry            # ดูก่อนว่าจะแก้ใคร
 *   php artisan fortune:backfill-fb-names --paid-first     # ลูกค้าที่จ่ายเงินแล้วก่อน
 *   php artisan fortune:backfill-fb-names --limit=50       # รอบเล็ก ๆ (cron ใช้ค่านี้)
 *   php artisan fortune:backfill-fb-names --user=278514... # เจาะรายคน
 *
 * @tip endpoint conversations กินโควต้า rate limit มากกว่า profile API
 *      → มี --sleep คั่นทุกคนเสมอ อย่าปิด (ดู [[rule_fb_page_needs_subscribed_apps]])
 */
class FortuneBackfillFacebookNames extends Command
{
    protected $signature = 'fortune:backfill-fb-names
                            {--limit=200 : จำนวนลูกค้าสูงสุดต่อรอบ}
                            {--paid-first : เรียงลูกค้าที่จ่ายเงินแล้วขึ้นก่อน}
                            {--days= : จำกัดเฉพาะบิลที่สร้างภายใน N วันล่าสุด}
                            {--user= : เจาะ PSID เดียว}
                            {--sleep=400 : หน่วงระหว่างคน (มิลลิวินาที) กัน rate limit}
                            {--dry : ดูอย่างเดียว ไม่เขียน DB}';

    protected $description = 'กู้ชื่อลูกค้า Facebook ที่หายไป (บิลขึ้น "คุณ") ผ่าน conversations API';

    /**
     * ค่าที่ถือว่า "ไม่ใช่ชื่อคนจริง" — ต้องตรงกับ FortuneReading::isHumanLikeName()
     *
     * 🚨 กับดัก: `facebook_user_name` ของเคสนี้ **ไม่ใช่ NULL** แต่เป็นสตริง `"คุณ"`
     *   ใครใช้ whereNull() อย่างเดียวจะได้ผลลัพธ์ 0 แถวแล้วสรุปผิดว่า "ไม่มีปัญหา"
     */
    protected const CODE_NAME_REGEXP = '^(FACEBOOK|LINE|FB|TG|TELEGRAM|MESSENGER|IG|INSTAGRAM)-';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $sleepMs = max(0, (int) $this->option('sleep'));

        $targets = $this->findTargets();

        if ($targets->isEmpty()) {
            $this->info('✅ ไม่มีบิลที่ชื่อลูกค้าหาย — ไม่ต้องทำอะไร');

            return self::SUCCESS;
        }

        $this->info('🔍 พบลูกค้าที่ต้องกู้ชื่อ '.$targets->count().' คน'.($dry ? ' (DRY RUN)' : ''));

        $ok = 0;
        $fail = 0;
        $rowsFixed = 0;

        foreach ($targets as $t) {
            $psid = (string) $t->platform_user_id;
            $pageId = $this->resolvePageId((int) $t->last_id);

            // 🏬 ต้อง bind สาขาก่อนทุกคน — PSID เป็น page-scoped
            //    ถ้าใช้ token ค้างของเพจคนก่อนหน้า = Graph 400 ทั้งที่ชื่อมีอยู่จริง
            FortunePageContext::bindFromId($pageId);
            $service = new FacebookWebhookService(FortuneTellingSetting::getSettings());

            $found = $service->fetchNameViaConversations($psid);
            $name = $found['name'] ?? null;

            if (! $name) {
                $fail++;
                $this->line("  ✗ {$psid} (page {$pageId}) — ยังหาชื่อไม่ได้");
                $this->throttle($sleepMs);

                continue;
            }

            $ok++;
            $this->line("  ✓ {$psid} (page {$pageId}) → {$name} [{$found['name_source']}]");

            if (! $dry) {
                $rowsFixed += $this->persistName($psid, $name);
            }

            $this->throttle($sleepMs);
        }

        FortunePageContext::forget();

        $this->newLine();
        $this->info("สรุป: กู้ได้ {$ok} คน · ยังไม่ได้ {$fail} คน".($dry ? ' (DRY RUN — ไม่ได้เขียน DB)' : " · อัปเดตบิล {$rowsFixed} ใบ"));

        return self::SUCCESS;
    }

    /**
     * หา PSID ที่ชื่อหาย (1 แถว = 1 คน) พร้อมสาขาล่าสุดที่คนนั้นคุยด้วย
     */
    protected function findTargets(): \Illuminate\Support\Collection
    {
        $q = DB::table('fortune_readings')
            ->where('platform', 'facebook')
            ->whereNotNull('platform_user_id')
            ->where('platform_user_id', '!=', '')
            ->where($this->missingNameClause(...));

        if ($psid = $this->option('user')) {
            $q->where('platform_user_id', $psid);
        }

        if ($days = $this->option('days')) {
            $q->where('created_at', '>=', now()->subDays((int) $days));
        }

        // 1 คน 1 แถว
        // ⚠️ ห้ามใช้ MAX(fortune_page_id) — ถ้าลูกค้าเคยคุย 2 เพจ MAX จะได้ "เพจที่ id สูงกว่า"
        //    ไม่ใช่ "เพจล่าสุด" → bind token ผิดเพจ → Graph 400 ทั้งที่ชื่อมีอยู่จริง
        //    เอา page จากแถว last_id ตอนวนลูปแทน (ดู resolvePageId)
        $q->selectRaw('platform_user_id, MAX(id) as last_id, MAX(is_paid) as ever_paid')
            ->groupBy('platform_user_id');

        if ($this->option('paid-first')) {
            $q->orderByDesc('ever_paid');
        }

        return $q->orderByDesc('last_id')
            ->limit((int) $this->option('limit'))
            ->get();
    }

    /**
     * สาขาที่ลูกค้าคุยด้วย "ครั้งล่าสุด" — PSID เป็น page-scoped ต้อง bind ให้ตรงก่อนยิง Graph
     */
    protected function resolvePageId(int $lastReadingId): ?int
    {
        $pageId = DB::table('fortune_readings')
            ->where('id', $lastReadingId)
            ->value('fortune_page_id');

        return $pageId ? (int) $pageId : null;
    }

    /**
     * เงื่อนไข "ชื่อนี้ใช้ไม่ได้" — ใช้ร่วมกันทั้งตอนค้นและตอนเขียน
     */
    protected function missingNameClause($q): void
    {
        $q->whereNull('facebook_user_name')
            ->orWhere('facebook_user_name', '')
            ->orWhere('facebook_user_name', 'คุณ')
            ->orWhere('facebook_user_name', 'ลูกค้า')
            ->orWhere('facebook_user_name', 'เจ้าชะตา')
            ->orWhere('facebook_user_name', 'REGEXP', self::CODE_NAME_REGEXP);
    }

    /**
     * เขียนชื่อกลับทั้งบิลเก่าและ credit (credit = ที่พึ่งของบิลใหม่ในอนาคต)
     *
     * @return int จำนวนบิลที่อัปเดต
     */
    protected function persistName(string $psid, string $name): int
    {
        // 🚨 ต้องเป็น DB::table() ห้ามใช้ Eloquent mass update — เด็ดขาด
        //   Eloquent update() จะ touch `updated_at` ของทุกแถวที่แก้ (754 บิลย้อนหลัง)
        //   แล้ว cron ครึ่งระบบตัดสินใจจาก updated_at:
        //     - FortuneExpireConversations: `updated_at < now()-billTimeout` → บิลตายเมื่อ 3 เดือนก่อน
        //       จะกลายเป็น "เพิ่งคุย" แล้ว **รอดจากการหมดอายุ** = บิลผีคืนชีพไปทวงเงินลูกค้า
        //     - FortuneCelticRedeliver: `updated_at > now()-2h` → ส่งคำทำนายเก่าซ้ำให้ลูกค้า
        //     - FortuneCelticAutoFinalize / PickVoiceNudge: ปลุกบทสนทนาที่จบไปแล้ว
        //   การเติมชื่อไม่ใช่ "กิจกรรมของบิล" → ห้ามขยับนาฬิกาของบิล
        $rows = DB::table('fortune_readings')
            ->where('platform', 'facebook')
            ->where(function ($q) use ($psid) {
                $q->where('platform_user_id', $psid)
                    ->orWhere('facebook_user_id', $psid);
            })
            ->where($this->missingNameClause(...))
            ->update(['facebook_user_name' => $name]);

        // ⭐ credit สำคัญกว่าบิล — resolveCustomerName() ใช้เป็นชั้นที่ 3
        //    ถ้าไม่เขียนตรงนี้ บิล "ใบต่อไป" ของคนเดิมจะกลับไปเป็น "คุณ" อีก
        try {
            DB::table('fortune_user_credits')
                ->where('facebook_user_id', $psid)
                ->where('platform', 'facebook')
                ->where($this->missingNameClause(...))
                ->update(['facebook_user_name' => $name]);
        } catch (\Throwable $e) {
            $this->warn("    ⚠ เขียน credit ไม่สำเร็จ ({$psid}): ".$e->getMessage());
        }

        return $rows;
    }

    protected function throttle(int $ms): void
    {
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }
}
