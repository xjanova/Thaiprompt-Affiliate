<?php

namespace App\Services\Fortune;

use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BirthdateCorrectionTrait — แก้วันเกิดแล้วทำนายใหม่ (1 ครั้งต่อบิล)
 *
 * 🎂 (2026-07-25, owner) "เจ้าชะตาแย้งว่าวันเกิดผิด ควรทำนายให้ใหม่ แต่เปลี่ยนได้ครั้งเดียวต่อบิล"
 *
 * ปัญหาเดิม: ลูกค้าจ่ายแล้วบอก "วันเกิดผิด" →
 *   - อยู่ใน Pro Session → AI ตอบคุยเฉยๆ ทำนายจากดวงเดิม (ลูกค้าเสียเงินฟรี)
 *   - หมดเวลา session → ตกไป parseStandaloneBirthdate → บอท "ขายซ้ำ 39 บาท" (เคสร้องเรียน)
 *
 * Flow ใหม่ (ทุกขั้นตอบด้วย reply — ไม่เพิ่ม push):
 *   1. ลูกค้าแย้ง "วันเกิดผิด" → ถามวันเกิดที่ถูกต้อง (ถ้าพิมพ์มาพร้อมกันเลย → ข้ามไปข้อ 2)
 *   2. บอทยืนยัน "จะทำนายใหม่ด้วยวันเกิด X — ใช้สิทธิ์ครั้งเดียวของบิลนี้" + ปุ่มยืนยัน/ยกเลิก
 *   3. ยืนยัน → reset ทุก flag + ล้าง cache lock + dispatch ทำนายใหม่
 *
 * โควต้า: `birthdate_correction_count` < 1 (pattern เดียวกับ celtic_shuffle_count)
 */
trait BirthdateCorrectionTrait
{
    /**
     * ย้อนหลังได้ไม่เกินกี่ชั่วโมงหลังสร้างบิล
     *
     * ⚠️ ต้อง < 24 ชม. เสมอ — cron `fortune:check-pending` (MAX_WAIT 1440 นาที) คือ safety net
     *    ถ้า dispatch ทำนายใหม่ล้มเหลว. ตั้งเกิน 24 ชม. = บิลที่พลาดจะไม่มีใครเก็บตก
     *    (ลูกค้าเสียสิทธิ์ + ไม่ได้คำทำนาย)
     */
    protected const BIRTHDATE_CORRECTION_WINDOW_HOURS = 20;

    /** flag "รอวันเกิดใหม่" ค้างได้กี่นาที — เกินนี้ถือว่าเลิกสนใจแล้ว ปล่อย flow ปกติ */
    protected const BIRTHDATE_CORRECTION_PENDING_MINUTES = 30;

    /**
     * ตรวจว่าลูกค้ากำลังแย้งว่าวันเกิดผิด / ขอเปลี่ยนวันเกิด
     *
     * ⚠️ ต้องใช้ "คำติดกัน" (adjacency) ห้ามเช็คแบบ substring หลวมๆ
     *   เดิม (วันเกิด + คำว่า ผิด/เปลี่ยน/ใหม่ ที่ไหนก็ได้ในประโยค) false-positive หนัก:
     *     "เปลี่ยนงานใหม่ดีไหม วันเกิด 5 ธค" / "ปีนี้วันเกิดจะได้แฟนใหม่ไหม" / "วันเกิดลูกผิดไหม"
     *   → คำถามทำนายที่ลูกค้าจ่ายเงินถามถูกกลืนหายเข้า flow แก้วันเกิด (เสีย turn + ติดค้าง)
     */
    protected function looksLikeBirthdateCorrectionRequest(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        $bd = '(?:วันเกิด|วันเดือนปีเกิด|วดป\.?|เกิดวันที่)';

        return (bool) (
            // "วันเกิด(ของฉัน)(มัน)(ยัง)ผิด / ไม่ถูก / ไม่ใช่"
            preg_match('/'.$bd.'\s*(?:ของ(?:ฉัน|ผม|หนู|เรา|ดิฉัน)\s*)?(?:มัน|นั้น|นี่|นี้)?\s*(?:ยัง)?(?:ผิด|ไม่ถูก|ไม่ใช่|คลาดเคลื่อน|ผิดพลาด)/u', $t)
            // "เปลี่ยน / แก้ / อัปเดต วันเกิด"
            || preg_match('/(?:เปลี่ยน|แก้ไข|แก้|อัพเดท|อัปเดต|ขอแก้|ขอเปลี่ยน)\s*'.$bd.'/u', $t)
            // "วันเกิดที่ถูกต้องคือ ..."
            || preg_match('/'.$bd.'\s*(?:ที่ถูก(?:ต้อง)?|ที่แท้จริง)/u', $t)
            // "ใส่ / กรอก / บอก วันเกิดผิด"
            || preg_match('/(?:ใส่|กรอก|บอก|พิมพ์)\s*'.$bd.'\s*(?:ผิด|ไม่ถูก)/u', $t)
        );
    }

