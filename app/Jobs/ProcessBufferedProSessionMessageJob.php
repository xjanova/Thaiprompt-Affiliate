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

        $service = new FortuneConversationService(FortuneTellingSetting::getSettings());

        // ยังอยู่ในช่วง debounce ปกติ (ลูกค้าพิมพ์ต่อได้อีก) → ปล่อยให้ job ตัวหลังจัดการ
        $hasCacheBuffer = ! empty($buffer->peek($scope, $this->userId));
        if ($hasCacheBuffer && ! $buffer->isReadyToFlush($scope, $this->userId, $this->windowSeconds)) {
            Log::debug('ProcessBufferedProSessionMessageJob: buffer ยังใหม่ → skip', [
                'reading_id' => $this->readingId,
                'window' => $this->windowSeconds,
            ]);

            return;
        }

        // ⚠️ ต้องเช็ค session "ก่อน" หยิบคำถามออก — isInProSession ยืดเวลาให้เมื่อยังมีคำถามค้าง
        //   ถ้าหยิบก่อน ธงค้างจะหายไป → session ถูกตัดสินว่าหมดเวลา → ไม่ตอบคำถามที่เพิ่งหยิบมา
        if (! $service->isInProSessionPublic($reading)) {
            Log::info('ProcessBufferedProSessionMessageJob: session ปิดแล้ว → ข้าม', [
                'reading_id' => $this->readingId,
            ]);

            return;
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

            app(FortuneChannelManager::class)->sendResponse($this->platform, $this->userId, $payload);
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
                ]);
            } catch (\Throwable $sendErr) {
                Log::debug('ProcessBufferedProSessionMessageJob: ส่งข้อความ fallback ไม่สำเร็จ', [
                    'error' => $sendErr->getMessage(),
                ]);
            }
        }
    }
}
