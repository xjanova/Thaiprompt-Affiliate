<?php

namespace App\Services\Fortune;

use App\Models\FortuneNavFloodStrike;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneBanService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🚦 NavFloodGuard — เบรก / เตือน / ระงับ 7 วัน สำหรับพฤติกรรม "กดปุ่มรัว"
 *
 * เคสจริงที่ทำให้ต้องมีคลาสนี้ (prod 2026-08-21):
 *   PSID 26463023433375768 ("สุวรรณ") คอมเมนต์ใต้โพส → บอท DM ชวนดูดวง
 *   แล้วกดปุ่ม DAILY_BDAY_1 รัว 10+ ครั้งใน 2 นาที (ห่างกัน 8-9 วินาที)
 *   บอทส่งดวงรายวัน 2,611 ตัวอักษรกลับทุกครั้ง = ขาออก 20+ ข้อความ
 *   คนเดียวกินโควตาส่งของเพจไป 26% ใน 1 ชั่วโมง — รูปแบบเดียวกับที่เคยทำให้เพจโดน #2022/#551
 *
 * ทำไมด่านเดิมจับไม่ได้: `isUserSpamming()` อ่านแต่ `$text`/`$attachments` และถูกเรียกจาก
 * สายข้อความจุดเดียว — การกดปุ่มไม่เคยถูกนับที่ไหนเลย ต่อให้กด 1,000 ครั้ง ตัวเลขยังเป็น 0
 *
 * ขั้นบันได:
 *   ขั้น 0  เบรกเงียบ  ปุ่มเดิมซ้ำใน N วินาที        → ไม่ตอบ (ไม่นับความผิด)
 *   ขั้น 1  เตือน      ปุ่มเดิม ≥4 ครั้ง/2 นาที       → เตือน + เงียบ 5 นาที + strike 1
 *           หรือ       ปุ่มใดก็ได้ ≥15 ครั้ง/5 นาที
 *   ขั้น 2  เตือนสุดท้าย แตะเกณฑ์อีกใน 24 ชม.        → เตือน (บอกชัดว่าครั้งหน้าระงับ) + strike 2
 *   ขั้น 3  ระงับ      แตะเกณฑ์ครั้งที่ 3 ใน 24 ชม.  → FortuneBanService::ban(7 วัน)
 *
 * 🚨 กติกาที่ห้ามพลาด
 *   1. **ลูกค้าจ่ายเงินแล้วยกเว้นทุกขั้น** และล้าง strike ที่ค้างทิ้ง
 *   2. **idempotent ต่อ request** — postback default ไหลต่อไป handleQuickReply
 *      ถ้าไม่กันจะนับ 2 เด้งต่อการกด 1 ครั้ง = ลูกค้าปกติโดนแบนที่ครึ่งเกณฑ์
 *   3. **ปุ่มที่ตั้งใจให้กดซ้ำต้อง whitelist** — คนจ่ายเงินแล้วกด "อ่านคำทำนาย" ซ้ำ 5 ครั้ง
 *      แล้วโดนปิดปาก = หายนะ (ยกเว้นเฉพาะกฎปุ่มเดิม ยังนับกฎรวมอยู่)
 *   4. **ตัวนับความถี่ต้องเป็น list ของ timestamp** ห้ามใช้ counter + Cache::put ต่ออายุ TTL
 *      แบบ line_flood ไม่งั้นตัวนับไม่มีวันหมดอายุสำหรับคนที่กดถี่ต่อเนื่อง
 *   5. **strike อยู่ DB** — deploy.sh รัน cache:clear ทุกครั้ง
 *   6. **ห้ามแบนถาวรอัตโนมัติ** — ส่งหน่วยเป็นนาทีเสมอ (null = ถาวร)
 */
class NavFloodGuard
{
    public const ACTION_PASS = 'pass';

    public const ACTION_SILENT = 'silent';

    public const ACTION_WARN = 'warn';

    public const ACTION_BANNED = 'banned';

    /** หน้าต่างสะสมความผิด — กดปุ่มรัวเป็นพฤติกรรมชั่ววูบ ไม่ควรสะสมข้ามหลายวัน */
    protected const STRIKE_WINDOW_HOURS = 24;