    /**
     * เช็คโควต้าแก้วันเกิดของบิลนี้ (1 ครั้ง/บิล)
     */
    protected function canCorrectBirthdateAgain(FortuneReading $reading): bool
    {
        return (int) $reading->getConversationState('birthdate_correction_count', 0) < 1;
    }

    /**
     * หา reading Deep ที่ทำนายเสร็จแล้วของลูกค้าคนนี้ (ภายในกรอบเวลาที่ยอมให้แก้)
     *
     * ใช้ตอนลูกค้าแย้งนอก Pro Session (status = COMPLETED)
     */
    protected function findRecentDeepReadingForCorrection(string $userId): ?FortuneReading
    {
        return FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('is_paid', true)
            ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
            ->whereNotNull('deep_response')
            ->where('deep_response', '!=', '')
            ->where('created_at', '>=', now()->subHours(self::BIRTHDATE_CORRECTION_WINDOW_HOURS))
            ->latest()
            ->first();
    }

    /**
     * หาบิลเป้าหมายสำหรับ flow แก้วันเกิด (ใช้นอก Pro Session — status COMPLETED)
     *
     * คืน reading เมื่อ:
     *   (ก) ลูกค้าเพิ่งแย้งว่าวันเกิดผิด หรือ
     *   (ข) บิลนั้นค้างอยู่กลาง flow (รอวันเกิดใหม่ / รอยืนยัน) → ข้อความถัดไปต้องเข้า flow เดิม
     *       แม้จะเป็นแค่ "12/05/2515" หรือ "ยืนยัน" ที่ไม่มีคำว่าวันเกิด
     *
     * @return FortuneReading|null null = ไม่เกี่ยวกับ flow นี้ (ปล่อย flow เดิมทำงาน)
     */
    protected function resolveBirthdateCorrectionTarget(string $userId, string $messageText): ?FortuneReading
    {
        $isRequest = $this->looksLikeBirthdateCorrectionRequest($messageText);

        // ข้อความสั้นๆ ที่อาจเป็นคำตอบกลาง flow (วันเกิด/ยืนยัน/ปฏิเสธ) → ต้องเช็ค pending
        $mayBeFollowUp = mb_strlen(trim($messageText)) <= 40;

        if (! $isRequest && ! $mayBeFollowUp) {
            return null;
        }

        $reading = $this->findRecentDeepReadingForCorrection($userId);
        if ($reading === null) {
            return null;
        }

        if ($isRequest) {
            return $reading;
        }

        // ไม่ใช่คำขอใหม่ → เข้า flow ต่อเมื่อบิลนี้ค้างอยู่กลางทางจริงๆ + ยังไม่หมดอายุ
        $pending = (bool) $reading->getConversationState('birthdate_correction_awaiting', false)
            || ! empty($reading->getConversationState('birthdate_correction_pending_date'));

        return $pending && $this->birthdateCorrectionPendingStillValid($reading) ? $reading : null;
    }

