<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Admin\OpsSummaryController;
use App\Models\AdminPushToken;
use App\Services\AdminApp\FortuneBillPresenter;
use App\Services\Fcm\FcmHttpV1Client;
use App\Support\SafeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 แจ้งเตือนแอปแอดมิน (FCM) เมื่อคิวงานบนหน้าแรก "เพิ่มขึ้น"
 *
 * ทุกนาที (routes/console.php) — นับกล่องคิวด้วยตัวนับชุดเดียวกับ GET ops/summary
 * (OpsSummaryController::buildQueue แบบไม่ดึงตัวอย่าง) แล้วเทียบกับรอบก่อน (cache)
 * กล่องไหน count มากกว่ารอบก่อน → ส่ง push หาทุกเครื่องในตาราง admin_push_tokens
 *
 * กล่องที่แจ้ง: customer_requests · bills_awaiting · withdrawals_pending · sms_unmatched · stuck_readings
 *
 * กติกา:
 *   - ไม่มีไฟล์ credentials ของ Firebase / หา project ไม่ได้ → จบเงียบ ๆ (ไม่ log ทุกนาที)
 *   - ยังไม่มีเครื่องลงทะเบียน → ล้าง snapshot แล้วจบ (เครื่องแรกที่ลงทะเบียนจะไม่โดนแจ้งย้อนหลังทั้งกอง)
 *   - รอบแรก (ยังไม่มี snapshot) = เก็บฐานอย่างเดียว ไม่แจ้ง
 *   - กล่องที่อ่านไม่ได้ (degraded = null) → ไม่แจ้ง และคงค่ารอบก่อนไว้ (กันแจ้งหลอกตอนกลับมาอ่านได้)
 *   - FCM บอกว่า token ใช้ไม่ได้ (UNREGISTERED / SENDER_ID_MISMATCH / INVALID_ARGUMENT ที่ token) → ลบแถวนั้น
 *   - ห้าม log token — log แค่จำนวน
 */
class AdminAppPushAlerts extends Command
{
    protected $signature = 'admin-app:push-alerts {--dry : คำนวณ+แสดงผล ไม่ส่งจริง ไม่อัปเดต snapshot}';

    protected $description = 'ส่ง push แจ้งแอปแอดมินเมื่อคิวงาน (คำขอคุยกับคน / บิลรอตรวจ / ถอนเงิน / SMS / บิลค้าง) เพิ่มขึ้น';

    /** snapshot จำนวนในคิวรอบก่อน */
    public const SNAPSHOT_KEY = 'admin_app:push_alerts:snapshot';

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

        if (! $fcm->isConfigured()) {
            // ไม่มี credentials = ยังไม่เปิดใช้ — เงียบ (scheduler เรียกทุกนาที)
            return self::SUCCESS;
        }

        $tokens = AdminPushToken::query()->get(['id', 'token', 'platform']);
        if ($tokens->isEmpty()) {
            if (! $dry) {
                Cache::forget(self::SNAPSHOT_KEY);
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

        $previous = Cache::get(self::SNAPSHOT_KEY);
        $previous = is_array($previous) ? $previous : null;

        // snapshot ใหม่: ค่าที่อ่านได้รอบนี้ · กล่องที่อ่านไม่ได้คงค่าเดิม
        $snapshot = $previous ?? [];
        foreach ($current as $type => $count) {
            if ($count !== null) {
                $snapshot[$type] = $count;
            }
        }

        if (! $dry) {
            Cache::put(self::SNAPSHOT_KEY, $snapshot, self::SNAPSHOT_TTL);
        }

        if ($previous === null) {
            $this->info('ตั้งฐานจำนวนคิวรอบแรก — ไม่ส่งแจ้งเตือน');

            return self::SUCCESS;
        }

        $alerts = [];
        foreach ($current as $type => $count) {
            if ($count === null || ! array_key_exists($type, $previous)) {
                continue;
            }
            if ($count > (int) $previous[$type]) {
                $alerts[] = $this->alertFor($type, $count, (int) $previous[$type], $queue[$type]);
            }
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
            'stuck_readings' => ['⚠️ บิลจ่ายแล้วค้าง', "ลูกค้าจ่ายแล้วยังไม่ได้คำทำนาย {$count} บิล — เปิดดูแล้วกดทำนายซ้ำ"],
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
