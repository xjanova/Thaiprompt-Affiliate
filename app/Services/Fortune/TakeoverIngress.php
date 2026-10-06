<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Support\SafeLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 🤫 TakeoverIngress — ข้อความลูกค้าที่เข้ามา "ระหว่างแอดมินเทคโอเวอร์"
 *
 * เรียกที่หัว webhook ของทุกช่องทาง (หลังกันข้อความซ้ำ) ก่อนด่าน/flow ใด ๆ:
 * - ไม่ได้ถูกเทคโอเวอร์ → ปิดเทคโอเวอร์ที่หมดเวลาแล้วแต่ cron ยังไม่กวาด (ส่งของที่พักไว้) → คืน false (flow ปกติ)
 * - ถูกเทคโอเวอร์อยู่ →
 *     1. จดข้อความเข้าแชทล็อก (แอปแอดมินอ่านจาก takeover/{reading}/messages) ให้แอดมินเห็น
 *     2. park ข้อความลงบิลที่จ่ายแล้วและยังไม่จบ (กลไกเดิม parkPendingContext) — ห้ามกลืนข้อความลูกค้า
 *     3. รูป = เก็บเงียบ ๆ แบบถาวร (อาจเป็นสลิป — หลักฐานการจ่ายเงินห้ามหาย)
 *     4. คืน true → ผู้เรียก return ทันที (ไม่เรียก AI ไม่ส่งอะไร)
 *
 * 📌 [[rule_gate_must_not_swallow_customer_text]] · [[rule_parked_context_is_not_an_answered_question]]
 */
final class TakeoverIngress
{
    /** เก็บรูปที่ลูกค้าส่งระหว่างเทคโอเวอร์ได้สูงสุดกี่ใบต่อบิล (กันสแปมรูปเต็มดิสก์) */
    private const MAX_SLIPS_PER_READING = 10;

    /** เพดานขนาดรูป (ไบต์) — เท่ากับ capturePendingSlipFromImage เดิม */
    private const MAX_IMAGE_BYTES = 6 * 1024 * 1024;

