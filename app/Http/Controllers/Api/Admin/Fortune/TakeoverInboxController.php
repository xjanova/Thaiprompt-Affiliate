<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Api\Admin\OpsSummaryController;
use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\FortuneTellingSetting;
use App\Models\User;
use App\Services\AdminApp\CustomerRequestQueue;
use App\Services\AdminApp\FortuneBillPresenter;
use App\Services\Fortune\FortuneChatLogService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Admin Mobile API: กล่องแชท / เทคโอเวอร์ (แอปไทยพร้อม แอดมิน)
 *
 * ข้อมูลชุดเดียวกับหน้าเว็บ admin/fortune/takeover (Admin\FortuneTakeoverController)
 * การกระทำ (เทคโอเวอร์ / ต่อเวลา / คืนงาน / ส่งข้อความ) ใช้ chat/* เดิม — ที่นี่อ่านอย่างเดียว
 */
class TakeoverInboxController extends Controller
{
    /** ตัวกรองที่รองรับ */
    public const STATUSES = ['taken_over', 'requested', 'active', 'all'];

    /** หน้าต่างของ "บทสนทนาที่ยังไม่จบ" — ค่าเดียวกับหน้าเว็บ */
    private const ACTIVE_DAYS = 7;

    /**
     * GET /api/admin/takeover/conversations?status=&platform=&search=&page=&per_page=
     */
    public function conversations(Request $request, FortuneChatLogService $chatLog): JsonResponse
    {
        $data = $request->validate([
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
            'platform' => 'nullable|in:facebook,line,telegram',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $status = (string) ($data['status'] ?? 'taken_over');
        $now = now();
        $query = $this->scoped(
            FortuneReading::query()->with(['takeoverAdmin:id,name', 'user:id,name']),
            $status,
            $now
        );

        if (! empty($data['platform'])) {
            $query->where('platform', $data['platform']);
        }

        $search = trim((string) ($data['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'like', "%{$search}%")
                    ->orWhere('facebook_user_id', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%")
                    ->orWhere('bill_reference', 'like', "%{$search}%");
            });
        }

        if ($status === 'requested') {
            // (v3) คิวคำขอ — รอนานสุดก่อน (เวลาคำขอแรกที่ยังไม่มีใครรับ)
            $query->orderBy('cr.req_first_at')->orderBy('fortune_readings.id');
        } else {
            $query->orderByDesc('fortune_readings.updated_at')->orderByDesc('fortune_readings.id');
        }

        $page = $query->paginate((int) ($data['per_page'] ?? 20));
        $rows = $page->getCollection();
        $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();

        $keywords = OpsSummaryController::customerRequestKeywords($ids);
        $openRequests = CustomerRequestQueue::byReading($now, $ids);
        $lastAdminMessages = $this->lastAdminPanelMessages($rows);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $rows->map(fn (FortuneReading $r) => $this->presentConversation(
                    $r,
                    $keywords[(int) $r->id] ?? null,
                    $this->lastMessage($r, $chatLog, $lastAdminMessages[(int) $r->id] ?? null),
                    $openRequests[(int) $r->id] ?? null,
                ))->values()->all(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/takeover/stats
     */
    public function stats(): JsonResponse
    {
        $settings = FortuneTellingSetting::getSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'taken_over' => $this->scoped(FortuneReading::query(), 'taken_over')->count(),
                // (v3) คำขอคุยกับคนที่ยังไม่มีใครรับใน 24 ชม. (นับเป็นจำนวนบิล — นิยามเดียวกับ ops/summary)
                'requested' => CustomerRequestQueue::aggregate()['count'],
                'active_conversations' => $this->scoped(FortuneReading::query(), 'active')->count(),
                'takeovers_today' => FortuneTakeoverLog::query()
                    ->where('action', FortuneTakeoverLog::ACTION_TAKEOVER)
                    ->whereDate('created_at', today())
                    ->count(),
                'takeover_enabled' => $settings->isTakeoverEnabled(),
                'default_minutes' => $settings->getTakeoverDefaultMinutes(),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/admin/takeover/{reading}/messages
     *
     * ใช้ตรรกะเดียวกับ fortune/readings/{id}/transcript (แชทสดวันนี้จาก Redis → ไม่มีค่อยประกอบจากข้อมูลถาวร)
     * แล้วแปลงเป็นรูปของแอป: sender = customer | bot | admin | system
     */
    public function messages(FortuneReading $reading, FortuneReadingsController $transcript): JsonResponse
    {
        $payload = $transcript->transcript($reading)->getData(true);
        $raw = $payload['data']['messages'] ?? [];

        // แอดมินที่ตอบ — log เก็บเป็น "admin#<id>" แปลงเป็นชื่อในคิวรีเดียว
        $adminIds = collect($raw)
            ->map(fn ($m) => is_string($m['by'] ?? null) && preg_match('/^admin#(\d+)$/', $m['by'], $mm) ? (int) $mm[1] : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $adminNames = $adminIds === [] ? [] : User::query()->whereIn('id', $adminIds)->pluck('name', 'id')->all();

        $messages = [];
        foreach ($raw as $i => $m) {
            $sender = match ($m['role'] ?? 'user') {
                'bot' => 'bot',
                'admin' => 'admin',
                'system' => 'system',
                default => 'customer',
            };

            $adminName = null;
            if ($sender === 'admin' && is_string($m['by'] ?? null) && preg_match('/^admin#(\d+)$/', $m['by'], $mm)) {
                $adminName = $adminNames[(int) $mm[1]] ?? null;
            }

            $message = [
                'id' => (int) ($m['id'] ?? $i + 1),
                'sender' => $sender,
                'text' => (string) ($m['text'] ?? ''),
                'at' => $m['ts'] ?? null,
                'admin_name' => $adminName,
                'image_url' => $m['image_url'] ?? null,
            ];
            if ($sender === 'bot') {
                $message['ai_provider'] = $m['ai'] ?? null;
            }
            $messages[] = $message;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reading_id' => (int) $reading->id,
                'source' => $payload['data']['source'] ?? 'structured',
                'messages' => $messages,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // ภายใน
    // ────────────────────────────────────────────────────────────

    private function scoped(Builder $query, string $status, ?CarbonInterface $now = null): Builder
    {
        $t = $query->getModel()->getTable();

        return match ($status) {
            // 🩹 (v3) เดิม = เทคโอเวอร์อยู่ + reason customer_request ⇒ ว่างตลอด (webhook ไม่เทคโอเวอร์ให้แล้วตั้งแต่ 2026-05-17)
            //   ตอนนี้ = บิลที่มีคำขอใน log ที่ยังไม่มีแอดมินลงมือ (CustomerRequestQueue) · join เพื่อเรียงตามเวลาคำขอ
            'requested' => $query
                ->joinSub(
                    CustomerRequestQueue::openRequestRows($now)
                        ->groupBy('req.fortune_reading_id')
                        ->selectRaw('req.fortune_reading_id AS rid, MIN(req.created_at) AS req_first_at'),
                    'cr',
                    'cr.rid',
                    '=',
                    "{$t}.id"
                )
                ->select("{$t}.*"),
            // บทสนทนาที่ยังไม่จบ — ชุดเดียวกับตัวกรอง active ของหน้าเว็บ
            'active' => $query->whereNotIn('conversation_status', [FortuneReading::STATUS_COMPLETED])
                ->where('updated_at', '>=', now()->subDays(self::ACTIVE_DAYS)),
            'all' => $query,
            default => $query->takenOver(),
        };
    }

    /**
     * @param  array{sender: string, text: string, at: string|null}|null  $last
     * @param  array{requested_at: CarbonInterface, last_requested_at: CarbonInterface, request_count: int, keyword: string|null}|null  $request
     *                                                                                                                                            คำขอคุยกับคนที่ยังไม่มีใครรับ (null = ไม่มี)
     * @return array<string, mixed>
     */
    private function presentConversation(FortuneReading $r, ?string $keyword, ?array $last, ?array $request = null): array
    {
        $active = $r->isAdminTakenOver();
        $reason = $r->admin_takeover_reason;
        $legacyRequest = $active && $reason === FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST;
        $requested = $legacyRequest || $request !== null;

        return [
            'reading_id' => (int) $r->id,
            'bill_number' => FortuneBillPresenter::billNumber($r),
            'customer_name' => FortuneBillPresenter::customerName($r),
            'platform' => FortuneBillPresenter::platform($r),
            'platform_user_id' => $r->platform_user_id ?: $r->facebook_user_id,
            'package_label' => FortuneBillPresenter::packageLabel($r),
            'is_paid' => (bool) $r->is_paid,
            'conversation_status' => $r->conversation_status,
            'stage' => FortuneBillPresenter::stage($r),
            'is_taken_over' => $active,
            'takeover_reason' => $active ? $reason : null,
            'takeover_reason_label' => $active ? $this->reasonLabel($reason) : null,
            // (v3) true เมื่อมีคำขอคุยกับคนที่ยังไม่มีแอดมินลงมือ (จาก log) — หรือเทคโอเวอร์แบบเก่าจากคำขอลูกค้า
            'requested_by_customer' => $requested,
            'request_keyword' => $requested ? ($request['keyword'] ?? $keyword) : null,
            // ── เพิ่มใน v3 ──
            'requested_at' => $request !== null ? $request['requested_at']->toIso8601String() : null,
            'request_count' => $request !== null ? (int) $request['request_count'] : 0,
            'takeover_started_at' => $active ? $r->admin_takeover_started_at?->toIso8601String() : null,
            'takeover_until' => $active ? $r->admin_takeover_until?->toIso8601String() : null,
            'remaining_minutes' => $active ? $r->takeoverRemainingMinutes() : 0,
            'takeover_admin' => $active && $r->takeoverAdmin
                ? ['id' => (int) $r->takeoverAdmin->id, 'name' => $r->takeoverAdmin->name]
                : null,
            'last_message' => $last,
            // ข้อความล่าสุดเป็นของลูกค้า = ลูกค้ารอคำตอบอยู่
            'unread' => ($last['sender'] ?? null) === 'customer',
            // 🤫 (2026-10-06) ของที่ลูกค้าจ่ายแล้วแต่บอทพักไว้ระหว่างเทคโอเวอร์ — ส่งตอนคืนงาน (เฉพาะแถวที่เทคโอเวอร์อยู่)
            'deferred' => $active
                ? \App\Services\Fortune\TakeoverResumeService::deferredFor(
                    FortuneBillPresenter::platform($r),
                    (string) ($r->platform_user_id ?: $r->facebook_user_id)
                )
                : [],
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }

    private function reasonLabel(?string $reason): string
    {
        return match ($reason) {
            FortuneReading::TAKEOVER_REASON_MANUAL => 'กดเทคโอเวอร์เอง',
            FortuneReading::TAKEOVER_REASON_AUTO_REPLY => 'แอดมินพิมพ์ในช่องสนทนา',
            FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST => 'ลูกค้าขอคุยกับคน',
            default => (string) ($reason ?? '-'),
        };
    }

    /**
     * ข้อความล่าสุด — แชทสดวันนี้ (Redis) ก่อน ไม่มีค่อยใช้ข้อความที่แอดมินส่งผ่านแผงล่าสุด
     *
     * @return array{sender: string, text: string, at: string|null}|null
     */
    private function lastMessage(FortuneReading $r, FortuneChatLogService $chatLog, ?FortuneTakeoverLog $adminLog): ?array
    {
        $pid = (string) ($r->platform_user_id ?: $r->facebook_user_id);

        if ($pid !== '') {
            // อ่านแค่ท้าย ๆ ของบทสนทนา — ไม่ดึงทั้งบทสนทนาทีละแถว
            // 🤫 (2026-10-06, bug-hunt M1) ข้ามบรรทัดระบบ ("บอทงดส่ง" ระหว่างเทคโอเวอร์) — ไม่งั้นข้อความลูกค้า
            //    ที่ตามด้วยบรรทัดระบบจะไม่ถูกนับว่า "ยังไม่ได้ตอบ" (unread = ข้อความล่าสุดที่ไม่ใช่ระบบเป็นของลูกค้า)
            $last = $chatLog->getLastNonSystemForCustomer($r->platform ?: 'facebook', $pid);
            if (is_array($last)) {
                return [
                    'sender' => match ($last['role'] ?? 'user') {
                        'bot' => 'bot',
                        'admin' => 'admin',
                        default => 'customer',
                    },
                    'text' => mb_substr((string) $last['text'], 0, 200),
                    'at' => $last['ts'] ?? null,
                ];
            }
        }

        if ($adminLog !== null) {
            return [
                'sender' => 'admin',
                'text' => mb_substr((string) $adminLog->message, 0, 200),
                'at' => $adminLog->created_at?->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * ข้อความล่าสุดที่แอดมินส่งผ่านแผงหลังบ้านของแต่ละบทสนทนา (คิวรีเดียวทั้งหน้า)
     *
     * @param  Collection<int, FortuneReading>  $rows
     * @return array<int, FortuneTakeoverLog>
     */
    private function lastAdminPanelMessages(Collection $rows): array
    {
        $ids = $rows->pluck('id')->all();
        if ($ids === []) {
            return [];
        }

        $out = [];
        FortuneTakeoverLog::query()
            ->whereIn('fortune_reading_id', $ids)
            ->where('action', FortuneTakeoverLog::ACTION_MESSAGE)
            // 🩹 (v3) เฉพาะข้อความของแอดมิน (user_id ไม่ว่าง) — log action=message ที่ user_id = null คือคำขอของลูกค้า
            //   ("🙋 ลูกค้าขอคุยกับคน: …") และ log ระบบ (บิลค้าง 24 ชม. / AI ล้ม) ซึ่งเดิมถูกโชว์เป็นข้อความแอดมิน
            ->whereNotNull('user_id')
            ->orderByDesc('id')
            ->get(['id', 'fortune_reading_id', 'message', 'created_at'])
            ->each(function (FortuneTakeoverLog $log) use (&$out) {
                $rid = (int) $log->fortune_reading_id;
                if (! isset($out[$rid])) {
                    $out[$rid] = $log;
                }
            });

        return $out;
    }
}
