<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Admin\OpsSummaryController;
use App\Models\AdminPushToken;
use App\Services\AdminApp\FortuneBillPresenter;
use App\Services\Fcm\FcmHttpV1Client;
use App\Services\FcmNotificationService;
use App\Support\SafeLog;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 แจ้งเตือนแอปแอดมิน (FCM) เมื่อคิวงานบนหน้าแรก "เพิ่มขึ้น"
 *
 * ทุก 2 นาที (routes/console.php) — นับกล่องคิวด้วยตัวนับชุดเดียวกับ GET ops/summary
 * (OpsSummaryController::buildQueue แบบไม่ดึงตัวอย่าง) แล้วเทียบกับ "จำนวนที่แจ้งไปล่าสุด" ของกล่องนั้น (cache)
 *
 * กล่องที่แจ้ง: customer_requests · bills_awaiting · withdrawals_pending · sms_unmatched · stuck_readings
 *
 * กติกา:
 *   - ปิด FCM ในหลังบ้าน (fcm_enabled — สวิตช์เดียวกับแอป SMS Checker) / ไม่มีไฟล์ credentials → จบเงียบ ๆ
 *   - ตาราง admin_push_tokens ยังไม่มี (deploy ขึ้นโค้ดก่อน migrate) → จบเงียบ ๆ ไม่ log ทุกรอบ
 *   - ส่งเฉพาะเครื่องของแอดมินที่ยังเป็นแอดมินอยู่ (is_super_admin หรือ role = admin · ยังไม่ถูกลบ)
 *     และ Sanctum token ที่ใช้ลงทะเบียนยังอยู่/ยังไม่หมดอายุ — แถวอื่นถูกลบทิ้ง (ลดสิทธิ์/ถอน token แล้วต้องเลิกได้ยอดเงิน)
 *   - ยังไม่มีเครื่องลงทะเบียน → ล้างสถานะแล้วจบ (เครื่องแรกที่ลงทะเบียนจะไม่โดนแจ้งย้อนหลังทั้งกอง)
 *   - รอบแรก (ยังไม่มีสถานะ) = เก็บฐานอย่างเดียว ไม่แจ้ง
 *   - 🚦 กันแจ้งกระพริบ (P2): แจ้งเมื่อจำนวน **สูงกว่าจำนวนที่แจ้งไปล่าสุด** ของกล่องนั้น และห่างจากครั้งก่อน
 *     ≥ COOLDOWN_MINUTES · จำนวนลงถึง 0 = รีเซ็ตจำนวนที่แจ้งเป็น 0 (คูลดาวน์ยังนับจากครั้งที่แจ้งจริง)
 *     เคสที่เคยกระพริบ: บิล Deep ในรอบ retry ของ check-pending สลับ "ค้าง/กำลังวิ่ง" ทุกนาที = แจ้งซ้ำ ~5 ครั้งต่อบิล
 *   - กล่องที่อ่านไม่ได้ (degraded = null) → ไม่แจ้ง และคงค่ารอบก่อนไว้ (กันแจ้งหลอกตอนกลับมาอ่านได้)
 *   - FCM บอกว่า token ใช้ไม่ได้ (UNREGISTERED / SENDER_ID_MISMATCH / INVALID_ARGUMENT ที่ token) → ลบแถวนั้น
 *   - ห้าม log token — log แค่จำนวน
 */
class AdminAppPushAlerts extends Command
{
    protected $signature = 'admin-app:push-alerts {--dry : คำนวณ+แสดงผล ไม่ส่งจริง ไม่อัปเดตสถานะ ไม่ลบ token}';

    protected $description = 'ส่ง push แจ้งแอปแอดมินเมื่อคิวงาน (คำขอคุยกับคน / บิลรอตรวจ / ถอนเงิน / SMS / บิลค้าง) เพิ่มขึ้น';

    /** snapshot จำนวนในคิวรอบก่อน (ไว้ดู/ดีบัก + ตัดสินกล่องที่อ่านไม่ได้) */
    public const SNAPSHOT_KEY = 'admin_app:push_alerts:snapshot';

