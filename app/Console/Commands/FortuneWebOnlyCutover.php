<?php

namespace App\Console\Commands;

use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortunePackageOffer;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\TakeoverSendGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🌐 (2026-11-03) ตัดรอบ FB → เว็บจันทรา (T0) — ล้างของค้างบน FB ที่จะพาลูกค้ากลับไปออกบิลในแชท
 *
 * ค่าเริ่มต้น = dry-run (นับให้ดูอย่างเดียว ไม่เขียนอะไรเลย) — ต้องใส่ --execute ถึงจะลงมือ
 *
 * --execute ทำ 2 อย่าง (design 1.5):
 *   1. เพิกถอนสิทธิ์ "ขอดูในแชท FB": fb_fallback_granted_at = NULL, transfer_attempts = 0 (เฉพาะลูกค้า FB)
 *   2. ปิด soft state ที่ยังไม่จ่าย (new / awaiting_confirmation / basic_done / tier_choice / discovery_* /
 *      free_predicted / collecting_* ที่ยังไม่จ่าย) เป็น completed + cancellation_reason = moved_to_web
 *      + ล้าง cache ที่จะพาข้อความถัดไปไปออกบิล (force_tier, pending_offer, consent, เมนูแพ็กเกจ)
 *
 * ห้ามแตะเด็ดขาด: แถว is_paid=1 · ลูกค้าที่แอดมินเทคโอเวอร์อยู่ · ลูกค้าที่มีของจ่ายแล้วกำลังส่ง (ระดับ A) ·
 *   บิลค้างจ่าย (ปล่อยหมดอายุเอง ≤180 นาที สลิปใช้ระดับ B) · บิลที่พักยอดขาด (partial_hold_at) · LINE / Telegram · บิลจันทรา
 *
 * --refresh-profile: ตั้งเมนู Messenger ใหม่ทุกเพจ FB (ใช้หลังเปิดธงแล้วเท่านั้น — ธงปิดจะได้เมนูเก่าที่มีราคา)
 *   ตั้งซ้ำได้ไม่เสียหาย จึงทำทันทีโดยไม่ต้อง --execute (เว้นแต่ใส่ --dry-run) และไม่แตะข้อมูลลูกค้า
 *
 * ใช้: php artisan fortune:web-only-cutover                 # ดูตัวเลขก่อน
 *      php artisan fortune:web-only-cutover --execute       # ลงมือ (ถามยืนยันก่อน, --force ข้ามคำถาม)
 *      php artisan fortune:web-only-cutover --refresh-profile
 */
class FortuneWebOnlyCutover extends Command
{
    protected $signature = 'fortune:web-only-cutover
        {--dry-run : ดูตัวเลขอย่างเดียว (ค่าเริ่มต้น — ชนะ --execute เสมอ)}
        {--execute : ลงมือจริง (เพิกถอน fallback + ปิด soft state)}
        {--force : ไม่ต้องถามยืนยันก่อน --execute}
        {--days=7 : ปิดเฉพาะ soft state ที่ขยับภายใน N วัน (แถวเก่ากว่านี้ด่านขาเข้าปิดให้เองตอนลูกค้าทักมา)}
        {--refresh-profile : ตั้งเมนู Messenger ใหม่ทุกเพจ FB (ต้องเปิดธงแล้ว · ทำซ้ำได้ ไม่ต้องใส่ --execute)}';

    protected $description = 'ตัดรอบ FB web-only: เพิกถอนสิทธิ์ดูในแชท FB + ปิด soft state ที่ยังไม่จ่าย (ค่าเริ่มต้น dry-run)';

    /** เหตุผลยกเลิกที่ใช้ร่วมกับด่านขาเข้า (WebOnlyGateTrait::WEB_ONLY_CANCEL_REASON) */
    public const CANCEL_REASON = 'moved_to_web';