    /** ครบกี่ strike ถึงระงับ (ลอกจาก BillTrollGuardService::MAX_STRIKES ที่ใช้จริงแล้ว) */
    protected const MAX_STRIKES = 3;

    /**
     * ปุ่มที่ "ตั้งใจให้กดซ้ำ" — ยกเว้นเฉพาะกฎปุ่มเดิม (ยังนับกฎรวมอยู่)
     *
     * เทียบแบบขึ้นต้นด้วย เพื่อครอบปุ่มที่มีเลขต่อท้าย (เช่น CELTIC_PICK_3)
     *
     * @var array<int, string>
     */
    protected const REPEAT_SAFE_PREFIXES = [
        'READ_PREDICTION',      // คนแก่กดอ่านคำทำนายซ้ำเป็นเรื่องปกติ
        'REPORT_PAYMENT',       // แจ้งโอนซ้ำเพราะไม่แน่ใจว่าติดไหม
        'SHOW_BANK_ACCOUNT',    // เปิดดูเลขบัญชีซ้ำระหว่างโอน
        'CANCEL',               // ยกเลิกทุกชนิด
        'CELTIC_PICK',          // เปิดไพ่ทีละใบ = ต้องกดหลายครั้งโดยธรรมชาติ
        'MY_BILLS',
        'CHECK_STATUS',
    ];

    /** memo กันนับซ้ำภายใน request เดียว: "platform:user:payload" => ผลลัพธ์ */
    protected static array $seen = [];