    /** สถานะการแจ้งรายกล่อง: type → ['count' => จำนวนที่แจ้งไปล่าสุด, 'at' => unix time ที่แจ้งจริงล่าสุด|null] */
    public const NOTIFIED_KEY = 'admin_app:push_alerts:notified';

    /** คูลดาวน์ต่อกล่อง (นาที) */
    public const COOLDOWN_MINUTES = 15;

    private const SNAPSHOT_TTL = 86400;

    /** Android notification channel ของแอปแอดมิน (แอปต้องสร้าง channel นี้ — ไม่มีก็ตกไปช่องเริ่มต้นของ FCM) */
    public const ANDROID_CHANNEL = 'admin_ops_alerts';

    /**
     * กล่องที่แจ้ง → route ในแอป
     *
     * @var array<string, string>
     */
    public const ROUTES = [
        'customer_requests' => '/chat',
        'bills_awaiting' => '/work?tab=bills',
        'withdrawals_pending' => '/work?tab=withdrawals',
        'sms_unmatched' => '/work?tab=sms',
        'stuck_readings' => '/work?tab=stuck',
    ];

    public function handle(FcmHttpV1Client $fcm): int
    {
        $dry = (bool) $this->option('dry');

        try {
            // สวิตช์ FCM ในหลังบ้าน — ตัวเดียวกับที่แอป SMS Checker เคารพ (FcmNotificationService::isEnabled)
            if (! app(FcmNotificationService::class)->isEnabled()) {
                return self::SUCCESS;
            }

            if (! $fcm->isConfigured()) {
                // ไม่มี credentials = ยังไม่เปิดใช้ — เงียบ (scheduler เรียกทุก 2 นาที)
                return self::SUCCESS;
            }

            $tokens = $this->eligibleTokens($dry);
        } catch (QueryException $e) {
            // ตาราง admin_push_tokens ยังไม่มี (โค้ดขึ้นก่อน migrate · SQLSTATE 42S02) — จบเงียบ รอบหน้าค่อยลองใหม่
            //   error อื่นยังจบแบบไม่ล้ม แต่ log ไว้ (กันบั๊ก query ทำให้แจ้งเตือนหายเงียบตลอดกาล)
            if ((string) $e->getCode() !== '42S02') {
                Log::warning('admin-app:push-alerts: อ่านตาราง push token ไม่ได้ — ข้ามรอบนี้', [
                    'error' => SafeLog::exceptionMessage($e),
                ]);
            }

            return self::SUCCESS;
        }

        if ($tokens->isEmpty()) {
            if (! $dry) {
                Cache::forget(self::SNAPSHOT_KEY);
                Cache::forget(self::NOTIFIED_KEY);
            }

            return self::SUCCESS;
        }

        $controller = app(OpsSummaryController::class);
        $queue = $controller->buildQueue(now(), app(FortuneBillPresenter::class), withPreview: false);

        $current = [];
        foreach (array_keys(self::ROUTES) as $type) {
            $box = $queue[$type] ?? null;
            $current[$type] = is_array($box) && isset($box['count']) ? (int) $box['count'] : null;
        }

        // snapshot ใหม่: ค่าที่อ่านได้รอบนี้ · กล่องที่อ่านไม่ได้คงค่าเดิม
        $previous = Cache::get(self::SNAPSHOT_KEY);
        $snapshot = is_array($previous) ? $previous : [];
        foreach ($current as $type => $count) {
            if ($count !== null) {
                $snapshot[$type] = $count;
            }
        }

        $notified = Cache::get(self::NOTIFIED_KEY);
        $firstRun = ! is_array($notified);
        $notified = is_array($notified) ? $notified : [];

        [$alerts, $notified] = $this->decideAlerts($current, $notified, $firstRun, $queue);

        if (! $dry) {
            Cache::put(self::SNAPSHOT_KEY, $snapshot, self::SNAPSHOT_TTL);
            Cache::put(self::NOTIFIED_KEY, $notified, self::SNAPSHOT_TTL);
        }

        if ($firstRun) {
            $this->info('ตั้งฐานจำนวนคิวรอบแรก — ไม่ส่งแจ้งเตือน');

            return self::SUCCESS;
        }

        if ($alerts === []) {
            return self::SUCCESS;
        }

        if ($dry) {
            foreach ($alerts as $a) {
                $this->line("[dry] {$a['data']['type']}: {$a['title']} — {$a['body']}");
            }

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;
        $invalidIds = [];

        foreach ($alerts as $alert) {
            foreach ($tokens as $row) {
                if (in_array($row->id, $invalidIds, true)) {
                    continue;
                }

                try {
                    $result = $fcm->send(
                        $row->token,
                        $alert['data'],
                        ['title' => $alert['title'], 'body' => $alert['body']],
                        ['channel_id' => self::ANDROID_CHANNEL, 'tag' => $alert['data']['type']],
                        ['apns' => ['payload' => ['aps' => ['sound' => 'default', 'thread-id' => $alert['data']['type']]]]],
                    );
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('admin-app:push-alerts: ส่งไม่สำเร็จ', ['push_token_row' => $row->id, 'error' => SafeLog::exceptionMessage($e)]);

                    continue;
                }

                if ($result->ok) {
                    $sent++;
                } elseif ($result->invalidToken) {
                    $invalidIds[] = $row->id;
                } else {
                    $failed++;
                }
            }
        }

        $removed = $invalidIds === [] ? 0 : AdminPushToken::query()->whereIn('id', $invalidIds)->delete();

        Log::info('admin-app:push-alerts: ส่งแจ้งเตือนคิวงาน', [
            'types' => array_column(array_column($alerts, 'data'), 'type'),
            'devices' => $tokens->count(),
            'sent' => $sent,
            'failed' => $failed,
            'removed_invalid_tokens' => $removed,
        ]);

        $this->info("ส่ง {$sent} · ล้มเหลว {$failed} · ลบ token เสีย {$removed}");

        return self::SUCCESS;
    }

    /**
     * ตัดสินว่ากล่องไหนต้องแจ้ง + สถานะการแจ้งรอบถัดไป (ไม่มีผลข้างเคียง)
     *
     * @param  array<string, int|null>  $current  จำนวนรอบนี้ (null = อ่านไม่ได้)
     * @param  array<string, array{count?: int, at?: int|null}>  $notified  สถานะการแจ้งเดิม
     * @param  array<string, mixed>  $queue  กล่องคิวจาก buildQueue (ใช้ยอดเงินในข้อความ)
     * @return array{0: array<int, array{title: string, body: string, data: array<string, string>}>, 1: array<string, array{count: int, at: int|null}>}
     */
    private function decideAlerts(array $current, array $notified, bool $firstRun, array $queue): array
    {
        $alerts = [];
        $nowTs = now()->getTimestamp();

        foreach ($current as $type => $count) {
            if ($count === null) {
                continue; // อ่านไม่ได้ — คงสถานะเดิม
            }

            if ($firstRun || ! array_key_exists($type, $notified)) {
                // ฐานแรก: ของที่ค้างอยู่แล้วถือว่า "รับรู้แล้ว" — ไม่แจ้งย้อนหลังทั้งกอง
                $notified[$type] = ['count' => $count, 'at' => null];

                continue;
            }

            $lastCount = (int) ($notified[$type]['count'] ?? 0);
            $lastAt = $notified[$type]['at'] ?? null;

            if ($count === 0) {
                // เคลียร์คิวหมดแล้ว — รอบหน้ามีของใหม่แม้ชิ้นเดียวก็แจ้งได้ (เมื่อพ้นคูลดาวน์)
                $notified[$type] = ['count' => 0, 'at' => $lastAt];

                continue;
            }

            if ($count <= $lastCount) {
                continue;
            }

            if ($lastAt !== null && ($nowTs - (int) $lastAt) < self::COOLDOWN_MINUTES * 60) {
                continue; // ยังอยู่ในคูลดาวน์ — ไม่ขยับจำนวนที่แจ้ง รอบหลังพ้นเวลาแล้วยังสูงกว่าค่อยแจ้ง
            }

            $alerts[] = $this->alertFor($type, $count, $lastCount, (array) ($queue[$type] ?? []));
            $notified[$type] = ['count' => $count, 'at' => $nowTs];
        }

        return [$alerts, $notified];
    }

    /**
     * token ที่ยังส่งได้ — แอดมินตัวจริง + Sanctum token ยังใช้ได้ · ลบแถวที่ไม่ผ่านทิ้ง (เว้นโหมด --dry)
     *
     * @return Collection<int, AdminPushToken>
     */
    private function eligibleTokens(bool $dry): Collection
    {
        $tokens = AdminPushToken::query()
            ->whereNotNull('access_token_id')
            // ยังเป็นแอดมินอยู่ — นิยามเดียวกับ AdminApiMiddleware (is_super_admin หรือ role = admin)
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'admin_push_tokens.user_id')
                    ->whereNull('users.deleted_at')
                    ->where(function ($w) {
                        $w->where('users.is_super_admin', true)->orWhere('users.role', 'admin');
                    });
            })
            // Sanctum token ที่ใช้ลงทะเบียนยังอยู่ (ไม่ถูกถอน) และยังไม่หมดอายุ
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('personal_access_tokens')
                    ->whereColumn('personal_access_tokens.id', 'admin_push_tokens.access_token_id')
                    ->where(function ($e) {
                        $e->whereNull('personal_access_tokens.expires_at')
                            ->orWhere('personal_access_tokens.expires_at', '>', now());
                    });
            })
            ->get(['id', 'token', 'platform']);

        if (! $dry) {
            $stale = AdminPushToken::query()->whereNotIn('id', $tokens->pluck('id')->all())->delete();
            if ($stale > 0) {
                Log::info('admin-app:push-alerts: ลบ push token ของเครื่องที่หมดสิทธิ์แล้ว', ['removed' => $stale]);
            }
        }

        return $tokens;
    }

    /**
     * ข้อความแจ้งเตือนของแต่ละกล่อง
     *
     * @param  array<string, mixed>  $box
     * @return array{title: string, body: string, data: array<string, string>}
     */
    private function alertFor(string $type, int $count, int $previous, array $box): array
    {
        $amount = isset($box['amount_thb']) ? number_format((float) $box['amount_thb'], 2) : null;
        $new = $count - $previous;

        [$title, $body] = match ($type) {
            'customer_requests' => ['🙋 ลูกค้าขอคุยกับแอดมิน', "มีคำขอใหม่ {$new} ราย — รอแอดมินตอบทั้งหมด {$count} ราย"],
            'bills_awaiting' => ['🧾 บิลรอตรวจการโอน', "ลูกค้าแจ้งโอน/ส่งสลิปใหม่ — รอตรวจ {$count} บิล".($amount !== null ? " ({$amount} บาท)" : '')],
            'withdrawals_pending' => ['🏦 คำขอถอนเงินใหม่', "รออนุมัติ {$count} รายการ".($amount !== null ? " ({$amount} บาท)" : '')],
            'sms_unmatched' => ['💬 เงินเข้ายังไม่ผูกบิล', "SMS เงินเข้ารอจับคู่ {$count} รายการ".($amount !== null ? " ({$amount} บาท)" : '')],
            // ไม่บอกให้ "กดทำนายซ้ำ" ตรง ๆ — บางบิลลูกค้ามีบิลใหม่แล้ว (retry จะตอบ NEWER_ACTIVE_BILL ให้คืนเงิน/ปิดแทน)
            'stuck_readings' => ['⚠️ บิลจ่ายแล้วค้าง', "ลูกค้าจ่ายแล้วยังไม่ได้คำทำนาย {$count} บิล — เปิดดูแล้วเลือกวิธีกู้"],
        };

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => $type,
                'route' => self::ROUTES[$type],
                'count' => (string) $count,
            ],
        ];
    }
}