    /** soft state ที่ปิดได้ (ตรงกับ WebOnlyGateTrait::WEB_ONLY_SOFT_STATUSES) — ทุกแถวต้อง is_paid=0 ด้วย */
    public const SOFT_STATUSES = [
        FortuneReading::STATUS_NEW,
        FortuneReading::STATUS_AWAITING_CONFIRMATION,
        FortuneReading::STATUS_BASIC_DONE,
        FortuneReading::STATUS_TIER_CHOICE,
        FortuneReading::STATUS_DISCOVERY_CHAT,
        FortuneReading::STATUS_DISCOVERY_CONFIRM,
        FortuneReading::STATUS_FREE_PREDICTED,
        FortuneReading::STATUS_COLLECTING_BIRTHDATE,
        FortuneReading::STATUS_COLLECTING_QUESTIONS,
        FortuneReading::STATUS_COLLECTING_TAROT,
    ];

    /** ขนาด chunk ตอนไล่แถว */
    protected const CHUNK = 500;

    public function handle(): int
    {
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();
        $mode = new FortuneBotMode($settings);

        // dry-run ชนะเสมอ — พิมพ์ทั้งสองตัวมา = ถือว่ายังไม่แน่ใจ
        $execute = (bool) $this->option('execute') && ! (bool) $this->option('dry-run');
        $days = max(1, (int) $this->option('days'));

        $this->newLine();
        $this->line('🌐  <options=bold>ตัดรอบ FB WEB-ONLY</> — '.($execute ? '<fg=red>EXECUTE (เขียนจริง)</>' : '<fg=green>DRY-RUN (ไม่เขียนอะไร)</>'));
        $this->line(str_repeat('─', 70));
        $this->line('ธง facebook_web_only: '.((bool) ($settings->facebook_web_only ?? false) ? 'เปิด' : 'ปิด (ตัดรอบก่อนเปิดธงได้ตามขั้นตอน)'));

        // ── รวบรวมตัวเลข (อ่านอย่างเดียว) ──
        $fallbackIds = $this->fallbackCreditIds();
        [$softRows, $skipped] = $this->closableSoftReadings($mode, $days);

        $this->newLine();
        $this->table(['รายการ', 'จำนวน', 'การจัดการ'], [
            ['สิทธิ์ "ขอดูในแชท FB" (fortune_user_credits)', count($fallbackIds), 'เพิกถอน → NULL + transfer_attempts=0'],
            ['soft state ที่ยังไม่จ่าย (ขยับใน '.$days.' วัน)', count($softRows), 'ปิด → completed / '.self::CANCEL_REASON],
            ['  ↳ ข้าม: แอดมินเทคโอเวอร์อยู่', $skipped['takeover'], 'ไม่แตะ'],
            ['  ↳ ข้าม: มีของจ่ายแล้วกำลังส่ง (ระดับ A)', $skipped['legacy_paid'], 'ไม่แตะ'],
            ['บิลค้างจ่าย (pending)', $this->countPendingBills(), 'ไม่แตะ — หมดอายุเอง'],
            ['จ่ายแล้วยังส่งไม่ครบ / is_paid=1', $this->countPaidOpen(), 'ไม่แตะ — ส่งต่อบน FB จนจบ'],
        ]);

        if ($this->option('refresh-profile')) {
            // ตั้งเมนูซ้ำได้ไม่เสียหาย (idempotent) — ทำเลยเว้นแต่สั่ง --dry-run ตรง ๆ
            $this->refreshProfiles($mode, ! (bool) $this->option('dry-run'));
        }

        if (! $execute) {
            $this->newLine();
            $this->info('DRY-RUN เท่านั้น — ไม่มีการเขียนข้อมูล · ลงมือจริงด้วย --execute');

            return self::SUCCESS;
        }

        if ($fallbackIds === [] && $softRows === []) {
            $this->info('ไม่มีอะไรต้องทำ');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm('ยืนยันเพิกถอน '.count($fallbackIds).' สิทธิ์ และปิด '.count($softRows).' soft state ใช่ไหม? (ย้อนกลับไม่ได้)', false)) {
            $this->warn('ยกเลิก — ไม่มีการเขียนข้อมูล');

            return self::FAILURE;
        }

        $revoked = $this->revokeFallback($fallbackIds);
        $closed = $this->closeSoftReadings($softRows);

        Log::warning('🌐 FB web-only cutover: ลงมือแล้ว', [
            'revoked_fallback' => $revoked,
            'closed_soft_state' => $closed,
            'days' => $days,
            'skipped' => $skipped,
        ]);

        $this->newLine();
        $this->info("✅ เพิกถอนสิทธิ์ดูในแชท FB {$revoked} แถว · ปิด soft state {$closed} แถว (".self::CANCEL_REASON.')');
        $this->line('ขั้นต่อไป: เปิดธง facebook_web_only ในหน้าแอดมิน → fortune:web-only-cutover --refresh-profile');

        return self::SUCCESS;
    }