    /**
     * @param  array{kind?:string,text?:?string,title?:?string,payload?:?string,image_url?:?string,image_base64?:?string,image_fetcher?:?callable}  $in
     *                                                                                                                                                   kind: text | image | sticker | quick_reply | postback | callback | audio | video | file | follow | other
     * @return bool true = ลูกค้าถูกเทคโอเวอร์อยู่ ผู้เรียกต้อง return ทันที
     */
    public static function intercept(string $platform, ?string $userId, array $in): bool
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            return false;
        }

        try {
            $takeoverReading = TakeoverSendGuard::activeTakeoverReading($platform, $userId);
        } catch (\Throwable $e) {
            Log::error('🤫 TakeoverIngress: เช็คเทคโอเวอร์ล้ม — ปล่อย flow ปกติ (fail open)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return false;
        }

        if ($takeoverReading === null) {
            // เทคโอเวอร์หมดเวลาไปแล้วแต่ cron ยังไม่กวาด → ปิดให้ตอนนี้เลย (ส่งของที่พักไว้ก่อน flow ปกติ)
            TakeoverResumeService::finishExpiredFor($platform, $userId);

            return false;
        }

        $kind = (string) ($in['kind'] ?? 'text');
        $text = trim((string) ($in['text'] ?? ''));
        $title = trim((string) ($in['title'] ?? ''));

        // 1️⃣ แชทล็อกให้แอดมินเห็น
        $imageMeta = [];
        if (! empty($in['image_url']) && is_string($in['image_url'])) {
            $imageMeta['image_url'] = $in['image_url'];
        }
        self::logInbound($platform, $userId, $kind, $text, $title, $imageMeta);

        // 2️⃣ park ข้อความ (เฉพาะบิลที่จ่ายแล้วและยังไม่จบ — ที่เดียวที่บริบทถูกอ่านกลับเข้าพรอมต์)
        $paidOpen = self::paidOpenReading($userId);
        $parked = false;
        if ($paidOpen !== null && $text !== '' && in_array($kind, ['text', 'image'], true)) {
            $parked = $paidOpen->parkPendingContext($text);
        }

        // Celtic ที่จ่ายแล้วค้างกลางทาง (เปิดไพ่/ถามคำถาม) แล้วลูกค้าพยายามคุย → ตอนจบเทคโอเวอร์ส่งกล่อง "แม่หมอกลับมาแล้ว ทำต่อได้เลย"
        // (ไม่ตอบข้อความที่พิมพ์ระหว่างเทคโอเวอร์ซ้ำ — แอดมินคุยไปแล้ว ลูกค้าแค่ต้องรู้ว่าทำต่อได้)
        if ($paidOpen !== null
            && in_array($paidOpen->conversation_status, [
                FortuneReading::STATUS_CELTIC_PICKING,
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_QA_PROMPT,
            ], true)
            && ! isset(((array) $paidOpen->getConversationState('takeover_deferred', []))[TakeoverResumeService::ITEM_CELTIC_RESUME])) {
            TakeoverResumeService::defer($paidOpen, TakeoverResumeService::ITEM_CELTIC_RESUME);
        }

        // 3️⃣ รูป = อาจเป็นสลิป → เก็บถาวร
        $slipPath = null;
        if ($kind === 'image') {
            $slipPath = self::captureImage($platform, $userId, $takeoverReading, $in);
        }

        Log::info('🤫 TakeoverIngress: ลูกค้าทักระหว่างแอดมินเทคโอเวอร์ — บอทเงียบ (จดล็อก/park/เก็บรูปแล้ว)', [
            'platform' => $platform,
            'user_id' => $userId,
            'takeover_reading_id' => $takeoverReading->id,
            'kind' => $kind,
            'text_preview' => mb_substr($text !== '' ? $text : $title, 0, 60),
            'parked' => $parked,
            'slip_path' => $slipPath,
        ]);

        return true;
    }

    /**
     * จดข้อความขาเข้าลงแชทล็อกของแอดมิน
     */
    private static function logInbound(string $platform, string $userId, string $kind, string $text, string $title, array $meta): void
    {
        $label = match ($kind) {
            'image' => '📷 [ลูกค้าส่งรูป — เก็บไว้แล้ว]'.($text !== '' ? ' '.$text : ''),
            'sticker' => '[สติกเกอร์]',
            'audio' => '[ข้อความเสียง]',
            'video' => '[วิดีโอ]',
            'file' => '[ไฟล์แนบ]',
            'follow' => '[ลูกค้าเพิ่มเพื่อน/กลับมาเปิดแชท]',
            'quick_reply', 'postback', 'callback' => '[กดปุ่ม] '.($title !== '' ? $title : $text),
            default => $text,
        };

        if (trim($label) === '') {
            return;
        }

        try {
            app(FortuneChatLogService::class)->record($platform, $userId, 'user', $label, $meta);
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    /**
     * บิลที่จ่ายแล้วและยังไม่จบของลูกค้าคนนี้ (ล่าสุด) — ที่ park ข้อความ / พักกล่อง "ทำต่อ"
     */
    private static function paidOpenReading(string $userId): ?FortuneReading
    {
        try {
            return FortuneReading::query()
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                ->where('is_paid', true)
                ->whereNotIn('conversation_status', [
                    FortuneReading::STATUS_COMPLETED, 'cancelled', 'expired', 'celtic_qa_window_expired',
                ])
                ->orderByDesc('id')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * เก็บรูปถาวร (ไม่รัน AI/SlipOK) + ผูกไว้ที่บิลที่ถือเทคโอเวอร์ + ตั้ง cache เดียวกับ capturePendingSlipFromImage
     * (ลูกค้าพิมพ์ "โอนแล้ว" ภายใน 30 นาทีหลังจบเทคโอเวอร์ → flow เดิมย้อนหยิบรูปนี้ไปตรวจได้)
     */
    private static function captureImage(string $platform, string $userId, FortuneReading $takeoverReading, array $in): ?string
    {
        try {
            $existing = (array) $takeoverReading->getConversationState('takeover_images', []);
            if (count($existing) >= self::MAX_SLIPS_PER_READING) {
                return null;
            }

            $bytes = null;
            if (! empty($in['image_base64']) && is_string($in['image_base64'])) {
                $b64 = $in['image_base64'];
                $clean = str_contains($b64, ',') ? substr($b64, strpos($b64, ',') + 1) : $b64;
                $bytes = base64_decode($clean, true) ?: null;
            } elseif (! empty($in['image_fetcher']) && is_callable($in['image_fetcher'])) {
                $b64 = ($in['image_fetcher'])();
                if (is_string($b64) && $b64 !== '') {
                    $clean = str_contains($b64, ',') ? substr($b64, strpos($b64, ',') + 1) : $b64;
                    $bytes = base64_decode($clean, true) ?: null;
                }
            } elseif (! empty($in['image_url']) && is_string($in['image_url'])) {
                $resp = Http::timeout(20)->get($in['image_url']);
                $bytes = $resp->successful() ? $resp->body() : null;
            }

            if (empty($bytes) || strlen($bytes) > self::MAX_IMAGE_BYTES) {
                return null;
            }

            $relPath = 'fortune/slips/takeover_'.md5($userId).'_'.now()->format('YmdHis').'_'.substr(md5($bytes), 0, 8).'.jpg';
            Storage::disk('local')->put($relPath, $bytes);

            $existing[] = ['path' => $relPath, 'at' => now()->toIso8601String()];
            $takeoverReading->setConversationState('takeover_images', $existing);

            // ทางเดิมของ "เก็บรูปเงียบ ๆ รอพิมพ์โอนแล้ว" (FortuneConversationService::capturePendingSlipFromImage)
            Cache::put('fortune:pending_slip:'.$platform.':'.$userId, $relPath, now()->addMinutes(30));

            return $relPath;
        } catch (\Throwable $e) {
            Log::warning('🤫 TakeoverIngress: เก็บรูประหว่างเทคโอเวอร์ไม่สำเร็จ (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return null;
        }
    }
}
