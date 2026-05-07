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
     * @return array|null null ถ้าไม่เข้าเงื่อนไข / array context ถ้าเข้า
     */
    public function detect(string $platform, string $platformUserId): ?array
    {
        if (! ($this->settings->celtic_premium_chat_enabled ?? true)) {
            return null;
        }

        // ดึง active Celtic reading ของ user
        // 🩹 (2026-05-07 review L8) — exclude COMPLETED/cancelled sessions
        $query = FortuneReading::where('reading_type', 'celtic_cross')
            ->where('is_paid', true)
            ->whereNotNull('celtic_first_answered_at')
            ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
            ->orderByDesc('celtic_first_answered_at');

        if ($platform === 'facebook') {
            $query->where('facebook_user_id', $platformUserId);
        } else {
            $query->where('line_user_id', $platformUserId);
        }

        $reading = $query->first();
        if (! $reading) {
            return null;
        }

        // เช็ค window
        $windowMin = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
        $deadline = $reading->celtic_first_answered_at->copy()->addMinutes($windowMin);
        if (now()->greaterThan($deadline)) {
            return null; // หมดเวลาแล้ว
        }

        // เช็ค trigger condition
        $trigger = $this->settings->celtic_premium_chat_trigger ?? 'after_questions_done';
        $maxQuestions = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $questionsUsed = (int) ($reading->celtic_questions_used ?? 0);

        if ($trigger === 'after_questions_done') {
            // ต้องตอบครบ max_questions ก่อน (หรือ max=0 = unlimited → ไม่เคย trigger)
            if ($maxQuestions === 0 || $questionsUsed < $maxQuestions) {
                return null;
            }
        }
        // 'always_after_q1' = ผ่านเงื่อนไข celtic_first_answered_at not null อยู่แล้ว

        // เช็ค message cap
        $maxMessages = (int) ($this->settings->celtic_premium_chat_max_messages ?? 30);
        $msgCount = $this->getMessageCount($reading->id);
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
     */
    public function incrementMessageCount(int $readingId): int
    {
        $key = "fortune:celtic_premium_msg:{$readingId}";
        $current = (int) Cache::get($key, 0);
        $next = $current + 1;
        Cache::put($key, $next, 3600); // 1 hour TTL — ครอบคลุม window

        return $next;
    }

    /**
     * ดึง message count
     */
    public function getMessageCount(int $readingId): int
    {
        return (int) Cache::get("fortune:celtic_premium_msg:{$readingId}", 0);
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
