<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;

/**
 * 🌙 FortuneCelticPremiumDetector (2026-05-07)
 *
 * ตรวจจับว่าลูกค้าอยู่ในช่วง Celtic Premium Chat หรือไม่
 *
 * Triggers (admin ตั้งได้ใน celtic_premium_chat_trigger):
 *   - 'after_questions_done': หลังตอบ celtic_cross_max_questions ครบ
 *   - 'always_after_q1':       หลังตอบ Q1 ตลอด window
 *
 * Conditions:
 *   - reading_type = 'celtic_cross'
 *   - is_paid = true
 *   - celtic_first_answered_at is set
 *   - within celtic_cross_qa_window_minutes (default 30)
 *   - max_messages cap ยังไม่ถึง
 *
 * Context for AI prompt:
 *   - 10 cards drawn (พร้อม position)
 *   - Q&A history (questions + responses)
 *   - minutes_remaining (สำหรับเตือน + สร้างความรู้สึก urgency)
 */
class FortuneCelticPremiumDetector
{
    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจว่า user อยู่ใน Celtic Premium window ไหม
     *
     * 🩹 (2026-05-07 review M3) — cache "ไม่มี active Celtic" ผล 30s
     *   ลูกค้าส่วนใหญ่ไม่มี active Celtic → ทุก chat turn ไม่ต้อง query DB
     *   เคสที่มี → ไม่ cache (จะ stale เพราะ window/cards เปลี่ยน)
     *
     * @return array|null null ถ้าไม่เข้าเงื่อนไข / array context ถ้าเข้า
     */
    public function detect(string $platform, string $platformUserId): ?array
    {
        if (! ($this->settings->celtic_premium_chat_enabled ?? true)) {
            return null;
        }

        // 🩹 M3 cache: skip query ถ้าเพิ่งเช็คไปและไม่มี active
        $negCacheKey = "fortune:celtic_premium_no_active:{$platform}:{$platformUserId}";
        if (Cache::get($negCacheKey)) {
            return null;
        }

        // ดึง active Celtic reading ของ user
        // 🩹 (2026-05-07 review L8) — exclude COMPLETED/cancelled sessions
        $query = FortuneReading::where('reading_type', 'celtic_cross')
            ->where('is_paid', true)
            ->whereNotNull('celtic_first_answered_at')
            ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
            ->orderByDesc('celtic_first_answered_at');

        // 🩹 (2026-05-08) fortune_readings ใช้ platform_user_id สำหรับ LINE
        //   ไม่มี column 'line_user_id' — query เก่า throw SQLSTATE[42S22]
        if ($platform === 'facebook') {
            $query->where('facebook_user_id', $platformUserId);
        } else {
            $query->where('platform_user_id', $platformUserId);
        }

        $reading = $query->first();
        if (! $reading) {
            // 🩹 M3 cache negative result 30s — ลูกค้าส่วนใหญ่ไม่มี active Celtic
            Cache::put($negCacheKey, true, 30);

            return null;
        }

        // เช็ค window
        $windowMin = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
        $deadline = $reading->celtic_first_answered_at->copy()->addMinutes($windowMin);
        if (now()->greaterThan($deadline)) {
            // window หมด → cache negative สั้น ๆ (60s) เพื่อไม่ query ซ้ำ
            Cache::put($negCacheKey, true, 60);

            return null; // หมดเวลาแล้ว
        }

        // เช็ค trigger condition
        // 🛡️ (2026-05-14 M4) ใช้ centralized accessor — ถ้า admin ไม่ set จะ return 0 (free chat)
        //   ใน free chat mode (max=0): 'after_questions_done' จะไม่ trigger Premium
        //   → ต้องใช้ trigger='always_after_q1' ถ้าอยากให้ Premium kick in หลัง Q1
        //   admin ที่ explicit set max ยังคงทำงานตามที่ตั้ง (ไม่เปลี่ยน behavior)
        $trigger = $this->settings->celtic_premium_chat_trigger ?? 'after_questions_done';
        $maxQuestions = $this->settings->getCelticMaxQuestions();
        $questionsUsed = (int) ($reading->celtic_questions_used ?? 0);

        if ($trigger === 'after_questions_done') {
            // ต้องตอบครบ max_questions ก่อน (หรือ max=0 = unlimited → ไม่เคย trigger)
            if ($maxQuestions === 0 || $questionsUsed < $maxQuestions) {
                return null;
            }
        }
        // 'always_after_q1' = ผ่านเงื่อนไข celtic_first_answered_at not null อยู่แล้ว

        // เช็ค message cap (อ่านจาก reading instance ตรง — ไม่ต้อง re-query)
        $maxMessages = (int) ($this->settings->celtic_premium_chat_max_messages ?? 30);
        $msgCount = (int) $reading->getConversationState('celtic_premium_msg_count', 0);
        if ($maxMessages > 0 && $msgCount >= $maxMessages) {
            return null; // เกิน cap
        }

        $minutesRemaining = max(0, now()->diffInMinutes($deadline, false));
        $warnAt = (int) ($this->settings->celtic_premium_chat_warn_minutes_left ?? 5);

        return [
            'reading_id' => $reading->id,
            'reading' => $reading,  // pass full model — caller ใช้ดึง cards + Q&A
            'minutes_remaining' => $minutesRemaining,
            'should_warn_time' => $minutesRemaining <= $warnAt,
            'message_count' => $msgCount,
            'max_messages' => $maxMessages,
            'window_min' => $windowMin,
            'questions_used' => $questionsUsed,
        ];
    }

