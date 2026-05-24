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
 *   - Mark `bill_reminder_sent_at` ใน conversation_state — ส่งครั้งเดียวพอ
 *   - Sanitize box chars ที่ AI อาจ leak
 *
 * ลูกค้าตอบ "ไม่จ่าย" / "ยกเลิก" → isCancelRequest จับ → ปิดบิล + ขอบคุณ (existing flow)
 */
class SendBillReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public int $readingId) {}

    public function handle(): void
    {
        $reading = FortuneReading::with('uniquePaymentAmount')->find($this->readingId);
        if (! $reading || $reading->is_paid) {
            return; // safety — บิลจ่ายแล้ว
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
        if ($reading->getConversationState('bill_reminder_sent_at')) {
            Log::debug('SendBillReminderJob: ส่งทวงแล้ว — skip', [
                'reading_id' => $reading->id,
            ]);

            return;
        }

        // Determine platform + user id
        $platform = ! empty($reading->line_user_id) ? 'line' : 'facebook';
        $userId = $reading->facebook_user_id ?: $reading->line_user_id ?: $reading->platform_user_id;
        if (empty($userId)) {
            return;
        }

        // Load persona context (optional)
        $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
        $personaContext = $persona ? $persona->toAiContextBlock() : '';

        // AI generate → fallback hardcoded
        $message = $this->generateAiReminder($reading, $personaContext) ?? $this->getFallbackText($reading);

        try {
            $this->sendMessage($platform, $userId, $message);

            // Mark sent — ส่งครั้งเดียวพอ
            $reading->setConversationState('bill_reminder_sent_at', now()->toIso8601String());

            Log::info('SendBillReminderJob: ส่งสำเร็จ', [
                'reading_id' => $reading->id,
                'platform' => $platform,
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

        $missionLine = $isAwaitingMethod
            ? 'ภารกิจ: ลูกค้าเห็นปุ่มเลือกวิธีชำระเงิน (QR ไทย / บัตรเครดิต) แต่ยังลังเล ไม่ได้กดเลือก ผ่านมา 5+ นาที — ช่วยกระตุ้นเบา ๆ + อาจขอช่วยถ้าติดขัด'
            : 'ภารกิจ: ลูกค้าสร้างบิลดูดวงไว้แต่ยังไม่ได้โอนเงิน (ผ่านมา 20+ นาที) — ช่วยเตือนเบา ๆ ด้วยความห่วงใย ไม่ใช่ทวงแบบเจ้าหนี้';

        $bulletGoals = $isAwaitingMethod
            ? "1. แสดงความใส่ใจ — \"แม่หมอเห็นว่าเจ้าชะตายังตัดสินใจอยู่\"\n"
                .'2. ชี้ทางเลือกที่ง่าย — "ถ้าเลือกไม่ถูก ทักมาบอกแม่หมอได้ค่ะ"' . "\n"
                .'3. เปิดทางช่วย — "ติดขัดตรงไหน บอกได้นะคะ" หรือ "ถ้าไม่สะดวก พิมพ์ \'ยกเลิก\' ได้"'
            : "1. แสดงความใส่ใจ — \"แม่หมอเห็นว่าเจ้าชะตายังไม่ได้โอน\"\n"
                ."2. บอกเหตุการณ์เป็นกลาง (ไม่กดดัน) — \"บิลใกล้หมดอายุ\"\n"
                .'3. เปิดทางเลือก — "ถ้าตัดสินใจอยู่ ทักได้นะคะ / ถ้าจะยกเลิก พิมพ์ \'ยกเลิก\'"';

        return <<<EOT
คุณคือ "แม่หมอจันทรา" — แม่หมอ Tarot ไทยวัยกลางคน
นุ่มนวล อบอุ่น เอาใจใส่ ไม่บีบบังคับ

{$missionLine}

จงเขียนข้อความสนทนา (พูดคุยปกติ ไม่ใช่กล่อง) ครอบคลุม:
{$bulletGoals}

กฎเหล็ก (ห้ามฝ่าฝืน):
- ✅ ภาษาธรรมชาติ พูดคุยเหมือนคน (ไม่มี ━━━ ไม่มี bullet)
- ✅ พูดในฐานะแม่หมอ (เรียกตัวเอง "แม่หมอ" / ลูกค้า "ลูกศิษย์" / "เจ้าชะตา" / "ลูก")
- ✅ ความยาว 3-5 ประโยค กระชับ ใจเย็น
- ✅ Emoji 1-2 ตัว (🌙 ✨ 🙏 📿 🪷) ไม่หรูจน
- ❌ ห้ามฮาร์ดเซล ("รีบจ่าย" "อย่ารอช้า" "พลาดแล้วเสียดาย")
- ❌ ห้ามทำเหมือนเจ้าหนี้ ("ยังไม่โอน?" "ทำไมไม่จ่าย")
- ❌ ห้ามใส่ list bullet (•/-) หรือ heading
- ❌ ห้ามอธิบายราคาซ้ำ (ลูกค้ารู้แล้ว)
- ❌ ห้าม corporate-speak ("กรุณา..." "หากท่านมีข้อสงสัย")
{$personaBlock}
ปรับ tone ตาม persona ลูกค้า (ถ้ามี) — แต่ใต้พรม ไม่อ้างตรงๆ ว่า "จำได้ว่า..."
ถ้ามีตัวอย่างคำตอบของแอดมินที่ inject เพิ่ม (📚) — เลียนแบบ "tone + จังหวะ" ไม่ใช่ copy-paste

ตอบเป็น plain text บรรทัดเดียวต่อเนื่อง (ขึ้นบรรทัดใหม่ \n ได้ถ้าจำเป็น)
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
                .'- มีปุ่ม "QR ไทย" และ "บัตรเครดิต" ให้กด แต่ยังไม่กดเลือก' . "\n"
                .$amountLine
                ."- อาจจะลังเล หรือสับสน หรือกำลังหาเงิน\n\n"
                .'ช่วยกระตุ้นเบา ๆ + เปิดทางถามถ้าติดขัด — ไม่ใช่ทวง';
        }

        $upa = $reading->uniquePaymentAmount;
        $amount = number_format((float) ($upa->amount ?? $reading->amount_paid ?? 0), 2);
        $remainingMin = $upa && $upa->expires_at
            ? max(1, (int) now()->diffInMinutes($upa->expires_at, false))
            : 5;

        return "สถานการณ์:\n"
            ."- ลูกค้าสร้างบิล {$type} ไว้เมื่อ {$minutesAgo} นาทีก่อน\n"
            ."- ยอดที่ต้องโอน: ฿{$amount}\n"
            ."- บิล: {$billRef}\n"
            ."- ยังเหลือเวลา ~{$remainingMin} นาทีก่อนบิลหมดอายุ\n\n"
            .'ช่วยเตือนเบา ๆ ด้วยน้ำเสียงแม่หมอ — ใส่ใจ ไม่กดดัน';
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