    /** instance เดียวทั้งโปรเซส — การสร้างใหม่ทุกครั้งแพงกว่าตัวงานเอง */
    protected static ?\App\Services\FortuneConversationService $fcs = null;

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจการกดปุ่ม 1 ครั้ง
     *
     * @param  string  $platform  facebook | line
     * @param  string  $userId  PSID / LINE userId
     * @param  string  $payload  payload ของปุ่ม (หรือ action ฝั่ง LINE)
     * @param  string|null  $displayName  ชื่อลูกค้า (snapshot ไว้ดูย้อนหลัง)
     * @return array{action:string,message:?string}
     */
    public function check(string $platform, string $userId, string $payload, ?string $displayName = null): array
    {
        $pass = ['action' => self::ACTION_PASS, 'message' => null];

        if ($userId === '') {
            return $pass;
        }

        try {
            if (! (bool) ($this->settings->enable_nav_flood_guard ?? false)) {
                return $pass;
            }

            // ⛔ กันนับซ้ำภายใน request เดียว (postback → handleQuickReply)
            $seenKey = $platform.':'.$userId.':'.$payload;
            if (array_key_exists($seenKey, self::$seen)) {
                return self::$seen[$seenKey];
            }

            // 💰 ลูกค้าจ่ายเงินแล้ว — ยกเว้นทุกขั้น + ล้างประวัติที่ค้าง
            if ($this->isPaidCustomer($platform, $userId)) {
                $this->clearStrikes($platform, $userId);

                return self::$seen[$seenKey] = $pass;
            }

            $result = $this->evaluate($platform, $userId, $payload, $displayName);

            // 👁️ shadow mode — คำนวณครบ เขียน log ครบ แต่ไม่บล็อกใคร
            if ($this->mode() !== 'enforce' && $result['action'] !== self::ACTION_PASS) {
                Log::info('NavFloodGuard: would_'.$result['action'], [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'payload' => $payload,
                ]);

                return self::$seen[$seenKey] = $pass;
            }

            return self::$seen[$seenKey] = $result;
        } catch (\Throwable $e) {
            // ด่านเสริมพัง ต้องไม่ทำให้ปุ่มตายทั้งระบบ
            Log::warning('NavFloodGuard: ด่านล้ม (ปล่อยผ่าน)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $pass;
        }
    }

    /**
     * ล้าง memo ระหว่างงาน — ต้องเรียกที่ต้น job ของ queue worker
     * (worker เป็นโปรเซสรันยาว memo ของงานก่อนหน้าจะค้าง)
     */
    public static function flushSeen(): void
    {
        self::$seen = [];
        self::$fcs = null;
    }

    /**
     * แกนตัดสิน — แยกออกมาให้ทดสอบง่ายและให้ shadow mode ห่อได้
     *
     * @return array{action:string,message:?string}
     */
    protected function evaluate(string $platform, string $userId, string $payload, ?string $displayName): array
    {
        $now = time();

        // ── ขั้น 0: เบรกเงียบ (ไม่นับความผิด)
        //    คนแก่กดนิ้วลั่น 2 ครั้งติดเป็นเรื่องปกติ ปุ่ม template ค้างบนแชทถาวรด้วย
        $lockSec = (int) ($this->settings->nav_flood_same_payload_lock_sec ?? 25);
        $lockKey = 'fortune:nav:lock:'.$platform.':'.$userId.':'.md5($payload);

        $lockPassed = $lockSec <= 0 || Cache::add($lockKey, true, $lockSec);

        // ── นับความถี่ (list ของ timestamp — ห้ามใช้ counter ที่ต่ออายุ TTL)
        $repeatWindow = max(5, (int) ($this->settings->nav_flood_repeat_window_sec ?? 120));
        $rateWindow = max(5, (int) ($this->settings->nav_flood_rate_window_sec ?? 300));

        $repeatCount = $this->bump(
            'fortune:nav:repeat:'.$platform.':'.$userId.':'.md5($payload),
            $repeatWindow,
            $now
        );
        $rateCount = $this->bump(
            'fortune:nav:rate:'.$platform.':'.$userId,
            $rateWindow,
            $now
        );

        $repeatMax = max(2, (int) ($this->settings->nav_flood_repeat_max ?? 4));
        $rateMax = max(3, (int) ($this->settings->nav_flood_rate_max ?? 15));

        $repeatTripped = ! $this->isRepeatSafe($payload) && $repeatCount >= $repeatMax;
        $rateTripped = $rateCount >= $rateMax;

        if (! $repeatTripped && ! $rateTripped) {
            if (! $lockPassed) {
                Log::info('NavFloodGuard: เบรกเงียบ (ปุ่มเดิมซ้ำเร็วเกิน)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'payload' => $payload,
                    'lock_sec' => $lockSec,
                ]);

                return ['action' => self::ACTION_SILENT, 'message' => null];
            }

            return ['action' => self::ACTION_PASS, 'message' => null];
        }

        // ── แตะเกณฑ์แล้ว → บวก strike (1 ครั้งต่อหน้าต่างคูลดาวน์)
        return $this->applyStrike(
            $platform,
            $userId,
            $payload,
            $displayName,
            $repeatTripped ? "ปุ่มเดิม {$repeatCount} ครั้ง/{$repeatWindow}วิ" : "ปุ่มรวม {$rateCount} ครั้ง/{$rateWindow}วิ"
        );
    }

