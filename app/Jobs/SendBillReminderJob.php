<?php

namespace App\Jobs;

use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 💸 (2026-05-14) Bill Reminder — บอทตามทวงลูกค้าที่สร้างบิลแล้วไม่โอน
 *
 * Trigger: `fortune:bill-reminder` command — ทุก 5 นาที ผ่าน scheduler
 * เงื่อนไข: บิล pending payment + อายุ 20-60 นาที + UPA ยังไม่ expire + ยังไม่เคยทวง
 *
 * Behavior:
 *   - Load FortuneCustomerPersona → AI ปรับ tone ตามนิสัยลูกค้า (อ่อนโยน ไม่บีบ)
 *   - Fallback hardcoded text ถ้า AI ใช้ไม่ได้
 *   - ⏰ (2026-06-12) รองรับ 3 จังหวะ (stage 1/2/3) ตลอดอายุบิล 3 ชม. — โทนต่างกันทุกครั้ง
 *     Mark `bill_reminder_stage` ใน conversation_state (เก็บ `bill_reminder_sent_at` ไว้ compat)
 *   - Sanitize box chars ที่ AI อาจ leak
 *
 * ลูกค้าตอบ "ไม่จ่าย" / "ยกเลิก" → isCancelRequest จับ → ปิดบิล + ขอบคุณ (existing flow)
 */
class SendBillReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public int $readingId, public int $stage = 1) {}

    public function handle(): void
    {
        $reading = FortuneReading::with('uniquePaymentAmount')->find($this->readingId);
        if (! $reading || $reading->is_paid) {
            return; // safety — บิลจ่ายแล้ว
        }

        // 🩹 (2026-06-07) ลูกค้าส่งสลิปแล้วกำลังรอ SlipOK ตรวจ → อย่าทวง "ยังไม่จ่าย/ติดขัดการโอน"
        //   ไม่งั้นดูเหมือนระบบไม่เห็นเงิน ลูกค้างง + แอดมินต้องเข้าช่วย
        //   (เคส FTU-260607-P3861: ลูกค้าส่งสลิป 21:23:50 แต่บอททวง 21:25:20 ทั้งที่สลิปรอตรวจอยู่)
        //   หมายเหตุ: approve แล้ว is_paid=true (เด้งด้านบน) → เงื่อนไขนี้ครอบเฉพาะ "สลิปรอตรวจ/เพิ่งเข้า fallback"
        if (! empty($reading->slip_received_at) && empty($reading->slipok_verified_at)) {
            Log::debug('SendBillReminderJob: มีสลิปรอ SlipOK ตรวจ — skip ทวง', [
                'reading_id' => $reading->id,
            ]);

            return;
        }

        // 🌙 (2026-05-24) Branch ตาม state:
        //   - AWAITING_PAYMENT_METHOD → ยังไม่มี UPA — nudge ให้กดปุ่มเลือกวิธีจ่าย
        //   - PENDING_PAYMENT / CELTIC_PENDING_PAYMENT → require UPA reserved ยังไม่หมดอายุ
        $isAwaitingMethod = $reading->conversation_status === FortuneReading::STATUS_AWAITING_PAYMENT_METHOD;

        if (! $isAwaitingMethod) {
            $upa = $reading->uniquePaymentAmount;
            if (! $upa || $upa->expires_at <= now()) {
                return; // บิลหมดอายุ — ไม่ต้องทวง
            }
        }

        // 🩹 Dedup re-check — กัน race (command + queue overlap)
        // ⏰ (2026-06-12) stage-aware: stage ที่ ≤ stage ที่ส่งไปแล้ว → skip
        //   (awaiting_payment_method = one-shot เดิม — เช็ค bill_reminder_sent_at)
        $sentStage = (int) $reading->getConversationState('bill_reminder_stage', 0);
        if ($sentStage === 0 && $reading->getConversationState('bill_reminder_sent_at')) {
            $sentStage = 1;
        }
        if ($sentStage >= $this->stage) {
            Log::debug('SendBillReminderJob: ส่งทวง stage นี้แล้ว — skip', [
                'reading_id' => $reading->id,
                'stage' => $this->stage,
                'sent_stage' => $sentStage,
            ]);

            return;
        }

        // Determine platform + user id
        // 🩹 (2026-06-12 จับผี #8) fortune_readings ไม่มีคอลัมน์ line_user_id (hotfix 2026-05-08)
        //   เดิม: $reading->line_user_id = null เสมอ → platform='facebook' ตลอด → reminder LINE ตายเงียบ
        //   ใหม่: ใช้คอลัมน์ platform เป็นหลัก + fallback ตรวจ format LINE userId (U + hex 32 ตัว)
        $userId = $reading->facebook_user_id ?: $reading->platform_user_id;
        if (empty($userId)) {
            return;
        }
        $platform = $reading->platform
            ?: (preg_match('/^U[0-9a-f]{32}$/i', (string) $userId) ? 'line' : 'facebook');

        // Load persona context (optional)
        $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
        $personaContext = $persona ? $persona->toAiContextBlock() : '';

        // AI generate → fallback hardcoded
        $message = $this->generateAiReminder($reading, $personaContext) ?? $this->getFallbackText($reading);

        try {
            $this->sendMessage($platform, $userId, $message);

            // Mark stage ที่ส่งแล้ว (+ sent_at compat กับโค้ดเก่า)
            $reading->setConversationState('bill_reminder_stage', $this->stage);
            $reading->setConversationState('bill_reminder_sent_at', now()->toIso8601String());

            Log::info('SendBillReminderJob: ส่งสำเร็จ', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'stage' => $this->stage,
                'reading_type' => $reading->reading_type,
                'amount' => $reading->amount_paid,
                'has_persona' => $persona !== null,
                'used_fallback' => str_starts_with($message, '🌙 ลูกศิษย์'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendBillReminderJob: ส่งล้มเหลว', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🤖 AI generate reminder — null ถ้าใช้ไม่ได้ (caller จะ fallback)
     */
    private function generateAiReminder(FortuneReading $reading, string $personaContext): ?string
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $aiService = new FortuneAIService($settings);

            $isAwaitingMethod = $reading->conversation_status === FortuneReading::STATUS_AWAITING_PAYMENT_METHOD;
            $systemMessage = $this->buildSystemMessage($personaContext, $isAwaitingMethod);
            $userMessage = $this->buildUserMessage($reading, $isAwaitingMethod);

            // 📚 (2026-05-24) Inject RAG admin Q&A few-shot — เรียน tone จากแอดมินที่เคยตอบ
            //   category=pre_payment (ครอบ awaiting_payment_method + pending_payment + celtic_pending_payment)
            //   query = last customer message ใน history (ถ้ามี) — fallback เป็น descriptor สถานะ
            try {
                $queryText = $this->extractRagQueryText($reading, $isAwaitingMethod);
                $systemMessage = $aiService->injectAdminQARagFewShot($systemMessage, $queryText, $reading);
            } catch (\Throwable $e) {
                // RAG ล้ม → ใช้ system message เดิม (ไม่ break job)
                Log::debug('SendBillReminderJob: RAG inject ล้ม fallback no-RAG', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $directKey = $settings->getChatAIApiKey();

            if (! empty($directKey)) {
                $result = $aiService->chatWithCustomSystemPrompt(
                    $systemMessage,
                    $userMessage,
                    ['temperature' => 0.85, 'max_tokens' => 400]
                );
            } else {
                // Pool fallback
                $result = $this->callViaPool($aiService, $systemMessage, $userMessage);
                if ($result === null) {
                    return null;
                }
            }

            $text = trim($result['response'] ?? '');

            if (empty($text) || mb_strlen($text) < 30) {
                return null;
            }

            // Sanitize ━ + bullet
            $text = preg_replace('/━+/u', '', $text);
            $text = preg_replace('/^[\s\n]*[•\-\*]\s+/mu', '', $text);

            return trim($text);
        } catch (\Throwable $e) {
            Log::warning('SendBillReminderJob: AI ล้มเหลว — fallback', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🏊 Pool fallback — chat purpose key
     */
    private function callViaPool(FortuneAIService $aiService, string $systemMessage, string $userMessage): ?array
    {
        try {
            $poolService = app(\App\Services\AiApiKeyPoolService::class);
            $key = $poolService->acquireKeyAnyProvider('chat');

            if (! $key) {
                return null;
            }

            return $aiService->chatWithCustomSystemPrompt(
                $systemMessage,
                $userMessage,
                ['temperature' => 0.85, 'max_tokens' => 400],
                $key->provider,
                $key->model,
                $key->api_key
            );
        } catch (\Throwable $e) {
            Log::warning('SendBillReminderJob: Pool fallback ล้มเหลว', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 🔍 (2026-05-24) สร้าง query text สำหรับ RAG retrieve admin Q&A
     *
     * Strategy:
     *   1. ดึงข้อความล่าสุดของลูกค้าจาก conversation history (LineBotConversation)
     *   2. ถ้าไม่มี → ใช้ descriptor ของ state เป็น query (จะ match กับ admin Q&A category=pre_payment)
     */
    private function extractRagQueryText(FortuneReading $reading, bool $isAwaitingMethod): string
    {
        try {
            $platform = ! empty($reading->line_user_id) ? 'line' : 'facebook';
            $userId = $reading->facebook_user_id ?: $reading->line_user_id ?: $reading->platform_user_id;
            if (! empty($userId)) {
                $conv = \App\Models\LineBotConversation::where('line_user_id', $userId)
                    ->where('platform', $platform)
                    ->first();
                if ($conv && ! empty($conv->history)) {
                    $history = is_array($conv->history) ? $conv->history : json_decode($conv->history, true);
                    if (is_array($history)) {
                        // หา user message ล่าสุด
                        for ($i = count($history) - 1; $i >= 0; $i--) {
                            $msg = $history[$i] ?? null;
                            if (is_array($msg) && ($msg['role'] ?? '') === 'user') {
                                $text = trim((string) ($msg['content'] ?? ''));
                                if (mb_strlen($text) >= 2) {
                                    return mb_substr($text, 0, 300);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Fallback — ใช้ descriptor ของ state (RAG จะดึง pre_payment category ตาม classifier)
        return $isAwaitingMethod
            ? 'ลูกค้าเห็นปุ่มเลือกวิธีชำระเงิน QR/บัตรเครดิต แต่ยังไม่ได้เลือก ไม่ตัดสินใจ'
            : 'ลูกค้าสร้างบิลแล้ว แต่ยังไม่ได้โอนเงิน';
    }

    private function buildSystemMessage(string $personaContext, bool $isAwaitingMethod = false): string
    {
        $personaBlock = ! empty($personaContext) ? "\n\n{$personaContext}\n" : '';

        // ⏰ (2026-06-12) สถานการณ์ตาม stage — โทนต่างกัน ไม่ใช่กล่องเดิมซ้ำ
        $situationLine = match (true) {
            $isAwaitingMethod => 'สถานการณ์: ลูกค้าเห็นเมนูเลือกวิธีชำระเงิน (QR ไทย / บัตรเครดิต) แต่ยังไม่ได้กดเลือก ผ่านมาสักพัก',
            $this->stage >= 3 => 'สถานการณ์: ลูกค้าสร้างบิลดูดวงไว้นานแล้วยังไม่โอน — บิลใกล้หมดอายุเต็มที (นี่คือการเตือนครั้งสุดท้าย ต้องบอกตรงๆ อย่างสุภาพว่าบิลกำลังจะถูกยกเลิกอัตโนมัติ)',
            $this->stage === 2 => 'สถานการณ์: ลูกค้าสร้างบิลดูดวงไว้ราวชั่วโมงแล้วยังไม่โอน — เคยทักไปครั้งหนึ่งแล้ว รอบนี้ทักเช็คอินเบาๆ มุมใหม่ อย่าซ้ำคำเดิม',
            default => 'สถานการณ์: ลูกค้าสร้างบิลดูดวงไว้แต่ยังไม่ได้โอนเงิน ผ่านมาสักพัก',
        };

        // 🗣️ (2026-06-02) ตอบ "เหมือนแอดมินตัวจริงตอบ" — อิงตัวอย่างจริงจาก RAG admin เป็นหลัก
        //   ไม่แต่ง persona อบอุ่น/benefit เอง (user: "ไปดูที่แอดมินตอบไว้ ตอบคล้ายแอดมินเลย")
        return <<<EOT
คุณคือ "แอดมินเพจดูดวงแม่หมอจันทรา" กำลังพิมพ์ตอบลูกค้าใน DM ด้วยตัวเอง

{$situationLine}

วิธีตอบ (สำคัญที่สุด):
- ด้านล่างจะมี "📚 ตัวอย่างคำตอบจริงของแอดมิน" ในสถานการณ์คล้ายกัน
  → ตอบให้ "เหมือนแอดมินตัวจริงตอบ" มากที่สุด: ใช้สำนวน น้ำเสียง และแนวทางเดียวกับตัวอย่าง
  → ยึดวิธีที่แอดมินตอบเป็นหลัก ไม่ต้องแต่งเติมความอบอุ่น/บทกวี/benefit เพิ่มเอง
- ถ้าไม่มีตัวอย่างให้ดู → ทักสั้น ๆ เป็นกันเอง ถามว่าติดขัดตรงไหนช่วยได้ไหม

กฎเหล็ก (ห้ามฝ่าฝืน):
- ✅ สั้น 1-3 ประโยค เหมือนคนพิมพ์แชทจริง (ไม่มี ━ ไม่มี bullet ไม่มี heading)
- ✅ แม่หมอเป็นผู้หญิง — ใช้ "ค่ะ/นะคะ" เท่านั้น ห้าม "ครับ/ผม" เด็ดขาด
- ❌ ห้ามทวงแบบเจ้าหนี้ ("ยังไม่โอน?" "ทำไมไม่จ่าย")
- ❌ ห้ามฮาร์ดเซล ("รีบโอน" "อย่ารอช้า" "พลาดแล้วเสียดาย")
- ❌ ห้ามอธิบายราคา/แพ็คเกจซ้ำ (ลูกค้าเห็นแล้ว)
- ❌ ห้าม corporate-speak ("กรุณา..." "หากท่านมีข้อสงสัย")
{$personaBlock}
ถ้ามีข้อมูล persona ลูกค้าด้านบน ปรับน้ำเสียงให้เข้ากันได้ แต่ไม่อ้างตรง ๆ ว่า "จำได้ว่า..."

ตอบเป็น plain text บรรทัดเดียวต่อเนื่อง (ขึ้นบรรทัดใหม่ได้ถ้าจำเป็น)
EOT;
    }

    private function buildUserMessage(FortuneReading $reading, bool $isAwaitingMethod = false): string
    {
        $isCeltic = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;
        $type = $isCeltic ? 'ไพ่ Celtic Cross 10 ใบ' : 'ดูดวงเชิงลึก';
        $billRef = $reading->bill_reference ?? '-';
        $minutesAgo = (int) $reading->created_at->diffInMinutes(now());

        if ($isAwaitingMethod) {
            // ไม่มี UPA / amount ที่แน่ชัดในสถานะนี้ (ยังไม่เลือกวิธีจ่าย)
            $expectedAmount = (int) ($reading->amount_paid ?? 0);
            $amountLine = $expectedAmount > 0 ? "- คาดว่าจะจ่าย: ~฿{$expectedAmount}\n" : '';

            return "สถานการณ์:\n"
                ."- ลูกค้าเห็นเมนูเลือกวิธีชำระเงิน ({$type}) เมื่อ {$minutesAgo} นาทีก่อน\n"
                .'- มีปุ่ม "QR ไทย" และ "บัตรเครดิต" ให้กด แต่ยังไม่กดเลือก'."\n"
                .$amountLine
                ."- อาจจะลังเล หรือสับสน หรือกำลังหาเงิน\n\n"
                .'ตอบเหมือนแอดมินตอบลูกค้าในจังหวะนี้ (ดูตัวอย่าง 📚 ถ้ามี) — ไม่ทวง ไม่ฮาร์ดเซล';
        }

        $upa = $reading->uniquePaymentAmount;
        $amount = number_format((float) ($upa->amount ?? $reading->amount_paid ?? 0), 2);
        $remainingMin = $upa && $upa->expires_at
            ? max(1, (int) now()->diffInMinutes($upa->expires_at, false))
            : 5;

        // ⏰ (2026-06-12) บอก stage ให้ AI รู้จังหวะ
        $stageHint = match ($this->stage) {
            3 => '- ⚠️ นี่คือการเตือน *ครั้งสุดท้าย* — บอกสุภาพแต่ชัดเจนว่าถ้าไม่โอน บิลจะถูกยกเลิกอัตโนมัติ และแจ้งเวลาที่เหลือ',
            2 => '- นี่คือการทักครั้งที่ 2 — เช็คอินเบาๆ มุมใหม่ ถามว่าติดขัดอะไรไหม อย่าใช้คำเดิมกับครั้งแรก',
            default => '- นี่คือการทักครั้งแรกหลังสร้างบิล',
        };

        return "สถานการณ์:\n"
            ."- ลูกค้าสร้างบิล {$type} ไว้เมื่อ {$minutesAgo} นาทีก่อน\n"
            ."- ยอดที่ต้องโอน: ฿{$amount}\n"
            ."- บิล: {$billRef}\n"
            ."- ยังเหลือเวลา ~{$remainingMin} นาทีก่อนบิลหมดอายุ\n"
            .$stageHint."\n\n"
            .'ตอบเหมือนแอดมินตอบลูกค้าในจังหวะนี้ (ดูตัวอย่าง 📚 ถ้ามี) — ไม่ทวง ไม่กดดัน';
    }

    /**
     * 📜 Fallback text — ใช้เมื่อ AI ใช้ไม่ได้
     */
    private function getFallbackText(FortuneReading $reading): string
    {
        $isAwaitingMethod = $reading->conversation_status === FortuneReading::STATUS_AWAITING_PAYMENT_METHOD;
        $isCeltic = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;
        // 🌙 (2026-05-23) Round 6 — pull ราคาจาก amount_paid ของบิลจริง ไม่ฮาร์ดโค้ด 39/99
        $billAmountInt = $reading->amount_paid
            ? (int) round((float) $reading->amount_paid)
            : null;
        $billAmountSuffix = $billAmountInt ? " ({$billAmountInt}฿)" : '';
        $type = $isCeltic
            ? "ไพ่ Celtic Cross 10 ใบ{$billAmountSuffix}"
            : "ดูดวงเชิงลึก{$billAmountSuffix}";

        // 🌙 (2026-05-24) Branch ตาม state
        if ($isAwaitingMethod) {
            return "🌙 ลูกศิษย์... แม่หมอเห็นว่ายังตัดสินใจอยู่ที่เมนู {$type} นะคะ\n\n"
                ."ติดขัดตรงไหน บอกแม่หมอได้เลยค่ะ ถ้าเลือกไม่ถูก แม่หมอช่วยแนะนำให้\n\n"
                ."💚 ถ้าใช้พร้อมเพย์ในไทย → พิมพ์ 'qr ไทย'\n"
                ."💳 ถ้าใช้บัตรเครดิต/ต่างประเทศ → พิมพ์ 'บัตร'\n\n"
                .'ถ้าไม่สะดวกจะดูตอนนี้ → พิมพ์ \'ยกเลิก\' ได้ ไม่เป็นไรนะคะ 🙏';
        }

        $upa = $reading->uniquePaymentAmount;
        $amount = number_format((float) ($upa->amount ?? $reading->amount_paid ?? 0), 2);
        $billRef = $reading->bill_reference ?? '-';
        $remainingMin = $upa && $upa->expires_at
            ? max(1, (int) now()->diffInMinutes($upa->expires_at, false))
            : 5;

        // ⏰ (2026-06-12) Fallback แยกตาม stage — ไม่ใช่ข้อความเดิมซ้ำ 3 รอบ
        if ($this->stage >= 3) {
            return "🌙 ลูกศิษย์... แม่หมอขอเตือนเป็น *ครั้งสุดท้าย* นะคะ\n\n"
                ."📋 บิล {$type}: {$billRef}\n"
                ."💰 ยอด: ฿{$amount}\n"
                ."⏰ เหลือเวลาอีกเพียง ~{$remainingMin} นาที — หลังจากนั้นระบบจะยกเลิกบิลอัตโนมัติค่ะ\n\n"
                ."ถ้ายังอยากดูอยู่ โอนได้เลยนะคะ แม่หมอเปิดไพ่ให้ทันที ✨\n"
                ."ถ้าไม่สะดวกแล้ว → พิมพ์ 'ยกเลิก' ได้เลย ไม่เป็นไรค่ะ 🙏";
        }

        if ($this->stage === 2) {
            return "🌙 ลูกศิษย์ยังอยู่ไหมคะ... แม่หมอยังเตรียมไพ่รอไว้อยู่นะ\n\n"
                ."ติดขัดเรื่องการโอน หรือมีอะไรให้แม่หมอช่วย พิมพ์ 'ช่วยหน่อย' ได้เลยค่ะ\n"
                ."📋 บิล {$billRef} ยอด ฿{$amount} ยังรออยู่ (เหลือ ~{$remainingMin} นาที)\n\n"
                .'ไม่รีบนะคะ แม่หมอรอได้ ✨';
        }

        return "🌙 ลูกศิษย์... แม่หมอเห็นว่าบิล {$type} ที่ทำไว้ ยังไม่ได้โอนเลยนะคะ\n\n"
            ."📋 บิล: {$billRef}\n"
            ."💰 ยอด: ฿{$amount}\n"
            ."⏰ เหลือเวลา ~{$remainingMin} นาทีก่อนบิลหมดอายุ\n\n"
            ."ถ้ายังตัดสินใจอยู่ ทักมาคุยได้ค่ะ แม่หมอรออยู่ ✨\n"
            ."ถ้าไม่สะดวกจะดูแล้ว → พิมพ์ 'ยกเลิก' ได้เลย ไม่เป็นไรนะคะ 🙏";
    }

    private function sendMessage(string $platform, string $userId, string $message): void
    {
        if ($platform === 'facebook') {
            $fbService = app(\App\Services\FacebookWebhookService::class);
            // ลูกค้าเพิ่งสร้างบิล < 30 นาที → ยังใน 24hr window — ไม่ต้องใช้ message_tag
            $fbService->sendMessage($userId, $message);

            // 🎧 (2026-06-21) เสียงระบบ: กระตุ้นให้จ่ายให้ฟัง (FB only) — best-effort
            try {
                $voiceUrl = (new \App\Services\FortuneSystemVoiceService(\App\Models\FortuneTellingSetting::getSettings()))
                    ->urlFor('payment_reminder');
                if (! empty($voiceUrl)) {
                    $fbService->sendAudio($userId, $voiceUrl);
                }
            } catch (\Throwable $e) {
                // เสียงเป็นแค่ตัวช่วย ไม่ critical
            }
        } elseif ($platform === 'line') {
            $lineService = app(\App\Services\LineFortuneService::class);
            $lineService->sendMessage($userId, $message);
        } else {
            Log::warning('SendBillReminderJob: platform ไม่รู้จัก', ['platform' => $platform]);
        }
    }

    public function displayName(): string
    {
        return "BillReminder[#{$this->readingId}]";
    }

    public function tags(): array
    {
        return [
            'fortune-bill-reminder',
            "reading:{$this->readingId}",
        ];
    }
}
