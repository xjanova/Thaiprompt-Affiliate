<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAIService;
use App\Services\FortuneTakeoverService;
use App\Services\LineFortuneService;
use App\Support\SafeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    public function __construct(
        private readonly FacebookWebhookService $fbService,
        private readonly LineFortuneService $lineService,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'nullable|integer|exists:fortune_readings,id',
            'platform' => 'nullable|in:facebook,line,telegram',
            'platform_user_id' => 'nullable|string|max:255',
            'text' => 'required|string|min:1|max:2000',
        ]);

        $platform = $data['platform'] ?? null;
        $userId = $data['platform_user_id'] ?? null;
        $reading = null;

        if (! empty($data['reading_id'])) {
            $reading = FortuneReading::find($data['reading_id']);
            if ($reading) {
                // Use the reading's CANONICAL identity — was hardcoded to
                // 'facebook' + facebook_user_id, which broke admin replies to
                // LINE customers (facebook_user_id is null for LINE).
                // ✈️ (2026-09-13) แหล่งเดียว FortuneRecipient — รู้จัก line / telegram / facebook ครบ
                ['platform' => $platform, 'user_id' => $resolvedId] = \App\Services\Fortune\FortuneRecipient::resolve($reading);
                $userId = $resolvedId !== '' ? $resolvedId : $userId;
            }
        }

        if (! $platform || ! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'need reading_id or (platform + platform_user_id)',
            ], 422);
        }

        // 🤫 (2026-10-06) แอดมินพิมพ์หาลูกค้า = แอดมินคุยอยู่ → เริ่ม/ต่อเทคโอเวอร์ให้เหมือนแผงเว็บ (บอทหยุดแทรก)
        //    ไม่ได้ส่ง reading_id มา → ใช้บิลล่าสุดของลูกค้าคนนี้ (เทคโอเวอร์ผูกกับบิล แต่ด่านนับทุกบิลของคนนั้น)
        $takeoverMinutes = 0;
        $takeoverStartedHere = false;
        try {
            $reading ??= FortuneReading::query()
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                ->orderByDesc('id')
                ->first();

            if ($reading) {
                $ensured = app(FortuneTakeoverService::class)
                    ->ensureAdminTakeoverDetailed($reading, $request->user()?->id, $data['text']);
                $takeoverMinutes = $ensured['minutes'];
                $takeoverStartedHere = $ensured['started'];
            }
        } catch (Throwable $e) {
            Log::warning('AdminChat: เริ่ม/ต่อเทคโอเวอร์ก่อนส่งไม่สำเร็จ (ยังส่งข้อความต่อ)', [
                'error' => SafeLog::exceptionMessage($e),
                'platform' => $platform,
                'platform_user_id' => $userId,
            ]);
        }

        try {
            // ✈️ (2026-09-13) Telegram → TelegramFortuneService (เดิมทุกอย่างที่ไม่ใช่ line ยิงเข้า Facebook)
            // 🤫 (2026-10-06) ส่งในนาม "แอดมินตัวจริง" — ด่านเทคโอเวอร์ชั้นส่งไม่บล็อก (ห้ามใช้ from_admin แทน)
            $ok = \App\Services\Fortune\TakeoverSendGuard::asHumanAdmin(fn () => match ($platform) {
                'line' => $this->lineService->sendMessage($userId, $data['text']),
                'telegram' => (new \App\Services\TelegramFortuneService)->sendMessage($userId, $data['text']),
                default => $this->fbService->sendMessage($userId, $data['text']),
            });

            Log::info('AdminChat: operator message sent', [
                'admin_id' => $request->user()?->id,
                'platform' => $platform,
                'platform_user_id' => $userId,
                'reading_id' => $reading?->id ?? ($data['reading_id'] ?? null),
                'text_preview' => mb_substr($data['text'], 0, 80),
                'delivered' => $ok,
                'takeover_minutes' => $takeoverMinutes,
            ]);

            // ↩️ (2026-10-06, bug-hunt L10) ส่งไม่ออก + request นี้เป็นคน "เริ่ม" เทคโอเวอร์ → ถอยกลับ
            //    (ข้อความไม่ถึงลูกค้า = แอดมินยังไม่ได้คุย บอทต้องไม่เงียบค้าง 30 นาที) · แค่ "ต่อเวลา" = ไม่แตะ
            if (! $ok && $takeoverStartedHere && $reading) {
                $this->revertTakeoverAfterFailedSend($reading, $request->user()?->id);
            }

            // 💬 (2026-06-19) Mirror the operator's reply into the realtime chat
            //    log so it shows in the warroom transcript immediately (this path
            //    bypasses FortuneChannelManager::sendResponse). Fail-safe.
            if ($ok) {
                try {
                    app(\App\Services\Fortune\FortuneChatLogService::class)
                        ->record($platform, $userId, 'admin', $data['text'], ['by' => 'admin#'.($request->user()?->id ?? '?')]);
                } catch (Throwable $logErr) {
                    // ignore — chat log is best-effort
                }

                // 📝 (v3) บันทึกข้อความแอดมินลง fortune_takeover_logs แบบเดียวกับแผงเว็บ (FortuneTakeoverService::logMessage)
                //   ⇒ คำขอ "ลูกค้าขอคุยกับคน" ถือว่ามีคนรับแล้ว (หลุดจากคิว ops/summary + takeover?status=requested)
                //   และโผล่ในประวัติเทคโอเวอร์บนเว็บ · ไม่แตะผลการส่ง (best-effort)
                //   หาบิลจากทั้ง reading_id และตัวตนลูกค้า (platform + platform user id) — คำขอถูกบันทึกกับบิลล่าสุด
                //   ของลูกค้าตอนขอ ซึ่งอาจเป็นคนละใบกับที่แอดมินเปิด หรือแอดมินส่งแบบไม่มี reading_id
                if ($request->user()?->id) {
                    $adminId = (int) $request->user()->id;
                    try {
                        foreach ($this->requestLogTargets($reading, (string) $platform, (string) $userId) as $target) {
                            app(FortuneTakeoverService::class)->logMessage($target, $adminId, $data['text']);
                        }
                    } catch (Throwable $auditErr) {
                        Log::warning('AdminChat: บันทึก log ข้อความแอดมินไม่สำเร็จ (ข้อความส่งแล้ว)', [
                            'reading_id' => $reading?->id,
                            'error' => SafeLog::exceptionMessage($auditErr),
                        ]);
                    }
                }
            }

            $reading?->refresh();

            return response()->json([
                'success' => (bool) $ok,
                'data' => [
                    'platform' => $platform,
                    'platform_user_id' => $userId,
                    'delivered' => (bool) $ok,
                    'at' => now()->toIso8601String(),
                    // 🤫 (2026-10-06) ส่งข้อความ = เทคโอเวอร์อัตโนมัติ — แอปใช้อัปเดตแถบ "แอดมินคุยอยู่"
                    'reading_id' => $reading?->id,
                    'is_takeover' => (bool) $reading?->isAdminTakenOver(),
                    'takeover_until' => optional($reading?->admin_takeover_until)->toIso8601String(),
                    'remaining_minutes' => $reading?->isAdminTakenOver() ? $reading->takeoverRemainingMinutes() : 0,
                ],
                'message' => $ok ? 'sent' : 'platform service rejected',
            ], $ok ? 200 : 502);
        } catch (Throwable $e) {
            Log::error('AdminChat: send failed', [
                'error' => $e->getMessage(),
                'platform' => $platform,
                'platform_user_id' => $userId,
            ]);

            if ($takeoverStartedHere && $reading) {
                $this->revertTakeoverAfterFailedSend($reading, $request->user()?->id);
            }

            // 🔐 (2026-10-06) error ของ Guzzle/Http พิมพ์ URL เต็ม (token ใน query) — ข้อความที่ออก JSON ต้องผ่าน SafeLog
            //   (log ปลอดภัยอยู่แล้วด้วย RedactSecretsProcessor · ใช้แพตเทิร์นเดียวกันกับ takeover/resume)
            return response()->json([
                'success' => false,
                'message' => 'send failed: '.SafeLog::exceptionMessage($e),
            ], 500);
        }
    }

    /**
     * บิลที่ต้องบันทึกข้อความแอดมิน (v3) — บิลจาก reading_id + ทุกบิลของลูกค้าคนนี้ที่มีคำขอคุยกับคนค้างอยู่
     * ไม่มีทั้งสองอย่าง → บิลล่าสุดของลูกค้า (ให้ประวัติเทคโอเวอร์บนเว็บเห็นข้อความเหมือนส่งจากแผงเว็บ)
     *
     * @return array<int, FortuneReading>
     */
    private function requestLogTargets(?FortuneReading $reading, string $platform, string $platformUserId): array
    {
        $targets = [];
        if ($reading !== null) {
            $targets[(int) $reading->id] = $reading;
        }

        if ($platform !== '' && $platformUserId !== '') {
            $openIds = \App\Services\AdminApp\CustomerRequestQueue::openRequestReadingIdsForCustomer($platform, $platformUserId);
            $missing = array_values(array_diff($openIds, array_keys($targets)));
            if ($missing !== []) {
                foreach (FortuneReading::query()->whereIn('id', $missing)->get() as $r) {
                    $targets[(int) $r->id] = $r;
                }
            }

            if ($targets === []) {
                $latest = FortuneReading::query()
                    ->where(function ($q) use ($platformUserId) {
                        $q->where('platform_user_id', $platformUserId)->orWhere('facebook_user_id', $platformUserId);
                    })
                    ->latest('id')
                    ->first();
                if ($latest !== null) {
                    $targets[(int) $latest->id] = $latest;
                }
            }
        }

        return array_values($targets);
    }

    /**
     * ↩️ ถอยเทคโอเวอร์ที่ request นี้เพิ่งเปิด (ส่งข้อความแอดมินไม่ออก) — best-effort
     */
    private function revertTakeoverAfterFailedSend(FortuneReading $reading, ?int $adminId): void
    {
        try {
            app(FortuneTakeoverService::class)->revertAdminTakeover($reading, $adminId);
        } catch (Throwable $e) {
            Log::warning('AdminChat: ถอยเทคโอเวอร์หลังส่งไม่ออกไม่สำเร็จ (หมดเวลาเองตามกำหนด)', [
                'reading_id' => $reading->id,
                'error' => SafeLog::exceptionMessage($e),
            ]);
        }
    }

    public function suggest(Request $request, FortuneAIService $aiService): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'nullable|integer|exists:fortune_readings,id',
            'context_text' => 'required|string|max:4000',
            'customer_name' => 'nullable|string|max:120',
        ]);

        $customerName = $data['customer_name'] ?? 'customer';
        if (! empty($data['reading_id'])) {
            $reading = FortuneReading::find($data['reading_id']);
            if ($reading?->facebook_user_name) {
                $customerName = $reading->facebook_user_name;
            }
        }

        $systemPrompt = 'You are an admin assistant for a Thai fortune-telling business. '
            .'Customer name: '.$customerName.'. The admin is drafting a reply in Thai. '
            .'Output ONE short Thai reply (1-3 sentences) that the admin can send as-is. '
            .'Be polite, empathetic, end with kha. No emojis unless natural. No greetings repeated.';

        try {
            $result = $aiService->chatWithCustomSystemPrompt(
                systemMessage: $systemPrompt,
                userMessage: $data['context_text'],
                config: ['temperature' => 0.6, 'max_tokens' => 220],
            );
            // FortuneAIService::chatWithCustomSystemPrompt returns
            // ['response' => string, ...] via sanitizeChatResult.
            $reply = is_array($result)
                ? ($result['response'] ?? $result['content'] ?? $result['text'] ?? '')
                : (string) $result;

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestion' => $reply,
                    'customer_name' => $customerName,
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('AdminChat: suggest failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'suggest failed: '.\App\Support\SafeLog::exceptionMessage($e),
            ], 500);
        }
    }

    /**
     * 🎮 (2026-06-04) Admin takeover from the warroom /chat "คืนงานให้บอท" toggle.
     *
     * Exposes the existing FortuneTakeoverService over the Sanctum admin API so
     * the warroom operator can pause the bot for a conversation exactly like the
     * web /admin/takeover page does. Additive — reuses the same service + audit
     * trail (fortune_takeover_logs), so cache invalidation + webhook bypass logic
     * all stay consistent across both UIs.
     */
    public function takeover(Request $request, FortuneTakeoverService $takeover): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'required|integer|exists:fortune_readings,id',
            'minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $reading = FortuneReading::find($data['reading_id']);
        if (! $reading) {
            return response()->json(['success' => false, 'message' => 'reading not found'], 404);
        }

        try {
            // forceIgnoreDisabled: this is an explicit operator action, so honour
            // it even if auto-takeover is disabled in settings (parity with the
            // admin panel button).
            $minutes = $takeover->takeover(
                $reading,
                FortuneReading::TAKEOVER_REASON_MANUAL,
                $request->user()?->id,
                $data['minutes'] ?? null,
                null,
                true,
            );
            $reading->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'reading_id' => $reading->id,
                    'is_takeover' => true,
                    'minutes' => $minutes,
                    'until' => optional($reading->admin_takeover_until)->toIso8601String(),
                    'remaining_minutes' => $reading->isAdminTakenOver() ? $reading->takeoverRemainingMinutes() : 0,
                ],
                'message' => 'admin took over — bot paused',
            ]);
        } catch (Throwable $e) {
            Log::error('AdminChat: takeover failed', [
                'error' => $e->getMessage(),
                'reading_id' => $reading->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'takeover failed: '.SafeLog::exceptionMessage($e),
            ], 500);
        }
    }

    /**
     * ⏱ (2026-10-06) ต่อเวลาเทคโอเวอร์ — บวกเพิ่มจากเวลาสิ้นสุดเดิม (แอปแอดมิน)
     *
     * chat/takeover ตั้งเวลาสิ้นสุดใหม่เป็น "ตอนนี้ + minutes" (อาจสั้นลงกว่าเดิม) จึงใช้แทนการต่อเวลาไม่ได้
     * - ยังเทคโอเวอร์อยู่ → FortuneTakeoverService::extend() ตัวเดียวกับปุ่ม "ต่อเวลา" บนหน้าเว็บ
     * - หมดเวลาไปแล้ว → เริ่มเทคโอเวอร์ใหม่แบบ forceIgnoreDisabled (เหมือน chat/takeover)
     *   🩹 ห้ามปล่อยให้ extend() ไปเรียก takeover() แบบไม่ force — ถ้าปิดระบบส่งต่อแอดมิน (admin_handover_enabled)
     *      จะได้ 0 นาที แล้วแอปขึ้น "ต่อเวลาอีก 0 นาที" ทั้งที่บอทกลับมาตอบเองแล้ว
     */
    public function extend(Request $request, FortuneTakeoverService $takeover): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'required|integer|exists:fortune_readings,id',
            'minutes' => 'required|integer|min:1|max:1440',
        ]);

        $reading = FortuneReading::find($data['reading_id']);
        if (! $reading) {
            return response()->json(['success' => false, 'message' => 'reading not found'], 404);
        }

        try {
            $adminId = $request->user()?->id;
            $minutes = (int) $data['minutes'];

            $reading->refresh();
            $added = $reading->isAdminTakenOver()
                ? $takeover->extend($reading, $minutes, $adminId)
                : $takeover->takeover($reading, FortuneReading::TAKEOVER_REASON_MANUAL, $adminId, $minutes, null, true);
            $reading->refresh();

            if ($added <= 0 || ! $reading->isAdminTakenOver()) {
                return response()->json([
                    'success' => false,
                    'data' => [
                        'reading_id' => $reading->id,
                        'is_takeover' => $reading->isAdminTakenOver(),
                        'minutes_added' => 0,
                        'until' => optional($reading->admin_takeover_until)->toIso8601String(),
                        'remaining_minutes' => 0,
                    ],
                    'message' => 'ต่อเวลาไม่สำเร็จ — บทสนทนานี้ไม่ได้อยู่ในโหมดแอดมินคุยแทนแล้ว ลองกดเทคโอเวอร์ใหม่',
                    'error_code' => 'TAKEOVER_NOT_ACTIVE',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reading_id' => $reading->id,
                    'is_takeover' => $reading->isAdminTakenOver(),
                    'minutes_added' => $added,
                    'until' => optional($reading->admin_takeover_until)->toIso8601String(),
                    'remaining_minutes' => $reading->isAdminTakenOver() ? $reading->takeoverRemainingMinutes() : 0,
                ],
                'message' => "ต่อเวลาอีก {$added} นาที",
            ]);
        } catch (Throwable $e) {
            Log::error('AdminChat: extend failed', [
                'error' => \App\Support\SafeLog::exceptionMessage($e),
                'reading_id' => $reading->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ต่อเวลาไม่สำเร็จ',
            ], 500);
        }
    }

    /**
     * ✨ (2026-06-04) Hand the conversation back to the AI bot (warroom toggle
     * flipped to "bot on"). Mirrors the /ai resume command.
     */
    public function resume(Request $request, FortuneTakeoverService $takeover): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'required|integer|exists:fortune_readings,id',
            // 🤫 (2026-10-06) true (ค่าเริ่มต้น) = ให้บอทส่งของที่จ่ายแล้วซึ่งถูกพักไว้ระหว่างเทคโอเวอร์
            //    false = "จัดการเองแล้ว ไม่ต้องส่ง" → ล้างรายการพัก + ตั้งธงว่าส่งแล้ว (cron ไม่ตามส่งซ้ำ)
            'deliver_deferred' => 'nullable|boolean',
        ]);

        $reading = FortuneReading::find($data['reading_id']);
        if (! $reading) {
            return response()->json(['success' => false, 'message' => 'reading not found'], 404);
        }

        try {
            ['platform' => $platform, 'user_id' => $uid] = \App\Services\Fortune\FortuneRecipient::resolve($reading);
            $deferredBefore = \App\Services\Fortune\TakeoverResumeService::deferredFor($platform, $uid);
            $deliver = (bool) ($data['deliver_deferred'] ?? true);

            // 🤫 (bug-hunt L5) คืนงาน = ปิดเทคโอเวอร์ "ทุกบิล" ของลูกค้าคนนี้ (ด่านบอทเงียบนับต่อลูกค้า)
            $takeover->resume($reading, $request->user()?->id, true, $deliver);

            return response()->json([
                'success' => true,
                'data' => [
                    'reading_id' => $reading->id,
                    'is_takeover' => false,
                    'deliver_deferred' => $deliver,
                    // ของที่พักไว้ ณ ตอนกดคืนงาน — ส่งตามลำดับ (deliver_deferred=true) หรือถูกล้างทิ้ง (false)
                    'deferred' => $deferredBefore,
                    // ระดับลูกค้า (additive) — บอทกลับมาคุยกับลูกค้าคนนี้จริงไหม (ทุกบิล)
                    'customer' => $this->customerTakeoverStatus($platform, $uid),
                ],
                'message' => 'bot resumed',
            ]);
        } catch (Throwable $e) {
            Log::error('AdminChat: resume failed', [
                'error' => $e->getMessage(),
                'reading_id' => $reading->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'resume failed: '.SafeLog::exceptionMessage($e),
            ], 500);
        }
    }

    /**
     * 🪪 (2026-06-04) Current takeover state for a reading — drives the warroom
     * chat toggle's initial position + per-thread polling. Cheap (cache hit).
     */
    public function takeoverStatus(Request $request, FortuneTakeoverService $takeover): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'required|integer|exists:fortune_readings,id',
        ]);

        $reading = FortuneReading::find($data['reading_id']);
        if (! $reading) {
            return response()->json(['success' => false, 'message' => 'reading not found'], 404);
        }

        $active = $takeover->isActive($reading);
        ['platform' => $platform, 'user_id' => $uid] = \App\Services\Fortune\FortuneRecipient::resolve($reading);

        return response()->json([
            'success' => true,
            'data' => [
                'reading_id' => $reading->id,
                'is_takeover' => $active,
                'until' => optional($reading->admin_takeover_until)->toIso8601String(),
                'remaining_minutes' => $active ? $reading->takeoverRemainingMinutes() : 0,
                // 🤫 (2026-10-06) ของที่ลูกค้าจ่ายแล้วแต่บอทพักไว้ระหว่างเทคโอเวอร์ (ส่งตอนคืนงาน)
                'deferred' => \App\Services\Fortune\TakeoverResumeService::deferredFor($platform, $uid),
                // 🤫 (bug-hunt L5) ระดับลูกค้า (additive) — ด่านบอทเงียบนับทุกบิลของลูกค้า: บิลนี้ไม่ได้เทคโอเวอร์
                //    แต่บิลอื่นของคนเดียวกันเทคโอเวอร์อยู่ = บอทยังเงียบ
                'customer' => $this->customerTakeoverStatus($platform, $uid),
            ],
        ]);
    }

    /**
     * สถานะเทคโอเวอร์ระดับลูกค้า (ทุกบิลของคนนั้น) — ตัวเดียวกับที่ด่านบอทเงียบใช้ตัดสิน
     *
     * @return array{is_takeover: bool, until: ?string, remaining_minutes: int, reading_id: ?int}
     */
    private function customerTakeoverStatus(string $platform, string $userId): array
    {
        try {
            $holder = $userId !== ''
                ? \App\Services\Fortune\TakeoverSendGuard::activeTakeoverReading($platform, $userId)
                : null;
        } catch (Throwable $e) {
            $holder = null;
        }

        return [
            'is_takeover' => $holder !== null,
            'until' => $holder?->admin_takeover_until?->toIso8601String(),
            'remaining_minutes' => $holder ? $holder->takeoverRemainingMinutes() : 0,
            'reading_id' => $holder?->id,
        ];
    }
}