    /**
     * บวก strike + ตัดสินว่าจะเตือนหรือระงับ
     *
     * @return array{action:string,message:?string}
     */
    protected function applyStrike(
        string $platform,
        string $userId,
        string $payload,
        ?string $displayName,
        string $reason
    ): array {
        if (! Schema::hasTable('fortune_nav_flood_strikes')) {
            // ช่วง deploy ที่โค้ดขึ้นก่อน migrate — เบรกเงียบไปก่อน ดีกว่าปล่อยยิงต่อ
            return ['action' => self::ACTION_SILENT, 'message' => null];
        }

        $cooldownMin = max(1, (int) ($this->settings->nav_flood_cooldown_minutes ?? 5));

        // 🔒 นับความผิด 1 ครั้งต่อคูลดาวน์ ไม่ใช่ทุกครั้งที่กด
        //    ไม่งั้นกดรัว 10 ครั้ง = 10 strikes = ระงับทันทีโดยไม่มีโอกาสได้เห็นคำเตือน
        $strikeLock = 'fortune:nav:struck:'.$platform.':'.$userId;
        $isNewStrike = Cache::add($strikeLock, true, $cooldownMin * 60);

        $row = FortuneNavFloodStrike::firstOrNew([
            'platform' => $platform,
            'platform_user_id' => $userId,
        ]);

        // หน้าต่างสะสมหมดอายุ → เริ่มนับใหม่
        if ($row->window_started_at === null
            || $row->window_started_at->lt(now()->subHours(self::STRIKE_WINDOW_HOURS))) {
            $row->window_started_at = now();
            $row->strikes = 0;
            $row->warned_count = 0;
        }

        if (! $isNewStrike) {
            // อยู่ระหว่างคูลดาวน์ — เงียบสนิท ห้ามส่งข้อความซ้ำแม้จะกดอีก 50 ครั้ง
            $row->last_hit_at = now();
            $row->last_payload = mb_substr($payload, 0, 100);
            $row->save();

            return ['action' => self::ACTION_SILENT, 'message' => null];
        }

        $row->strikes = (int) $row->strikes + 1;
        $row->last_hit_at = now();
        $row->last_payload = mb_substr($payload, 0, 100);

        if ($displayName !== null && $displayName !== '') {
            $row->display_name = mb_substr($displayName, 0, 191);
        }

        Log::warning('🚦 NavFloodGuard: แตะเกณฑ์กดปุ่มรัว', [
            'platform' => $platform,
            'user_id' => $userId,
            'payload' => $payload,
            'reason' => $reason,
            'strikes' => $row->strikes,
        ]);

        // ── ขั้น 3: ระงับ
        if ($row->strikes >= self::MAX_STRIKES) {
            $banDays = max(1, (int) ($this->settings->nav_flood_ban_days ?? 7));

            // 🚨 shadow mode ต้อง **ไม่แบนใครจริง** — คำนวณและบันทึก strike ได้ แต่ห้ามลงโทษ
            //    (check() แปลงผลเป็น pass ทีหลังก็จริง แต่ ban() มี side effect ถาวรไปแล้ว)
            if ($this->mode() !== 'enforce') {
                $row->save();

                Log::info('NavFloodGuard: would_ban (shadow mode — ไม่ได้แบนจริง)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'reason' => $reason,
                    'ban_days' => $banDays,
                ]);

                return [
                    'action' => self::ACTION_BANNED,
                    'message' => $this->buildBanMessage($banDays),
                ];
            }

            $row->banned_at = now();
            $row->save();

            try {
                app(FortuneBanService::class)->ban(
                    $platform,
                    $userId,
                    $banDays * 24 * 60,   // ⚠️ หน่วยเป็นนาที · null = แบนถาวร ห้ามส่ง
                    'nav_flood: '.$reason,
                    null,
                    $row->display_name,
                );
            } catch (\Throwable $e) {
                Log::error('NavFloodGuard: สั่งระงับไม่สำเร็จ', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'action' => self::ACTION_BANNED,
                'message' => $this->buildBanMessage($banDays),
            ];
        }

        // ── ขั้น 1-2: เตือน (เพดานแข็ง 2 ข้อความ/หน้าต่าง)
        $maxWarnings = self::MAX_STRIKES - 1;

        if ($row->warned_count >= $maxWarnings) {
            $row->save();

            return ['action' => self::ACTION_SILENT, 'message' => null];
        }

        $row->warned_count = (int) $row->warned_count + 1;
        $row->last_warned_at = now();
        $row->save();

        $banDays = max(1, (int) ($this->settings->nav_flood_ban_days ?? 7));

        return [
            'action' => self::ACTION_WARN,
            'message' => $row->warned_count === 1
                ? $this->buildFirstWarning($cooldownMin)
                : $this->buildFinalWarning($banDays),
        ];
    }

    /**
     * บวกตัวนับแบบ list-of-timestamps แล้วคืนจำนวนครั้งในหน้าต่าง
     *
     * ⚠️ ห้ามใช้ counter + Cache::put ต่ออายุ TTL (แบบ line_flood) — คนที่กดถี่ต่อเนื่อง
     *    จะทำให้ตัวนับไม่มีวันหมดอายุ ค้างยาวจนกว่าจะหยุดสนิท
     */
    protected function bump(string $key, int $windowSec, int $now): int
    {
        $log = Cache::get($key, []);

        if (! is_array($log)) {
            $log = [];
        }

        $log = array_values(array_filter($log, fn ($t) => ($now - (int) $t) < $windowSec));
        $log[] = $now;

        // เก็บเผื่อหน้าต่างอีกเท่าตัว กันขอบหาย แต่ไม่ให้ยาวไม่จำกัด
        Cache::put($key, array_slice($log, -200), $windowSec * 2);

        return count($log);
    }