    /**
     * flag "รอวันเกิดใหม่/รอยืนยัน" ยังไม่หมดอายุใช่ไหม
     *
     * กันเคสลูกค้าทิ้งไว้ค้างข้ามวัน แล้วข้อความปกติทุกอันถูกดูดเข้า flow แก้วันเกิด
     */
    protected function birthdateCorrectionPendingStillValid(FortuneReading $reading): bool
    {
        $at = $reading->getConversationState('birthdate_correction_pending_at');
        if (empty($at)) {
            return true; // ของเก่าที่ยังไม่มี timestamp — ไม่ตัดสิทธิ์
        }

        try {
            return \Carbon\Carbon::parse($at)->diffInMinutes(now(), true) <= self::BIRTHDATE_CORRECTION_PENDING_MINUTES;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * จัดการคำขอแก้วันเกิด (ทุกกรณี) — คืน null ถ้าไม่เกี่ยวกับเรื่องนี้ ให้ flow เดิมทำงานต่อ
     *
     * @param  FortuneReading  $reading  บิลที่ทำนายเสร็จแล้ว
     * @param  string  $messageText  ข้อความลูกค้า
     * @return array|null response array หรือ null (= ไม่ใช่เคสนี้)
     */
    protected function handleBirthdateCorrection(FortuneReading $reading, string $messageText): ?array
    {
        $trimmed = trim($messageText);

        // ── ขั้นที่ 3: รอยืนยันก่อนทำนายใหม่ ──────────────────────────
        $pendingDate = $reading->getConversationState('birthdate_correction_pending_date');
        if (! empty($pendingDate)) {
            if ($this->isBirthdateCorrectionConfirmed($trimmed)) {
                return $this->applyBirthdateCorrection($reading, (string) $pendingDate);
            }

            // ปฏิเสธ → ยกเลิกคำขอ (ไม่กินโควต้า)
            if ($this->isBirthdateCorrectionDeclined($trimmed)) {
                $reading->setConversationState('birthdate_correction_pending_date', null);
                $reading->setConversationState('birthdate_correction_awaiting', false);

                return [
                    'action' => 'birthdate_correction_cancelled',
                    'message' => "🙏 ยกเลิกการแก้วันเกิดแล้วนะคะ — ใช้คำทำนายเดิมต่อได้เลยค่ะ\n\n"
                        .'ถ้าเปลี่ยนใจ พิมพ์ "วันเกิดผิด" ได้อีกครั้ง (ยังไม่ได้ใช้สิทธิ์ค่ะ)',
                    'reading' => $reading,
                ];
            }

            // พิมพ์วันเกิดใหม่มาอีก (เปลี่ยนใจก่อนยืนยัน) → อัปเดตตัวที่รอยืนยัน
            $newDate = $this->extractBirthdateFromCorrectionMessage($trimmed);
            if (! empty($newDate)) {
                return $this->askBirthdateCorrectionConfirm($reading, $newDate);
            }

            // อย่างอื่น → ย้ำให้ยืนยัน
            return [
                'action' => 'birthdate_correction_confirm',
                'message' => '🙏 ยืนยันไหมคะว่าจะให้แม่หมอทำนายใหม่ด้วยวันเกิด *'
                    .$this->formatThaiDate((string) $pendingDate)."*\n\n"
                    .'กดปุ่มด้านล่าง หรือพิมพ์ "ยืนยัน" / "ไม่ใช่" ค่ะ',
                'reading' => $reading,
                'show_quick_replies' => true,
                'quick_replies' => $this->birthdateCorrectionConfirmQuickReplies(),
            ];
        }

        // ── ขั้นที่ 2: รอรับวันเกิดใหม่ ─────────────────────────────────
        if ((bool) $reading->getConversationState('birthdate_correction_awaiting', false)) {
            $newDate = $this->extractBirthdateFromCorrectionMessage($trimmed);
            if (! empty($newDate)) {
                return $this->askBirthdateCorrectionConfirm($reading, $newDate);
            }

            // ลูกค้าเปลี่ยนใจ ไม่แก้แล้ว
            if ($this->isBirthdateCorrectionDeclined($trimmed)) {
                $reading->setConversationState('birthdate_correction_awaiting', false);

                return [
                    'action' => 'birthdate_correction_cancelled',
                    'message' => '🙏 ได้ค่ะ ใช้คำทำนายเดิมต่อได้เลยนะคะ',
                    'reading' => $reading,
                ];
            }

            return [
                'action' => 'birthdate_correction_ask',
                'message' => "🎂 ขอ*วันเดือนปีเกิดที่ถูกต้อง*อีกครั้งนะคะ\n\n"
                    ."📝 *ตัวอย่าง:* 15 มีนาคม 2538 หรือ 15/3/2538\n\n"
                    .'_(พิมพ์ "ไม่แก้แล้ว" ถ้าเปลี่ยนใจค่ะ)_',
                'reading' => $reading,
            ];
        }

        // ── ขั้นที่ 1: ลูกค้าเพิ่งแย้งว่าวันเกิดผิด ────────────────────
        if (! $this->looksLikeBirthdateCorrectionRequest($trimmed)) {
            return null;
        }

        // หมดโควต้าแล้ว → อธิบายอย่างสุภาพ (ไม่ปล่อยให้ AI ตอบมั่ว)
        if (! $this->canCorrectBirthdateAgain($reading)) {
            $current = $reading->birth_date?->format('Y-m-d');

            return [
                'action' => 'birthdate_correction_denied',
                'message' => "🙏 บิลนี้ใช้สิทธิ์แก้วันเกิดไปแล้วค่ะ (แก้ได้ 1 ครั้งต่อบิล)\n\n"
                    .(! empty($current) ? '🎂 วันเกิดที่ใช้ทำนายตอนนี้: *'.$this->formatThaiDate($current)."*\n\n" : '')
                    .'หากวันเกิดยังไม่ถูก แม่หมอแนะนำให้เริ่มบิลใหม่ หรือพิมพ์ "ขอคุยกับคน" เพื่อให้แอดมินช่วยดูให้ค่ะ',
                'reading' => $reading,
            ];
        }

        // พิมพ์วันเกิดมาพร้อมกันเลย → ข้ามไปยืนยันทันที (ลดขั้นตอน)
        $inlineDate = $this->extractBirthdateFromCorrectionMessage($trimmed);
        if (! empty($inlineDate)) {
            return $this->askBirthdateCorrectionConfirm($reading, $inlineDate);
        }

        $reading->setConversationState('birthdate_correction_awaiting', true);
        $reading->setConversationState('birthdate_correction_pending_at', now()->toIso8601String());

        Log::info('Fortune: ลูกค้าแย้งว่าวันเกิดผิด — ขอวันเกิดใหม่', [
            'reading_id' => $reading->id,
            'text_preview' => mb_substr($trimmed, 0, 60),
        ]);

        return [
            'action' => 'birthdate_correction_ask',
            'message' => "🙏 ขอโทษด้วยนะคะ เดี๋ยวแม่หมอทำนายใหม่ให้เลย\n\n"
                ."🎂 ขอ*วันเดือนปีเกิดที่ถูกต้อง*ค่ะ\n"
                ."📝 *ตัวอย่าง:* 15 มีนาคม 2538 หรือ 15/3/2538\n\n"
                .'⚠️ _แก้วันเกิดแล้วทำนายใหม่ได้ *1 ครั้งต่อบิล* นะคะ — ตรวจให้ดีก่อนส่งค่ะ_',
            'reading' => $reading,
        ];
    }

    /**
     * ดึงวันเกิดจากประโยคแย้ง (เช่น "วันเกิดผิด ที่ถูกคือ 12/05/2515")
     *
     * ⚠️ ตัดเฉพาะ "ส่วนที่เป็นวันที่" ออกมาก่อน parse — ไม่ส่งทั้งประโยคเข้า parseBirthDate
     *    เพราะ AI fallback อาจเดาเลขอื่นในประโยคเป็นวันเกิด
     */
    protected function extractBirthdateFromCorrectionMessage(string $text): ?string
    {
        // รูปแบบ dd/mm/yyyy | dd-mm-yyyy | dd.mm.yyyy
        if (preg_match('/\d{1,2}\s*[\/\-.]\s*\d{1,2}\s*[\/\-.]\s*\d{2,4}/u', $text, $m)) {
            return $this->parseBirthDate($m[0]);
        }

        // รูปแบบ "15 มีนาคม 2538" / "15 มี.ค. 38"
        // ⚠️ ต้องใช้ช่วง \x{0E01}-\x{0E4E} (พยัญชนะ+สระ+วรรณยุกต์) — [ก-ฮ] คือ \x{0E01}-\x{0E2E}
        //   ซึ่ง **ไม่รวมสระ** → "มีนาคม" (มี ี), "พฤษภาคม" (มี ฤ ษ า) จับไม่ได้เลย
        //   เคสจริง: บอทบอกตัวอย่าง "15 มีนาคม 2538" ลูกค้าพิมพ์ตาม → parse ไม่ได้ → วนถามซ้ำไม่จบ
        if (preg_match('/\d{1,2}\s*[\x{0E01}-\x{0E4E}][\x{0E01}-\x{0E4E}.\s]{1,14}\s*\d{2,4}/u', $text, $m)) {
            return $this->parseBirthDate($m[0]);
        }

        return null;
    }

    /**
     * ถามยืนยันก่อนใช้สิทธิ์แก้วันเกิด
     */
    protected function askBirthdateCorrectionConfirm(FortuneReading $reading, string $newDate): array
    {
        $reading->setConversationState('birthdate_correction_pending_date', $newDate);
        $reading->setConversationState('birthdate_correction_awaiting', false);
        $reading->setConversationState('birthdate_correction_pending_at', now()->toIso8601String());

        $old = $reading->birth_date?->format('Y-m-d');
        $oldLine = ! empty($old) && $old !== $newDate
            ? '🔸 เดิม: '.$this->formatThaiDate($old)."\n"
            : '';

        return [
            'action' => 'birthdate_correction_confirm',
            'message' => "🎂 *ตรวจสอบวันเกิดใหม่*\n\n"
                .$oldLine
                .'🔹 ใหม่: *'.$this->formatThaiDate($newDate)."*\n\n"
                ."ถ้าถูกต้องแล้ว แม่หมอจะคำนวณดวงและทำนายให้ใหม่ทั้งหมดค่ะ\n"
                .'⚠️ _ใช้ได้ *ครั้งเดียวต่อบิล* — ยืนยันแล้วเปลี่ยนอีกไม่ได้นะคะ_',
            'reading' => $reading,
            'show_quick_replies' => true,
            'quick_replies' => $this->birthdateCorrectionConfirmQuickReplies(),
        ];
    }

    /**
     * ปุ่มยืนยัน/ยกเลิกการแก้วันเกิด
     */
    protected function birthdateCorrectionConfirmQuickReplies(): array
    {
        return [
            ['title' => '✅ ถูกต้อง ทำนายใหม่', 'text' => 'ยืนยันวันเกิดใหม่', 'payload' => 'ยืนยันวันเกิดใหม่'],
            ['title' => '❌ ยังไม่ใช่', 'text' => 'ไม่แก้แล้ว', 'payload' => 'ไม่แก้แล้ว'],
        ];
    }

    /**
     * ตรวจคำยืนยัน
     */
    protected function isBirthdateCorrectionConfirmed(string $text): bool
    {
        return in_array($this->normalizeBirthdateCorrectionReply($text), [
            'ยืนยันวันเกิดใหม่', 'ยืนยัน', 'ถูกต้อง', 'ถูกแล้ว', 'ถูก', 'ใช่', 'ใช่แล้ว',
            'ตกลง', 'ทำนายใหม่', 'เอาเลย', 'ok', 'okay', 'yes',
        ], true);
    }

    /**
     * ตรวจคำปฏิเสธ
     */
    protected function isBirthdateCorrectionDeclined(string $text): bool
    {
        return in_array($this->normalizeBirthdateCorrectionReply($text), [
            'ไม่แก้แล้ว', 'ไม่แก้', 'ยกเลิก', 'ไม่ใช่', 'ไม่ต้อง', 'ไม่เอา', 'พอแล้ว', 'ไม่', 'no',
        ], true);
    }

    /**
     * ตัดคำลงท้ายสุภาพออกก่อนเทียบ — "ยืนยันค่ะ" / "ใช่ครับผม" / "ตกลงนะคะ" ต้องผ่าน
     */
    protected function normalizeBirthdateCorrectionReply(string $text): string
    {
        $t = mb_strtolower(trim($text));
        // ตัดเครื่องหมาย/ตัวซ้ำท้ายก่อน
        // ⚠️ ห้ามใช้ trim($t, '...ๆฯ') — trim ตัดทีละ "ไบต์" ไม่ใช่ตัวอักษร
        //    ตัวไทยเป็น UTF-8 3 ไบต์ → ไบต์แรก (0xE0) ของ ๆ/ฯ ไปตัดหัวคำไทยอื่นพัง
        $t = (string) preg_replace('/[\s.!?ๆฯ]+$/u', '', $t);
        // ตัดคำลงท้ายสุภาพ (ซ้อนกันได้ เช่น "ใช่ครับผม" → "ใช่")
        $t = (string) preg_replace('/\s*(ครับผม|ครับ|คร้าบ|ค่ะ|คะ|ค่า|จ้า|จ้ะ|นะคะ|นะครับ|นะ|เลย)+\s*$/u', '', $t);

        return trim($t);
    }

    /**
     * ✅ ใช้สิทธิ์แก้วันเกิด — reset ทุกอย่างแล้วสั่งทำนายใหม่
     *
     * ⚠️ ต้องล้างให้ครบ ไม่งั้น Job จะ skip เงียบ:
     *   - deep_response (early-return guard ของ Job)
     *   - reading_image_url (ไม่งั้นได้ chart ดวงเดิม)
     *   - delivery flags (ไม่งั้นถือว่าส่งแล้ว)
     *   - pro_session (ปิด session เดิม + กัน cron ส่ง "หมดเวลา" ทับ)
     *   - cache lock 3 ตัว (deep_gen / deep_deliver / deep_dispatch)
     */
    protected function applyBirthdateCorrection(FortuneReading $reading, string $newDate): array
    {
        // 🔒 กันกดยืนยันรัวๆ → ทำนายใหม่ซ้อน (atomic lock 60 วิ)
        if (! Cache::add("fortune:birthdate_fix:{$reading->id}", 1, 60)) {
            return [
                'action' => 'birthdate_correction_processing',
                'message' => '🌙 แม่หมอกำลังคำนวณดวงใหม่ให้อยู่นะคะ รอสักครู่ค่ะ ✨',
                'reading' => $reading,
            ];
        }

        // 🛡️ double-check โควต้าหลังได้ lock (กัน race)
        if (! $this->canCorrectBirthdateAgain($reading)) {
            return [
                'action' => 'birthdate_correction_denied',
                'message' => '🙏 บิลนี้ใช้สิทธิ์แก้วันเกิดไปแล้วค่ะ (แก้ได้ 1 ครั้งต่อบิล)',
                'reading' => $reading,
            ];
        }

        $existingState = is_array($reading->conversation_state) ? $reading->conversation_state : [];
        $newState = array_merge($existingState, [
            // ใช้สิทธิ์ (ต่อจากนี้แก้ไม่ได้อีก)
            'birthdate_correction_count' => (int) ($existingState['birthdate_correction_count'] ?? 0) + 1,
            'birthdate_correction_pending_date' => null,
            'birthdate_correction_awaiting' => false,
            'birthdate_correction_previous' => $reading->birth_date?->format('Y-m-d'),
            'birthdate_correction_at' => now()->toIso8601String(),

            // ล้าง delivery flags — ให้ Job ส่งคำทำนายใหม่ได้
            'reading_sent_directly' => false,
            'reading_notification_sent' => false,
            'reading_notification_attempted' => false,
            'reading_notification_retry_count' => 0,
            'reading_ready_sent' => false,
            'reading_ready_for_reply' => false,
            'delivered_by_push' => false,
            'delivered_by_reply_message' => false,
            'ai_failed_alert' => false,
            // ⭐ รีเซ็ตตัวนับ retry ของ cron ด้วย — ไม่งั้นบิลที่เคยถูก retry ครบ 5 ครั้ง
            //   จะไม่มี safety net เหลือถ้า dispatch รอบนี้ล้ม (fortune:check-pending ข้ามที่ MAX_AUTO_RETRIES)
            'auto_retry_count' => 0,
            'last_auto_retry_at' => null,

            // ปิด Pro Session เดิม — ให้ processPaymentConfirmed เปิด session ใหม่ตอนส่งคำทำนาย
            //   ⚠️ ห้ามตั้ง pro_session_timeout_notified = true: enterProSession ไม่ล้าง flag นี้
            //   → session รอบใหม่จะไม่ได้ข้อความปิดท้าย "หมดเวลาทำนาย" + ไม่ได้ชวนรีวิว
            //   (pro_session_active=false พอแล้วสำหรับกัน cron ยิงระหว่างกำลัง gen)
            'pro_session_active' => false,
            'pro_session_pending_exit' => false,
            'pro_session_awaiting_first_question' => false,
            'pro_session_started_at' => null,
            'pro_session_history' => [],
            'pro_session_timeout_notified' => false,
            'pro_session_nudge_sent' => false,
        ]);

        $reading->update([
            'birth_date' => $newDate,
            'deep_response' => null,
            'ai_response' => null,
            'reading_image_url' => null,   // ⭐ ไม่ล้าง = ได้รูปดวงของวันเกิดเดิม
            'conversation_status' => FortuneReading::STATUS_PAID,
            'conversation_state' => $newState,
        ]);

        // ล้าง lock ของรอบก่อน — ไม่งั้น Job/dispatch มองว่ากำลังทำอยู่แล้วข้ามเงียบ
        Cache::forget("fortune:deep_gen:{$reading->id}");
        Cache::forget("fortune:deep_deliver:{$reading->id}");
        Cache::forget("fortune:deep_dispatch:{$reading->id}");

        Log::info('Fortune: ✅ แก้วันเกิด + สั่งทำนายใหม่ (ใช้สิทธิ์ 1 ครั้ง/บิล)', [
            'reading_id' => $reading->id,
            'bill' => $reading->bill_reference,
            'birth_date_old' => $newState['birthdate_correction_previous'] ?? null,
            'birth_date_new' => $newDate,
        ]);

        // dispatch ทำนายใหม่ (resolve platform แบบเดียวกับ fortune:retry-reading)
        try {
            $platformUserId = $reading->platform_user_id ?: $reading->facebook_user_id;
            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', (string) $platformUserId) ? 'line' : 'facebook');

            ProcessDeepFortuneReadingJob::dispatchSmart($reading->id, null, $platform, $platformUserId);
        } catch (\Throwable $e) {
            // ⚠️ โควต้าถูกใช้ไปแล้ว + deep_response ถูกล้าง = ลูกค้ามือเปล่าถ้าไม่มีใครเก็บตก
            //   safety net: status=PAID + auto_retry_count=0 → cron fortune:check-pending รับช่วง (ภายใน 24 ชม.)
            //   ยกระดับเป็น critical เพื่อให้ทีมเห็นทันทีถ้า cron ก็ล้มด้วย
            Log::critical('Fortune: แก้วันเกิดแล้ว dispatch ทำนายใหม่ล้มเหลว — รอ cron check-pending เก็บตก', [
                'reading_id' => $reading->id,
                'bill' => $reading->bill_reference,
                'error' => $e->getMessage(),
                'hint' => 'ถ้า cron ไม่เก็บ ให้รัน: php artisan fortune:retry-reading '.$reading->id,
            ]);
        }

        return [
            'action' => 'birthdate_correction_applied',
            'message' => '🎂 *แก้วันเกิดเป็น '.$this->formatThaiDate($newDate)." แล้วค่ะ* ✨\n\n"
                ."🌙 แม่หมอกำลังคำนวณดวงใหม่ทั้งหมด แล้วจะส่งคำทำนายมาให้นะคะ\n"
                .'⏳ ใช้เวลาสักครู่ — รอรับได้เลยค่ะ 🙏',
            'reading' => $reading,
        ];
    }
}
