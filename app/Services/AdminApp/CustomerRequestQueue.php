<?php

namespace App\Services\AdminApp;

use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 🙋 คิว "ลูกค้าขอคุยกับคน" ของแอปแอดมิน — สร้างจาก fortune_takeover_logs (นิยามเดียวของ ops/summary + takeover/*)
 *
 * ทำไมไม่ดูคอลัมน์ admin_takeover_* ของบิล:
 *   ตั้งแต่ 2026-05-17 webhook ทั้ง 3 ช่องทาง (LINE / FB / Telegram — handleCustomerHandoffRequest)
 *   เป็นโหมด "แจ้งแอดมินอย่างเดียว" — เขียน log (action = message · reason = customer_request · user_id = null)
 *   แล้วส่งข้อความบอกลูกค้าให้รอ ไม่เทคโอเวอร์ให้เอง ⇒ ดูจาก admin_takeover_reason คิวจะว่างตลอดกาล
 *
 * นิยาม "คำขอที่ยังไม่มีคนรับ":
 *   - แถว log ที่ reason = customer_request และ action ∈ {message (โหมดปัจจุบัน), takeover (โหมดเก่าก่อน 2026-05-17)}
 *   - เกิดภายใน WINDOW_HOURS ชั่วโมง (เก่ากว่านั้นไม่ใช่งานค้างแล้ว)
 *   - และ **ไม่มี** log ที่ใหม่กว่า (id มากกว่า) ของบิลเดียวกันที่แปลว่าแอดมินลงมือแล้ว:
 *       · action = message ที่มี user_id (แอดมินส่งข้อความผ่านแผงเว็บ / แอปแอดมิน chat/send)
 *       · action = takeover ที่ reason ≠ customer_request (กดเทคโอเวอร์เอง / แอดมินพิมพ์ใน Page Inbox = auto_reply)
 *       · action = extend หรือ resume (แอดมินต่อเวลา / คืนงานให้บอท)
 *     (auto_expire ไม่นับ — เป็นระบบหมดเวลาเอง ไม่ใช่คนตอบ · log ระบบอื่น user_id = null ก็ไม่นับ)
 *   - บิลที่ถูกลบ (soft delete จาก readings/{id}/cancel) ไม่นับ
 *
 * ⚠️ แอดมินที่ตอบทาง LINE OA Chat / Page Inbox แบบที่ระบบมองไม่เห็น จะยังค้างในคิวจนครบ 24 ชม.
 *    (FB: ข้อความแอดมินใน Page Inbox ปลุก takeover auto_reply = ถือว่ารับแล้ว · LINE OA Chat ไม่มี echo มาให้เห็น)
 */
final class CustomerRequestQueue
{
    /** มองย้อนหลังกี่ชั่วโมง */
    public const WINDOW_HOURS = 24;

    /** หัวข้อความที่ webhook เติมหน้าข้อความลูกค้า (ตัดทิ้งตอนโชว์ keyword) */
    private const LOG_PREFIX_PATTERN = '/^\s*🙋\s*ลูกค้าขอคุยกับคน\s*:\s*/u';

    /**
     * คิวรีของ "แถว log คำขอที่ยังไม่มีคนรับ" (ยังไม่ group) — คอลัมน์ id, fortune_reading_id, created_at, message
     */
    public static function openRequestRows(?CarbonInterface $now = null): QueryBuilder
    {
        $now ??= now();
        $since = $now->copy()->subHours(self::WINDOW_HOURS);
        $reason = FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST;

        return DB::table('fortune_takeover_logs as req')
            ->join('fortune_readings as fr', 'fr.id', '=', 'req.fortune_reading_id')
            ->whereNull('fr.deleted_at')
            ->where('req.reason', $reason)
            ->whereIn('req.action', [FortuneTakeoverLog::ACTION_MESSAGE, FortuneTakeoverLog::ACTION_TAKEOVER])
            ->where('req.created_at', '>=', $since)
            ->where('req.created_at', '<=', $now)
            ->whereNotExists(function ($q) use ($reason) {
                $q->selectRaw('1')
                    ->from('fortune_takeover_logs as ans')
                    ->whereColumn('ans.fortune_reading_id', 'req.fortune_reading_id')
                    ->whereColumn('ans.id', '>', 'req.id')
                    ->where(function ($w) use ($reason) {
                        $w->where(function ($m) {
                            $m->where('ans.action', FortuneTakeoverLog::ACTION_MESSAGE)
                                ->whereNotNull('ans.user_id');
                        })->orWhere(function ($t) use ($reason) {
                            $t->where('ans.action', FortuneTakeoverLog::ACTION_TAKEOVER)
                                ->whereRaw("COALESCE(ans.reason, '') <> ?", [$reason]);
                        })->orWhereIn('ans.action', [FortuneTakeoverLog::ACTION_EXTEND, FortuneTakeoverLog::ACTION_RESUME]);
                    });
            });
    }

    /**
     * จำนวนบิล (ไม่ใช่จำนวนข้อความ) ที่มีคำขอค้าง + เวลาของคำขอที่เก่าสุด
     *
     * @return array{count: int, oldest_at: Carbon|null}
     */
    public static function aggregate(?CarbonInterface $now = null): array
    {
        $row = self::openRequestRows($now)
            ->selectRaw('COUNT(DISTINCT req.fortune_reading_id) AS c, MIN(req.created_at) AS oldest')
            ->first();

        return [
            'count' => (int) ($row->c ?? 0),
            'oldest_at' => ! empty($row->oldest) ? Carbon::parse($row->oldest) : null,
        ];
    }

    /**
     * สรุปคำขอค้างรายบิล — เรียงคำขอที่รอนานสุดก่อน
     *
     * @param  array<int, int>|null  $readingIds  null = ทุกบิล
     * @return array<int, array{reading_id: int, requested_at: Carbon, last_requested_at: Carbon, request_count: int, keyword: string|null}>
     *                                                                                                                                       (คีย์ = reading_id)
     */
    public static function byReading(?CarbonInterface $now = null, ?array $readingIds = null, ?int $limit = null): array
    {
        if ($readingIds === []) {
            return [];
        }

        $groups = self::openRequestRows($now)
            ->when($readingIds !== null, fn ($q) => $q->whereIn('req.fortune_reading_id', $readingIds))
            ->groupBy('req.fortune_reading_id')
            ->selectRaw('req.fortune_reading_id AS rid, MIN(req.created_at) AS first_at, MAX(req.created_at) AS last_at,'
                .' MAX(req.id) AS last_id, COUNT(*) AS n')
            ->orderBy('first_at')
            ->orderBy('rid')
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();

        if ($groups->isEmpty()) {
            return [];
        }

        // ข้อความของคำขอล่าสุดในแต่ละบิล (คิวรีเดียว)
        $messages = DB::table('fortune_takeover_logs')
            ->whereIn('id', $groups->pluck('last_id')->all())
            ->pluck('message', 'id');

        $out = [];
        foreach ($groups as $g) {
            $out[(int) $g->rid] = [
                'reading_id' => (int) $g->rid,
                'requested_at' => Carbon::parse($g->first_at),
                'last_requested_at' => Carbon::parse($g->last_at),
                'request_count' => (int) $g->n,
                'keyword' => self::keyword($messages[(int) $g->last_id] ?? null),
            ];
        }

        return $out;
    }

    /**
     * id บิลทุกใบของลูกค้าคนนี้ (platform + platform user id) ที่มีคำขอคุยกับคนค้างอยู่
     *
     * ใช้ตอนแอดมินส่งข้อความจากแอป (chat/send) — คำขอถูกบันทึกไว้กับ "บิลล่าสุดของลูกค้า ณ ตอนขอ"
     * ซึ่งอาจไม่ใช่บิลที่แอดมินเปิดอยู่ หรือแอดมินส่งด้วย platform + platform_user_id โดยไม่มี reading_id
     * ⇒ ต้องหาจากตัวตนลูกค้า ไม่งั้นคำขอค้างในคิวจนครบ 24 ชม. ทั้งที่ตอบแล้ว
     *
     * @return array<int, int>
     */
    public static function openRequestReadingIdsForCustomer(string $platform, string $platformUserId, ?CarbonInterface $now = null, int $limit = 10): array
    {
        if ($platformUserId === '') {
            return [];
        }

        return self::openRequestRows($now)
            ->where(function ($q) use ($platformUserId) {
                $q->where('fr.platform_user_id', $platformUserId)->orWhere('fr.facebook_user_id', $platformUserId);
            })
            ->where(function ($q) use ($platform) {
                $q->where('fr.platform', $platform);
                if ($platform === 'facebook') {
                    // แถวเก่าก่อนมีคอลัมน์ platform = Facebook
                    $q->orWhereNull('fr.platform');
                }
            })
            ->distinct()
            ->limit($limit)
            ->pluck('req.fortune_reading_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * ข้อความที่ลูกค้าพิมพ์ตอนขอ — ตัดหัว "🙋 ลูกค้าขอคุยกับคน:" ที่ webhook เติมไว้ · ยาวสุด 120 ตัวอักษร
     */
    public static function keyword(?string $message): ?string
    {
        $text = trim((string) preg_replace(self::LOG_PREFIX_PATTERN, '', (string) $message));

        return $text === '' ? null : mb_substr($text, 0, 120);
    }
}
