<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\MessageBuffer;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 📦 (2026-08-17) Settle window ของ Deep 39 Pro Session — คู่แฝดของ ProcessBufferedCelticMessageJob
 *
 * ปัญหาเดิม: Deep 39 ไม่มี debounce เลย (MessageBuffer จดไว้ว่า 'deep_qa' — Phase 4b — future
 *   แต่ไม่เคยทำ) → ลูกค้าถามรัว 3 ข้อ = ยิง AI 3 ครั้ง ตอบ 3 ครั้งแยกกัน ทับกันเอง
 *
 * Flow (trailing debounce — เหมือน Celtic เป๊ะ):
 *   1. handleProSession (จุด 3c) append เข้า buffer 'deep_qa' + dispatch job นี้ delay window+1
 *   2. ลูกค้าพิมพ์อีก → append + dispatch อีกตัว (นาฬิกาเริ่มใหม่)
 *   3. job ตัวแรก fire → เห็น last_at ยังใหม่ → skip
 *   4. job ตัวสุดท้าย fire → last_at ครบ window → flush + ตอบทีเดียว
 *
 * Idempotent: flush เป็น atomic — ตัวแรกที่ได้ buffer ตัวถัดไปเจอ empty แล้ว return
 */
class ProcessBufferedProSessionMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int ไม่ retry — buffer ไม่ใช่ critical path, fail = ลูกค้าพิมพ์ใหม่ได้ */
    public int $tries = 1;

    /** @var int flush + AI call + reply */
    public int $timeout = 180;

    public function __construct(
        public int $readingId,
        public string $platform,
        public string $userId,
        public int $windowSeconds,
    ) {
        $this->onQueue('tpix-default'); // queue เดียวกับ Celtic (มี worker อยู่จริง)
    }

    public function handle(): void
    {
        $buffer = app(MessageBuffer::class);
        $scope = 'deep_qa';

        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            Log::warning('ProcessBufferedProSessionMessageJob: ไม่พบ reading', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        $settings = FortuneTellingSetting::getSettings();
        $service = new FortuneConversationService($settings);

        // ยังอยู่ในช่วง debounce ปกติ (ลูกค้าพิมพ์ต่อได้อีก) → ปล่อยให้ job ตัวหลังจัดการ
        //   ⏳ (2026-09-02) หน้าต่างอาจถูกขยายสำหรับคนเล่ายาว → ต้องมีเพดานรวมกำกับ (ดู Celtic job)
        $maxSec = (int) ($settings->qa_settle_max_seconds ?? 180);
        $hasCacheBuffer = ! empty($buffer->peek($scope, $this->userId));
        if ($hasCacheBuffer && ! $buffer->isSettled($scope, $this->userId, $this->windowSeconds, $maxSec)) {
            Log::debug('ProcessBufferedProSessionMessageJob: buffer ยังใหม่ → skip', [
                'reading_id' => $this->readingId,
                'window' => $this->windowSeconds,
                'max' => $maxSec,
            ]);

            return;
        }

        // 🛟 (2026-08-22) peek สำเนาคำถามค้างไว้ "ก่อน" เช็ค session — ห้าม take
        //   isInProSession() ไม่ใช่ read-only: หมดเวลาเมื่อไหร่มันเรียก clearProSessionFlags()
        //   ซึ่งล้าง pending_q ทิ้งในคอลเดียวกัน ⇒ บรรทัดถัดไปจะไม่เหลืออะไรให้กู้เลย
        //   (แต่จะ take ก่อนก็ไม่ได้ — ธงต้องอยู่ให้ isInProSession ยืดเวลาให้เมื่อยังมีคำถามค้าง)
        $peeked = $service->peekPendingProSessionQuestionPublic($reading, 'deep');

        // ⚠️ ต้องเช็ค session "ก่อน" หยิบคำถามออก — isInProSession ยืดเวลาให้เมื่อยังมีคำถามค้าง
        //   ถ้าหยิบก่อน ธงค้างจะหายไป → session ถูกตัดสินว่าหมดเวลา → ไม่ตอบคำถามที่เพิ่งหยิบมา
        $isLateAnswer = false;

        if (! $service->isInProSessionPublic($reading)) {
            // ✅ (2026-08-22) บิลที่จ่ายเงินแล้ว + มีคำถามที่ไม่เคยได้คำตอบ = ต้องตอบ แม้ session ปิดไปแล้ว
            //
            //   ลูกค้าจ่ายเงินซื้อ "คำตอบ" ไม่ได้ซื้อ "สิทธิ์ถามภายในเวลา" —
            //   ระบบตอบไม่ทันเป็นความผิดฝั่งเรา ไม่ใช่ของเขา (rule: paid bills always resume)
            //
            //   ต้นตอ FTU-260822-P2391: job ติดคิวหลัง ProcessCommentEngagement ~100 ตัว นาน 12 นาที
            //   พอได้รันจริง cron ก็เพิ่งปิดบิลไปเสี้ยววินาทีก่อนหน้า → เดิม return ตรงนี้
            //   ⇒ ลูกค้าจ่าย 39฿ ถาม 1 ข้อ ได้คำตอบ 0 ข้อ แถมโดนข้อความ "หมดเวลาทำนายแล้วค่ะ"
            //   fail-closed ทุกด่าน — ไม่ใช่บิลจ่ายเงิน / ไม่มีคำถามค้าง / ไม่รู้อายุ = เงียบไว้ดีกว่า
            //   (ห้ามเดาอายุ — ตอบคำถามของเมื่อคืนตอนตี 2 แย่กว่าไม่ตอบ)
            $lateText = $peeked['text'];
            $ageMin = $this->pendingAgeMinutes($peeked['at']);

            if (! $reading->is_paid
                || $lateText === ''
                || $ageMin === null
                || $ageMin > FortuneConversationService::PRO_SESSION_LATE_ANSWER_MAX_MINUTES) {
                Log::info('ProcessBufferedProSessionMessageJob: session ปิดแล้ว → ข้าม', [
                    'reading_id' => $this->readingId,
                    'is_paid' => (bool) $reading->is_paid,
                    'pending_age_min' => $ageMin,
                ]);

                return;
            }

            // 🔒 ผู้ชนะรายเดียวเท่านั้น — ทางนี้เกิดหลัง pending_q ถูกล้างแล้ว = ไม่มี token ให้แย่งกัน
            //    ถ้าไม่กัน job ที่ค้างคิวจะตอบซ้ำกันทุกตัว (เคสจริงมี 4 ตัวรันไล่กันใน 3 นาที)
            if (! $service->claimLateProSessionAnswerPublic($reading, 'deep')) {
                Log::info('ProcessBufferedProSessionMessageJob: มี job อื่นตอบย้อนหลังไปแล้ว → ข้าม', [
                    'reading_id' => $this->readingId,
                ]);

                return;
            }

            $isLateAnswer = true;

            Log::warning('ProcessBufferedProSessionMessageJob: ⏰ session ปิดแล้วแต่คำถามที่จ่ายเงินยังไม่ได้ตอบ → ตอบย้อนหลัง', [
                'reading_id' => $this->readingId,
                'pending_age_min' => $ageMin,
                'preview' => mb_substr($lateText, 0, 120),
            ]);
        }

        $combined = '';
        $count = 0;
        $source = 'cache';

        if ($hasCacheBuffer) {
            $flushed = $buffer->flush($scope, $this->userId);
            $combined = trim((string) ($flushed['combined'] ?? ''));
            $count = (int) ($flushed['count'] ?? 0);

            // ⚠️ ต้องล้างสำเนาสำรอง "ทันที" ที่ flush สำเร็จ — ห้ามเลื่อนไปท้ายบล็อก
            //   job ตัวอื่นที่ fire พร้อมกันจะเจอ cache ว่าง (เราชนะ lock ไปแล้ว) แล้วตกไปหยิบสำเนา
            //   ถ้ายังไม่ล้าง = ตอบคำถามเดียวกันซ้ำสองรอบ (เคสจริงมี job ค้างคิวพร้อมกัน 3 ตัว)
            if ($combined !== '') {
                $service->takePendingProSessionQuestionPublic($reading);
            }
        }

        // 🛟 (2026-08-21) buffer บน Cache หายไป → กู้จากสำเนาบน conversation_state (MySQL)
        //   เกิดจริงเมื่อ deploy รัน `cache:clear` (= flushdb ทั้ง redis DB 1) ระหว่างที่ลูกค้าถาม
        //   เดิมเคสนี้ = `return;` เงียบ → คำถามลูกค้าที่จ่ายเงินแล้วระเหยหายโดยไม่มี error
        if ($combined === '') {
            $combined = $service->takePendingProSessionQuestionPublic($reading);
            $source = 'conversation_state';
            $count = $combined === '' ? 0 : substr_count($combined, "\n") + 1;

            if ($combined !== '') {
                Log::warning('ProcessBufferedProSessionMessageJob: 🛟 กู้คำถามจาก conversation_state (cache buffer หาย)', [
                    'reading_id' => $this->readingId,
                    'combined_preview' => mb_substr($combined, 0, 120),
                ]);
            }
        }

        // 🛟 (2026-08-22) ทางตอบย้อนหลัง — pending_q ถูก clearProSessionFlags() ล้างไปแล้วตอนเช็ค session
        //   ⇒ take...() ข้างบนคืนค่าว่างแน่นอน ต้องตกมาใช้สำเนาที่ peek ไว้ก่อนหน้า
        //   ปลอดภัยเรื่องตอบซ้ำ เพราะสิทธิ์ถูกจองไปแล้วด้วย claimLateProSessionAnswerPublic()
        if ($combined === '' && $isLateAnswer && $peeked['text'] !== '') {
            $combined = $peeked['text'];
            $source = 'late_peek';
            $count = substr_count($combined, "\n") + 1;
        }

        if ($combined === '') {
            return; // job อื่น flush/หยิบไปแล้ว
        }

        Log::info('ProcessBufferedProSessionMessageJob: flush + ตอบทีเดียว', [
            'reading_id' => $this->readingId,
            'platform' => $this->platform,
            'message_count' => $count,
            'source' => $source,
            'combined_preview' => mb_substr($combined, 0, 120),
        ]);

        try {
            $payload = $service->handleProSessionBuffered($reading, $combined);

            // silent_skip ไม่ควรเกิด (skipSettle=true) — กันไว้ไม่ให้ส่งข้อความว่าง
            if (($payload['action'] ?? null) === 'silent_skip' || empty($payload['message'])) {
                Log::warning('ProcessBufferedProSessionMessageJob: ไม่มีข้อความจะส่ง', [
                    'reading_id' => $this->readingId,
                    'action' => $payload['action'] ?? null,
                ]);

                return;
            }

            // ⏰ (2026-08-22) ตอบย้อนหลัง — ลูกค้าเพิ่งได้ข้อความ "หมดเวลาทำนายแล้วค่ะ" ไปหมาดๆ
            //   ถ้าโผล่คำตอบเฉยๆ ต่อท้ายจะงงว่าตกลงหมดเวลาหรือไม่หมด → ต้องมีบรรทัดขอโทษนำ
            //   (อยู่ในบทบาทแม่หมอตามธรรมเนียมไฟล์นี้ — ห้ามพูดถึงคิว/ระบบ/AI ขัดข้อง)
            if ($isLateAnswer) {
                $payload['message'] = "🌙 ขออภัยที่แม่หมอตอบช้าไปหน่อยนะคะ 🙏\n"
                    ."คำถามของเจ้าชะตาไม่ได้หายไปไหน — แม่หมอตอบให้แล้วค่ะ ✨\n\n"
                    ."──────────────────────\n\n"
                    .$payload['message'];
            }

            // 🎟️ (2026-08-26) ยืม replyToken ที่เทิร์น silent_skip ฝากไว้ → ตอบฟรี ไม่กินโควต้า push
            //   นี่คือเส้นที่ลูกค้า FTU-260826-G5544 ถามเรื่องลูกแมวแล้วคำตอบ push ไม่ออก (โควต้าหมด 300/300)
            app(FortuneChannelManager::class)->sendResponse($this->platform, $this->userId, $payload, $this->borrowedReplyExtra());
        } catch (\Throwable $e) {
            Log::error('ProcessBufferedProSessionMessageJob: exception', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);

            // 🌙 ห้ามบอกลูกค้าว่า "AI ขัดข้อง" — ชวนพิมพ์ใหม่แบบอยู่ในบทบาท
            try {
                app(FortuneChannelManager::class)->sendResponse($this->platform, $this->userId, [
                    'action' => 'pro_session_ai_fail',
                    'message' => "🌙 ขอเวลาแม่หมอตั้งจิตสักครู่นะคะ 🙏\n"
                        .'พลังงานปั่นป่วนเล็กน้อย — ลองส่งคำถามอีกครั้งได้ไหมคะ ✨',
                ], $this->borrowedReplyExtra());
            } catch (\Throwable $sendErr) {
                Log::debug('ProcessBufferedProSessionMessageJob: ส่งข้อความ fallback ไม่สำเร็จ', [
                    'error' => $sendErr->getMessage(),
                ]);
            }
        }
    }

    /**
     * 🎟️ (2026-08-26) หยิบ replyToken ที่เทิร์น webhook ฝากไว้ (ถ้ายังสด)
     *
     * LINE คิดเงิน push แต่ **reply ฟรี** — job นี้เดิมไม่มี token เลยต้อง push ทุกคำตอบ
     * แพลนที่ใช้อยู่มีแค่ 300 push/เดือน ⇒ คำตอบ pro session กินโควต้าจนหมดแล้วเงียบ
     * (เคสจริง 2026-08-26: ลูกค้าถามเรื่องลูกแมวหาย 3 เทิร์น คำตอบ push ไม่ออกทั้งหมด)
     *
     * @return array<string,string> ว่าง = ไม่มี token → caller ตกไป push ตามเดิม (ไม่มี regression)
     */
    protected function borrowedReplyExtra(): array
    {
        if ($this->platform !== 'line') {
            return [];
        }

        $token = \App\Services\Fortune\ReplyTokenVault::take($this->platform, $this->userId);

        return $token ? ['reply_token' => $token] : [];
    }

    /**
     * 🛟 (2026-08-22) อายุของคำถามค้าง (นาที) — ใช้ตัดสินว่ายังควรตอบย้อนหลังไหม
     *
     * @param  mixed  $at  ISO timestamp จาก pro_session_pending_q_at
     * @return int|null null = ไม่มี timestamp / parse ไม่ได้ ⇒ ห้ามตอบย้อนหลัง (fail-closed)
     *                  เพราะไม่รู้อายุ = ไม่รู้ว่าเป็นคำถามเมื่อครู่หรือของเมื่อคืน
     *
     * ⚠️ รับ mixed ไม่ใช่ ?string — ค่ามาจาก conversation_state (คอลัมน์ JSON) จะเป็นอะไรก็ได้
     *    ถ้าประกาศ ?string แล้วเจอ array จะโยน TypeError **นอก** try ของ handle() = job ตายทั้งตัว
     */
    private function pendingAgeMinutes($at): ?int
    {
        if (! is_string($at) || $at === '') {
            return null;
        }

        try {
            // 🩹 Carbon 3 — absolute=true เสมอ (กัน now() < $at → ค่าลบ → ผ่านด่านอายุฟรี)
            return (int) \Carbon\Carbon::parse($at)->diffInMinutes(now(), true);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
