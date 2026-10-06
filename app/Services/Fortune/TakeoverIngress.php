<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Support\SafeLog;
use Illuminate\Support\Collection;
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
 * ⚡ คิวรีเดียวต่อข้อความ (bug-hunt P1) — บิลที่มีเทคโอเวอร์ค้าง + บิลที่จ่ายแล้วยังไม่จบ + บิลที่มีกล่องจอดรอ reply
 *    แล้วหยอดผลลง memo ของ TakeoverSendGuard (ด่านเดิมในคอนโทรลเลอร์ + ชั้นส่งใน request นี้ไม่ต้องคิวรีซ้ำ)
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
     * ที่เก็บรูประหว่างเทคโอเวอร์ — โฟลเดอร์ของตัวเอง (bug-hunt M2)
     * ⚠️ ห้ามใช้ fortune/slips ร่วมกับ fortune:pending_slip — ทางนั้น "ลบไฟล์ทิ้ง" หลังหยิบไปตรวจ
     *    และไม่อยู่ใต้ fortune/slip_archive (fortune:purge-slip-archive ลบทิ้งที่ 30 วัน)
     */
    public const IMAGE_DIR = 'fortune/takeover_images';

    /** สถานะที่ถือว่าบิล "จบแล้ว" — ไม่ park ข้อความลงบิลพวกนี้ */
    private const CLOSED_STATUSES = [
        FortuneReading::STATUS_COMPLETED, 'cancelled', 'expired', 'celtic_qa_window_expired',
    ];

    /**
     * @param  array{kind?:string,text?:?string,title?:?string,payload?:?string,image_url?:?string,image_base64?:?string,image_fetcher?:?callable}  $in
     *                                                                                                                                                   kind: text | command | image | sticker | quick_reply | postback | callback | audio | video | file | follow | other
     * @return bool true = ลูกค้าถูกเทคโอเวอร์อยู่ ผู้เรียกต้อง return ทันที
     */
    public static function intercept(string $platform, ?string $userId, array $in): bool
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            return false;
        }

        TakeoverSendGuard::beginMemoScope();

        try {
            $rows = self::customerRows($userId);
        } catch (\Throwable $e) {
            Log::error('🤫 TakeoverIngress: เช็คเทคโอเวอร์ล้ม — ปล่อย flow ปกติ (fail open)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return false;
        }

        $autoOn = TakeoverSendGuard::autoTakeoverEnabled();
        $takeoverReading = $rows
            ->filter(fn (FortuneReading $r) => $r->admin_takeover_until !== null
                && $r->admin_takeover_until->isFuture()
                && ($autoOn || $r->admin_takeover_reason !== FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST))
            ->sortByDesc(fn (FortuneReading $r) => $r->admin_takeover_until->getTimestamp())
            ->first();

        // หยอดผลลง memo — ด่านเดิมหลังจากนี้ + ชั้นส่งใน request นี้ไม่ต้องคิวรีซ้ำ
        TakeoverSendGuard::remember($userId, $takeoverReading?->admin_takeover_until, $takeoverReading?->id);

        if ($takeoverReading === null) {
            // เทคโอเวอร์หมดเวลาไปแล้วแต่ cron ยังไม่กวาด → ปิดให้ตอนนี้เลย (ส่งของที่พักไว้ก่อน flow ปกติ)
            $expired = $rows->filter(fn (FortuneReading $r) => $r->admin_takeover_until !== null && ! $r->admin_takeover_until->isFuture());
            if ($expired->isNotEmpty()) {
                TakeoverResumeService::finishExpiredFor($platform, $userId, $expired, true);
            }

            // LINE: กล่อง "แม่หมอกลับมาแล้ว" ที่จอดรอ (ไม่มี replyToken ตอนจบเทคโอเวอร์) → แนบไปกับคำตอบรอบนี้ (ห้าม push)
            if ($platform === FortuneRecipient::PLATFORM_LINE
                && $rows->contains(fn (FortuneReading $r) => ! empty(((array) $r->getConversationState('takeover_deferred', []))[TakeoverResumeService::ITEM_CELTIC_RESUME]['await_reply'] ?? null))) {
                TakeoverResumeService::armLineReplyPrefix($userId);
            }

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
        //    คำสั่ง (/start ฯลฯ) ไม่ใช่บริบทของคำถาม — ไม่ park (bug-hunt M6)
        $paidOpen = self::paidOpenReading($rows);
        $parked = false;
        if ($paidOpen !== null && $text !== '' && in_array($kind, ['text', 'image'], true) && ! str_starts_with($text, '/')) {
            $parked = self::parkContext($paidOpen, $text);
        }

        // Celtic ที่จ่ายแล้วค้างกลางทาง (เปิดไพ่/ถามคำถาม) แล้วลูกค้าพยายามคุย → ตอนจบเทคโอเวอร์ส่งกล่อง "แม่หมอกลับมาแล้ว ทำต่อได้เลย"
        // (ไม่ตอบข้อความที่พิมพ์ระหว่างเทคโอเวอร์ซ้ำ — แอดมินคุยไปแล้ว ลูกค้าแค่ต้องรู้ว่าทำต่อได้)
        // defer() แบบไม่มี meta = "พักถ้ายังไม่มี" (JSON_INSERT) — ไม่ต้องเช็คก่อน
        if ($paidOpen !== null
            && in_array($paidOpen->conversation_status, [
                FortuneReading::STATUS_CELTIC_PICKING,
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_QA_PROMPT,
            ], true)) {
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
     * คิวรีเดียวของลูกค้าคนนี้: บิลที่มีเทคโอเวอร์ค้าง (ทั้งยังไม่หมดและหมดแล้วรอปิด)
     * + บิลที่จ่ายแล้วยังไม่จบ (30 วันล่าสุด) + บิลที่มีกล่องจอดรอ reply
     *
     * @return Collection<int, FortuneReading>
     */
    private static function customerRows(string $userId): Collection
    {
        return FortuneReading::query()
            ->where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
            ->where(function ($q) {
                $q->whereNotNull('admin_takeover_until')
                    ->orWhere(function ($p) {
                        $p->where('is_paid', true)
                            ->whereNotIn('conversation_status', self::CLOSED_STATUSES)
                            ->where('created_at', '>=', now()->subDays(30));
                    })
                    ->orWhere('conversation_state', 'like', '%"await_reply"%');
            })
            ->orderByDesc('id')
            ->get();
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
            'command' => '[คำสั่ง] '.$text,
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
    private static function paidOpenReading(Collection $rows): ?FortuneReading
    {
        $cutoff = now()->subDays(30);

        return $rows
            ->filter(fn (FortuneReading $r) => (bool) $r->is_paid
                && ! in_array($r->conversation_status, self::CLOSED_STATUSES, true)
                && $r->created_at !== null && $r->created_at->greaterThanOrEqualTo($cutoff))
            ->sortByDesc('id')
            ->first();
    }

    /**
     * park บริบทแบบเขียนคีย์เดียว (กฎเดียวกับ FortuneReading::parkPendingContext — สั้นเกิน/ตัวเลขล้วน/ซ้ำ = ไม่เก็บ)
     *
     * อ่านค่าสดของคีย์นี้จาก DB แล้ว JSON_SET กลับคีย์เดียว — ไม่เขียน state ทั้งก้อนจากสำเนาเก่า (bug-hunt C1)
     */
    private static function parkContext(FortuneReading $reading, string $text): bool
    {
        $text = trim($text);
        if (mb_strlen($text) < 6 || preg_match('/^[\d\s\/\.\-:]+$/u', $text)) {
            return false;
        }

        try {
            $existing = trim((string) ConversationStateAtomic::fresh($reading, 'celtic_parked_context', ''));
            if ($existing !== '' && mb_strpos($existing, $text) !== false) {
                return false;
            }

            $merged = trim($existing.' | '.$text, ' |');
            $max = FortuneReading::PARKED_CONTEXT_MAX_CHARS;
            if (mb_strlen($merged) > $max) {
                $merged = '...'.mb_substr($merged, mb_strlen($merged) - $max);
            }

            ConversationStateAtomic::set($reading, 'celtic_parked_context', $merged);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * เก็บรูปถาวร (ไม่รัน AI/SlipOK) + ผูกไว้ที่บิลที่ถือเทคโอเวอร์ + ตั้ง cache เดียวกับ capturePendingSlipFromImage
     * (ลูกค้าพิมพ์ "โอนแล้ว" ภายใน 30 นาทีหลังจบเทคโอเวอร์ → flow เดิมย้อนหยิบรูปไปตรวจได้)
     *
     * 🧩 (bug-hunt C1) โหลดรูปก่อน (ช้าได้ถึง 20 วิ) แล้วค่อย "ต่อท้ายรายการ" ด้วย JSON_ARRAY_APPEND คีย์เดียว
     *    เดิมเขียน state ทั้งก้อนจากสำเนาที่โหลดก่อนโหลดรูป → ทับของที่พัก (ตัดบิล SMS / กล่องทำต่อ / park) หายเงียบ
     */
    private static function captureImage(string $platform, string $userId, FortuneReading $takeoverReading, array $in): ?string
    {
        try {
            $existing = ConversationStateAtomic::fresh($takeoverReading, 'takeover_images', []);
            if (is_array($existing) && count($existing) >= self::MAX_SLIPS_PER_READING) {
                return null;
            }

            $bytes = null;
            if (! empty($in['image_base64']) && is_string($in['image_base64'])) {
                $bytes = self::decodeBase64($in['image_base64']);
            } elseif (! empty($in['image_fetcher']) && is_callable($in['image_fetcher'])) {
                $b64 = ($in['image_fetcher'])();
                $bytes = is_string($b64) && $b64 !== '' ? self::decodeBase64($b64) : null;
            } elseif (! empty($in['image_url']) && is_string($in['image_url'])) {
                $resp = Http::timeout(20)->get($in['image_url']);
                $bytes = $resp->successful() ? $resp->body() : null;
            }

            if (empty($bytes) || strlen($bytes) > self::MAX_IMAGE_BYTES) {
                return null;
            }

            $disk = Storage::disk('local');
            $relPath = self::IMAGE_DIR.'/'.md5($userId).'_'.now()->format('YmdHis').'_'.substr(md5($bytes), 0, 8).'.jpg';
            $disk->put($relPath, $bytes);

            // ต่อท้ายรายการแบบคีย์เดียว (ครบเพดานพอดีระหว่างโหลด = ไม่เก็บ ลบไฟล์ทิ้ง)
            if (! ConversationStateAtomic::append($takeoverReading, 'takeover_images', [
                'path' => $relPath,
                'at' => now()->toIso8601String(),
            ], self::MAX_SLIPS_PER_READING)) {
                $disk->delete($relPath);

                return null;
            }

            // ทางเดิมของ "เก็บรูปเงียบ ๆ รอพิมพ์โอนแล้ว" — ใช้ "สำเนา" เพราะทางนั้นลบไฟล์หลังหยิบไปตรวจ (M2)
            try {
                $slipCopy = 'fortune/slips/pend_takeover_'.md5($userId).'_'.now()->timestamp.'.jpg';
                $disk->copy($relPath, $slipCopy);
                Cache::put('fortune:pending_slip:'.$platform.':'.$userId, $slipCopy, now()->addMinutes(30));
            } catch (\Throwable $e) {
                // สำเนาสำหรับ flow สลิปไม่ได้ = ตัวจริงยังอยู่ แอดมินเปิดดูได้
            }

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

    private static function decodeBase64(string $b64): ?string
    {
        $clean = str_contains($b64, ',') ? substr($b64, strpos($b64, ',') + 1) : $b64;

        return base64_decode($clean, true) ?: null;
    }
}