    /**
     * ปุ่มนี้ตั้งใจให้กดซ้ำไหม (ยกเว้นเฉพาะกฎปุ่มเดิม)
     */
    protected function isRepeatSafe(string $payload): bool
    {
        $upper = mb_strtoupper(trim($payload));

        foreach (self::REPEAT_SAFE_PREFIXES as $prefix) {
            if (str_starts_with($upper, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function isPaidCustomer(string $platform, string $userId): bool
    {
        try {
            // ⚡ ใช้ instance เดียวทั้งโปรเซส — การสร้าง FortuneConversationService ใหม่
            //    ลากเอา FortuneAIService + FacebookWebhookService + FortuneChartService มาด้วย
            //    ถ้าสร้างใหม่ทุกครั้งที่กดปุ่ม จะแพงกว่าตัวงานเองหลายเท่า
            //    (hasPaidActiveReading มีแคช 30 วิของตัวเองอยู่แล้ว)
            self::$fcs ??= app(\App\Services\FortuneConversationService::class);

            return self::$fcs->hasPaidActiveReading($userId);
        } catch (\Throwable $e) {
            // เช็คไม่ได้ = ถือว่าจ่ายแล้ว (ปลอดภัยไว้ก่อน ห้ามเสี่ยงปิดปากคนจ่ายเงิน)
            return true;
        }
    }

    protected function clearStrikes(string $platform, string $userId): void
    {
        try {
            if (! Schema::hasTable('fortune_nav_flood_strikes')) {
                return;
            }

            FortuneNavFloodStrike::where('platform', $platform)
                ->where('platform_user_id', $userId)
                ->delete();
        } catch (\Throwable $e) {
            // ไม่สำคัญพอจะทำให้ flow พัง
        }
    }

    protected function mode(): string
    {
        return (string) ($this->settings->nav_flood_mode ?? 'log_only');
    }

    protected function buildFirstWarning(int $cooldownMin): string
    {
        return "🌙 เจ้าชะตากดปุ่มถี่เกินไปแล้วนะคะ\n\n"
            ."แม่หมอส่งให้ใหม่ทุกครั้งที่เจ้าชะตากด ระบบเลยรวน\n"
            ."และเพจอาจถูกจำกัดการส่งข้อความค่ะ\n\n"
            ."ขอพักสัก {$cooldownMin} นาที แล้วกดใหม่ได้ตามปกติ\n"
            .'ของเจ้าชะตายังอยู่ครบ ไม่หายไปไหนนะคะ 🙏';
    }

    protected function buildFinalWarning(int $banDays): string
    {
        return "⚠️ *ประกาศจากระบบ*\n\n"
            ."เจ้าชะตากดปุ่มรัวซ้ำเป็นครั้งที่ 2 ภายในวันนี้แล้วค่ะ\n"
            ."หากยังกดรัวแบบเดิมอีก ระบบจะ*งดให้บริการจากเพจ {$banDays} วัน*\n\n"
            .'กดครั้งเดียวแล้วรอสักครู่ก็พอค่ะ แม่หมอส่งให้ครบทุกครั้งอยู่แล้ว 🙏';
    }

    protected function buildBanMessage(int $banDays): string
    {
        return "🚫 *ระบบงดให้บริการชั่วคราว {$banDays} วัน*\n"
            ."═══════════════════════\n"
            ."เจ้าชะตาได้รับคำเตือนเรื่องการกดปุ่มรัวไปแล้ว 2 ครั้ง แต่ยังทำซ้ำ\n"
            ."ระบบจึงงดให้บริการจากเพจ {$banDays} วันตามที่แจ้งไว้ค่ะ\n"
            ."═══════════════════════\n"
            .'ครบกำหนดแล้วกลับมาดูดวงได้ตามปกติ — หากเป็นความเข้าใจผิด ทักหาแอดมินเพจได้ค่ะ 🙏';
    }
}