    // ── อ่านอย่างเดียว ───────────────────────────────────────────

    /**
     * id แถว fortune_user_credits ของลูกค้า FB ที่ยังถือสิทธิ์ดูในแชท/ตัวนับความพยายาม
     *
     * กรองด้วย resolvePlatform ทีละแถว — แถวเก่าที่ platform ว่างแต่เป็น id LINE/Telegram ห้ามโดน
     *
     * @return array<int, int>
     */
    protected function fallbackCreditIds(): array
    {
        $ids = [];

        DB::table('fortune_user_credits')
            ->whereRaw("COALESCE(platform, 'facebook') = 'facebook'")
            ->where(fn ($q) => $q->whereNotNull('fb_fallback_granted_at')->orWhere('transfer_attempts', '>', 0))
            ->select(['id', 'facebook_user_id', 'platform'])
            ->orderBy('id')
            ->chunk(self::CHUNK, function ($rows) use (&$ids) {
                foreach ($rows as $row) {
                    if (FortuneBotMode::resolvePlatform($row->platform ?? 'facebook', (string) $row->facebook_user_id)
                        === FortuneBotMode::FB_WEB_ONLY_PLATFORM) {
                        $ids[] = (int) $row->id;
                    }
                }
            });

        return $ids;
    }

    /**
     * soft state ของ FB ที่ปิดได้ + สถิติแถวที่ข้าม
     *
     * @return array{0: array<int, FortuneReading>, 1: array{takeover:int, legacy_paid:int}}
     */
    protected function closableSoftReadings(FortuneBotMode $mode, int $days): array
    {
        $rows = FortuneReading::query()
            ->whereRaw("COALESCE(platform, 'facebook') = 'facebook'")
            ->withoutJuntra()
            ->where('is_paid', false)
            ->whereNull('partial_hold_at')
            ->whereIn('conversation_status', self::SOFT_STATUSES)
            ->where('updated_at', '>=', now()->subDays($days))
            // เทคโอเวอร์บนแถวนี้เอง = ไม่แตะ
            ->where(fn ($q) => $q->whereNull('admin_takeover_until')->orWhere('admin_takeover_until', '<=', now()))
            ->orderBy('id')
            ->get();

        $userOf = fn (FortuneReading $r) => (string) ($r->platform_user_id ?: $r->facebook_user_id);

        // ทิ้งแถวที่ id ไม่ใช่ของ FB (แถวเก่า platform ว่างแต่เป็น LINE/Telegram)
        $rows = $rows->filter(fn (FortuneReading $r) => $userOf($r) !== ''
            && FortuneBotMode::resolvePlatform($r->platform, $userOf($r), $r) === FortuneBotMode::FB_WEB_ONLY_PLATFORM);

        $users = $rows->map($userOf)->unique()->values()->all();

        // เทคโอเวอร์ระดับลูกค้า (แถวอื่นของคนเดียวกัน) — คิวรีเดียวทั้งชุด
        try {
            $takenOver = TakeoverSendGuard::takenOverUserIds($users);
        } catch (\Throwable $e) {
            // เช็คไม่ได้ = ไม่ปิดอะไรเลย (ปลอดภัยกว่าไปปิดแถวที่แอดมินคุมอยู่)
            $this->warn('ตรวจเทคโอเวอร์ไม่ได้ — ข้ามการปิด soft state ทั้งหมด: '.$e->getMessage());

            return [[], ['takeover' => $rows->count(), 'legacy_paid' => 0]];
        }

        $legacyPaid = [];
        foreach ($users as $uid) {
            if (! isset($takenOver[$uid]) && $mode->fbLegacyPaidInFlight($uid)) {
                $legacyPaid[$uid] = true;
            }
        }

        $skipped = ['takeover' => 0, 'legacy_paid' => 0];
        $keep = [];
        foreach ($rows as $row) {
            $uid = $userOf($row);
            if (isset($takenOver[$uid])) {
                $skipped['takeover']++;

                continue;
            }
            if (isset($legacyPaid[$uid])) {
                $skipped['legacy_paid']++;

                continue;
            }
            $keep[] = $row;
        }

        return [$keep, $skipped];
    }