    /**
     * เพิ่ม message count + return ค่าใหม่
     *
     * 🩹 (2026-05-07 review L7) — persist ใน FortuneReading conversation_state
     *   เดิม: Cache only → cache:clear/Redis flush รีเซ็ต cap → bypass ได้
     *   ใหม่: DB ใน conversation_state JSON — ถาวรตลอด session
     */
    public function incrementMessageCount(int $readingId): int
    {
        $reading = FortuneReading::find($readingId);
        if (! $reading) {
            return 0;
        }

        $current = (int) $reading->getConversationState('celtic_premium_msg_count', 0);
        $next = $current + 1;
        $reading->setConversationState('celtic_premium_msg_count', $next);

        return $next;
    }

    /**
     * ดึง message count (อ่านจาก DB conversation_state)
     */
    public function getMessageCount(int $readingId): int
    {
        $reading = FortuneReading::find($readingId);
        if (! $reading) {
            return 0;
        }

        return (int) $reading->getConversationState('celtic_premium_msg_count', 0);
    }

    /**
     * Build context string สำหรับ AI prompt
     * รวม: cards + previous Q&A
     *
     * 🩹 (2026-05-07 review L9) — wrap in try/catch ป้องกัน malformed JSON crash
     */
    public function buildContextForAI(FortuneReading $reading): string
    {
        $parts = [];

        // 10 ใบไพ่ (try/catch — กัน malformed JSON ของ celtic_cards column)
        try {
            $cards = $reading->getCelticCards();
            if (! empty($cards)) {
                $cardLines = [];
                foreach ($cards as $idx => $card) {
                    $position = $card['position_label'] ?? 'ใบที่ '.($idx + 1);
                    $name = $card['name'] ?? $card['card_name'] ?? '';
                    $reversed = ($card['reversed'] ?? false) ? ' (กลับหัว)' : '';
                    $cardLines[] = "  • {$position}: {$name}{$reversed}";
                }
                $parts[] = "🃏 ไพ่ที่เปิดไป 10 ใบ:\n".implode("\n", $cardLines);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Celtic Premium: getCelticCards ล้มเหลว', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // Q&A history
        try {
            $questions = $reading->celticQuestions ?? null;
            if ($questions === null && method_exists($reading, 'celticQuestions')) {
                $questions = $reading->celticQuestions()->orderBy('sequence')->get();
            }
            if ($questions && $questions->count() > 0) {
                $qaLines = [];
                foreach ($questions as $q) {
                    $qText = mb_substr($q->question ?? '', 0, 150);
                    $aText = mb_substr($q->response ?? '', 0, 300);
                    $qaLines[] = "  Q{$q->sequence}: {$qText}\n  A: {$aText}";
                }
                $parts[] = "📜 คำถาม-คำตอบที่ผ่านมา:\n".implode("\n\n", $qaLines);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Celtic Premium: load Q&A history ล้มเหลว', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return implode("\n\n", $parts);
    }
}