    protected function countPendingBills(): int
    {
        try {
            return FortuneReading::query()
                ->whereRaw("COALESCE(platform, 'facebook') = 'facebook'")
                ->withoutJuntra()
                ->where('is_paid', false)
                ->whereIn('conversation_status', FortuneFbWebOnlyPreflight::PENDING_BILL_STATUSES)
                ->where('updated_at', '>=', now()->subMinutes(FortuneReading::billTimeoutMinutes()))
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function countPaidOpen(): int
    {
        try {
            return FortuneReading::query()
                ->whereRaw("COALESCE(platform, 'facebook') = 'facebook'")
                ->withoutJuntra()
                ->where('is_paid', true)
                ->whereIn('conversation_status', FortuneFbWebOnlyPreflight::PAID_UNDELIVERED_STATUSES)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ── เขียนจริง (--execute เท่านั้น) ──────────────────────────

    /**
     * เพิกถอนสิทธิ์ดูในแชท FB (โค้ดฝั่งบอทไม่สนค่านี้อยู่แล้วใต้ธง — ล้างให้สะอาดเผื่อปิดธงชั่วคราว)
     */
    protected function revokeFallback(array $ids): int
    {
        $total = 0;
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            $total += DB::table('fortune_user_credits')
                ->whereIn('id', $chunk)
                ->update([
                    'fb_fallback_granted_at' => null,
                    'transfer_attempts' => 0,
                    'updated_at' => now(),
                ]);
        }

        return $total;
    }

    /**
     * ปิด soft state ทีละแถว — ล็อกแถวแล้วเช็คซ้ำ (ลูกค้าอาจจ่าย/ขยับสถานะระหว่างคำสั่งรัน)
     *
     * @param  array<int, FortuneReading>  $rows
     */
    protected function closeSoftReadings(array $rows): int
    {
        $closed = 0;
        $users = [];

        foreach ($rows as $row) {
            try {
                $done = DB::transaction(function () use ($row) {
                    $fresh = FortuneReading::query()->whereKey($row->id)->lockForUpdate()->first();

                    // เช็คซ้ำในล็อก — จ่ายแล้ว / ขยับไปสถานะอื่น / ถูกเทคโอเวอร์ / พักยอดขาด = ไม่แตะ
                    if (! $fresh
                        || (bool) $fresh->is_paid
                        || ! in_array($fresh->conversation_status, self::SOFT_STATUSES, true)
                        || $fresh->partial_hold_at !== null
                        || ($fresh->admin_takeover_until !== null && $fresh->admin_takeover_until->isFuture())) {
                        return false;
                    }

                    $state = is_array($fresh->conversation_state) ? $fresh->conversation_state : [];
                    $state['cancellation_reason'] = self::CANCEL_REASON;
                    $state['cancelled_at'] = now()->toIso8601String();
                    $state['web_only_cutover_from'] = $fresh->conversation_status;

                    $fresh->forceFill([
                        'conversation_status' => FortuneReading::STATUS_COMPLETED,
                        'conversation_state' => $state,
                    ])->save();

                    return true;
                });
            } catch (\Throwable $e) {
                Log::warning('🌐 FB web-only cutover: ปิดแถวไม่สำเร็จ', [
                    'reading_id' => $row->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($done) {
                $closed++;
                $users[(string) ($row->platform_user_id ?: $row->facebook_user_id)] = true;
            }
        }

        foreach (array_keys($users) as $uid) {
            $this->forgetBillingCache($uid);
        }

        return $closed;
    }

    /**
     * ล้าง cache ที่ทำให้ข้อความถัดไปกลายเป็นคำสั่งออกบิล (ชุดเดียวกับ WebOnlyGateTrait::closeWebOnlySoftState)
     */
    protected function forgetBillingCache(string $uid): void
    {
        foreach ([
            "fortune:force_tier:{$uid}",
            "fortune:pending_offer:{$uid}",
            "fortune:skip_payment_gate:{$uid}",
            'fortune:consent_pending:'.$uid,
            'fortune:consent_ok:'.$uid,
            'fortune:daily_dm_pending:'.FortuneBotMode::FB_WEB_ONLY_PLATFORM.':'.$uid,
        ] as $key) {
            try {
                Cache::forget($key);
            } catch (\Throwable $e) {
                // ไม่สำคัญพอจะหยุดคำสั่ง
            }
        }

        try {
            FortunePackageOffer::clear($uid);
        } catch (\Throwable $e) {
            // ไม่สำคัญพอจะหยุดคำสั่ง
        }
    }

    /**
     * ตั้งเมนู Messenger ใหม่ทุกเพจ FB — ธงปิดอยู่ = ไม่ทำ (จะได้เมนูเก่าที่มีราคา)
     */
    protected function refreshProfiles(FortuneBotMode $mode, bool $execute): void
    {
        $this->newLine();

        if (! $mode->fbWebOnly()) {
            $this->warn('--refresh-profile: ธง facebook_web_only ยังปิด — เปิดธงก่อนแล้วค่อยรันใหม่ (ไม่งั้นได้เมนูเก่าที่มีราคา)');

            return;
        }

        $pages = [null];
        try {
            foreach (FortunePage::query()->active()->get() as $page) {
                if (($page->platform ?? 'facebook') === 'facebook' && ! empty($page->page_access_token)) {
                    $pages[] = $page;
                }
            }
        } catch (\Throwable $e) {
            // ไม่มีตารางสาขา = เพจหลักอย่างเดียว
        }

        if (! $execute) {
            $this->info('--refresh-profile (dry-run): จะตั้งเมนูใหม่ '.count($pages).' เพจ — ไม่ใส่ --dry-run เพื่อลงมือ');

            return;
        }

        foreach ($pages as $page) {
            $name = $page ? 'เพจ '.$page->display_label.' (#'.$page->id.')' : 'เพจหลัก';

            try {
                $result = FortunePageContext::run($page, function () {
                    return (new FacebookWebhookService(FortuneTellingSetting::getSettings()))->setupMessengerProfile();
                });
            } catch (\Throwable $e) {
                $result = ['success' => false, 'message' => $e->getMessage()];
            }

            if ($result['success'] ?? false) {
                $this->info("✅ ตั้งเมนู Messenger ใหม่: {$name}");
            } else {
                $this->error("❌ ตั้งเมนูไม่สำเร็จ: {$name} — ".($result['message'] ?? 'ไม่ทราบสาเหตุ'));
            }
        }
    }
}
