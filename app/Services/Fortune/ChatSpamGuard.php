<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Log;

/**
 * 🚫 ด่านสแปมข้อความแชทดูดวง — ใช้ร่วมกันทุกช่องทางที่ไม่ใช่ Facebook (2026-09-13)
 *
 * ย้ายมาจาก `LineFortuneWebhookController::isUserSpamming()` **แบบคำต่อคำ**
 * ต่างกันแค่ชื่อช่องทางใน cache key / log (LINE ยังได้ key `fortune:spam:*:line:*` เดิมเป๊ะ)
 *
 * ทำไมต้องย้าย: Telegram ต้องมีด่านเดียวกัน — [[rule_spam_guard_parity_fb_line]]
 *   "ด่านที่ทำฝั่งเดียวคือด่านที่คนกวนย้ายช่องหนี" · ก็อปไปอีกชุด = สองชุดแก้ไม่พร้อมกันแน่นอน
 *
 * FB มีด่านของตัวเองใน FacebookWebhookController (โครงต่างกัน — ไม่ได้ย้ายมา)
 *
 * @see \App\Http\Controllers\LineFortuneWebhookController::isUserSpamming()
 * @see \App\Http\Controllers\TelegramFortuneWebhookController
 */
class ChatSpamGuard
{
    /**
     * ลูกค้าคนนี้ควรถูกปิดปาก (ข้ามข้อความนี้) หรือไม่
     *
     * @param  string  $platform  'line' | 'telegram' — ใช้เป็นส่วนหนึ่งของ cache key
     * @param  string|null  $messageType  'text' | 'image' | 'sticker' | 'video' | 'audio' | 'file' …
     * @return bool true = ข้ามข้อความนี้เงียบ ๆ
     */
    public function isSpamming(string $platform, string $userId, string $text, ?string $messageType): bool
    {
        $label = strtoupper($platform);
        $silencedKey = "fortune:spam:silenced:{$platform}:{$userId}";
        $strikeKey = "fortune:spam:strikes:{$platform}:{$userId}";
        $lastTextKey = "fortune:spam:last_text:{$platform}:{$userId}";
        $maxStrikes = 5;

        // 🚨 (2026-08-18) ลิงก์สแปม = สแปมเสมอ — คำนวณก่อนทุก bypass
        //   ต่อให้อยู่กลาง flow หรือพิมพ์คำสั่งปกติ ลิงก์ภายนอกก็ไม่ใช่พฤติกรรมลูกค้า
        //   ⚠️ ของเดิม `#https?://|www\.|\.com/|\.net/|\.online/#` ดัก main.thaiprompt.online ของตัวเอง
        //      → ลูกค้าก็อปลิงก์จ่ายเงิน/ลิงก์วอลเลตที่บอทส่งให้ กลับมาถาม = โดน strike ฟรี
        //
        //   วิธีแก้: "ลบโดเมนเราออกจากข้อความก่อน" แล้วค่อยตรวจด้วย pattern กว้างเหมือนเดิม
        //   — ไม่ใช้ negative lookahead แบบ FB เพราะ FB ดักได้แค่ลิงก์ที่มี https?:// นำหน้า
        //     ส่วนของ LINE ดักโดเมนเปล่า (`xxx.com/`) ได้ด้วย ถ้าเปลี่ยนไปตาม FB = ตรวจจับแย่ลง
        $textForUrlCheck = preg_replace(
            '#(?:https?://)?(?:www\.)?(?:main\.)?thaiprompt\.online\S*#i',
            '',
            $text
        );
        $hasSpamUrl = trim((string) $textForUrlCheck) !== ''
            && preg_match('#https?://|www\.|t\.me/|bit\.ly/|\.com/|\.net/|\.online/#i', $textForUrlCheck) === 1;

        // 🛡️ (2026-05-21) CRITICAL FIX — Bypass spam guard ถ้า user อยู่ใน active prediction flow
        //   เคสจริง: ลูกค้า LINE Celtic 99฿ พิมพ์ "พร้อม" 5 ครั้ง (เปิดไพ่ 5 ใบ)
        //            → strike #3 (text เหมือนเดิม) ติด 5 ครั้ง → silenced 1 ชั่วโมง
        //            → บอทเงียบ ลูกค้าเสียเงินใช้ไม่ได้
        //   บอทเอง instruct ให้พิมพ์ "พร้อม" 10 ครั้งติดกัน — ไม่ใช่ spam!
        //
        //   Statuses ที่ bypass:
        //   - CELTIC_PICKING — เปิดไพ่ (พิมพ์ "พร้อม" ซ้ำ 10 ครั้ง)
        //   - CELTIC_AWAITING_QUESTION/GENERATING/QA_PROMPT — Q&A flow
        //   - PAID — รอ AI gen (อย่ารังควาน)
        //   - COLLECTING_BIRTHDATE/QUESTIONS/TAROT — pre-payment flow
        //
        // 🛡️ (2026-08-18) เพิ่ม status ต้นทางของ flow — เคสจริงลูกค้า U46a1f097 เสียไป
        //   ลูกค้าใหม่เพิ่งกดติดตาม → พิมพ์ "ดูดวง" → ได้เมนูแพคเกจยาว 586 ตัวอักษร
        //   → พิมพ์ "ดูดวง" ซ้ำอีก 4 ครั้งใน 28 วินาที (นึกว่าบอทไม่ตอบ)
        //   → strike ครบ 5 → silenced 1 ชม. → พิมพ์ "99" (จะซื้อ!) บอทเงียบสนิท
        //   ตอนนั้น reading อยู่ status = tier_choice ซึ่ง "ไม่อยู่" ใน bypass list
        //   → ลูกค้ากำลังเลือกแพคเกจ = อยู่ใน flow เต็มตัว ไม่ใช่คนป่วน
        //   - TIER_CHOICE — กำลังเลือกแพคเกจ (39/99/199)
        //   - CELTIC_PENDING_PAYMENT / AWAITING_PAYMENT_METHOD — รอจ่าย
        //   - DISCOVERY_CHAT / DISCOVERY_CONFIRM — คุยเก็บข้อมูลก่อนทำนาย
        //   - AWAITING_CONFIRMATION — รอยืนยันข้อมูล
        $bypassStatuses = [
            \App\Models\FortuneReading::STATUS_CELTIC_PICKING,
            \App\Models\FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            \App\Models\FortuneReading::STATUS_CELTIC_GENERATING,
            \App\Models\FortuneReading::STATUS_CELTIC_QA_PROMPT,
            \App\Models\FortuneReading::STATUS_PAID,
            \App\Models\FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            \App\Models\FortuneReading::STATUS_COLLECTING_QUESTIONS,
            \App\Models\FortuneReading::STATUS_COLLECTING_TAROT,
            \App\Models\FortuneReading::STATUS_PENDING_PAYMENT,
            \App\Models\FortuneReading::STATUS_TIER_CHOICE,
            \App\Models\FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            \App\Models\FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
            \App\Models\FortuneReading::STATUS_DISCOVERY_CHAT,
            \App\Models\FortuneReading::STATUS_DISCOVERY_CONFIRM,
            \App\Models\FortuneReading::STATUS_AWAITING_CONFIRMATION,
        ];
        try {
            $hasActiveFlow = \App\Models\FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', $bypassStatuses)
                ->where('updated_at', '>=', now()->subHours(2))
                ->exists();

            // ⚠️ (2026-08-18) bypass ไม่ครอบลิงก์สแปม — คนป่วนเปิดบิลค้างไว้แล้วยิงลิงก์ได้ 2 ชม.
            if ($hasActiveFlow && ! $hasSpamUrl) {
                // ลูกค้าอยู่ใน flow ปกติ → ไม่ใช่ spam
                // เคลียร์ silence + strikes ที่อาจติดมาจาก guard ผิดพลาด
                if (\Illuminate\Support\Facades\Cache::has($silencedKey)) {
                    \Illuminate\Support\Facades\Cache::forget($silencedKey);
                    \Illuminate\Support\Facades\Cache::forget($strikeKey);
                    Log::info($label.' Fortune spam guard: bypassed + cleared silence (active flow)', [
                        'user_id' => $userId,
                        'text_preview' => mb_substr($text, 0, 50),
                    ]);
                }

                return false;
            }
        } catch (\Throwable $e) {
            // เช็คล้มเหลว → fall through ไป spam check ปกติ
            Log::debug($label.' spam guard: active flow check fail (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }

        if (\Illuminate\Support\Facades\Cache::has($silencedKey)) {
            return true;
        }

        $strikes = (int) \Illuminate\Support\Facades\Cache::get($strikeKey, 0);
        $newStrikes = 0;

        // 🚨 Strike 1: non-text/non-image + ไม่มี active bill
        // ⚖️ (2026-09-01) parity FB rewrite 2026-05-02: **สติกเกอร์ไม่นับ strike** — เป็นภาษาแชท
        //   ปกติของคนไทย (FB ตัด sticker-strike ทิ้งแล้ว LINE ตกค้าง) เหลือดักเฉพาะ video/audio/file
        //   + แก้คอลัมน์: เดิมเช็คแค่ facebook_user_id — แถวที่มีแต่ platform_user_id มองไม่เห็น
        if ($messageType && ! in_array($messageType, ['text', 'image', 'sticker'], true)) {
            $hasActiveBill = \App\Models\FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', [
                    \App\Models\FortuneReading::STATUS_PENDING_PAYMENT,
                    \App\Models\FortuneReading::STATUS_PAID,
                ])
                ->exists();

            if (! $hasActiveBill) {
                $newStrikes++;
            }
        }

        // 🚨 Strike 2: ข้อความมี URL/ลิงก์ (คำนวณไว้ข้างบน — ละเว้นโดเมนเราแล้ว)
        if ($hasSpamUrl) {
            $newStrikes++;
        }

        // 🚨 Strike 3: ข้อความเหมือนเดิม
        //
        // 🛡️ (2026-08-18) ยกเว้น "คำสั่งปกติ" — parity กับ FB (FacebookWebhookController::isUserSpamming
        //     $stateExpectedInputs มีมาตั้งแต่ 2026-05-06 commit 0ec4aa0f7 แต่ LINE ไม่เคยได้ตาม)
        //
        //   เคสจริง 2026-08-18 ลูกค้า U46a1f097 (เพิ่งกดติดตาม 10 วินาทีก่อน):
        //     14:44:02 "ดูดวง" → เมนูแพคเกจ 586 ตัวอักษร (has_quick_replies=false ไม่มีปุ่มให้กด)
        //     14:44:11/19/21/27 "ดูดวง" ซ้ำ → strike 1,2,3,4
        //     14:44:30 "ดูดวง" → strike 5 → silenced 1 ชม.
        //     14:46:35 "99" (จะซื้อแล้ว!) → บอทเงียบสนิท = เสียลูกค้าจ่ายเงินทั้งคน
        //
        //   "ดูดวง" คือคีย์เวิร์ดเปิดบทสนทนาหลักของบอทเอง — พิมพ์ซ้ำ = คนนึกว่าบอทไม่ตอบ ไม่ใช่คนป่วน
        //   ([[rule_nav_noise_never_counts_as_input]] — ตัวหนังสือบนปุ่มไหลกลับมาเป็น text ก็เข้าทางนี้)
        //
        //   ⚠️ ตัวเลขราคา 39/99 ต้องรอดด้วย ([[rule_typed_price_fasttrack_bill]] — พิมพ์เลขเอง = จะซื้อ)
        //      ครอบด้วยกฎความยาว ≤ 4 ตัวอักษร เหมือน FB
        //
        // 🌙 (2026-08-21) เลนดวงฟรีรายวันเปิดฝั่ง LINE แล้ว — คำของเลนนี้ต้องรอดด้วย
        //   กฎ fallback คือ `mb_strlen <= 4` ซึ่ง **ไม่ครอบชื่อวันไทยเลยสักวัน**
        //   ("จันทร์" 6 ตัว · "อาทิตย์" 7 ตัว · สั้นสุด "พุธ" ก็ยัง 3 ตัวรอดแค่วันเดียว)
        //   ⇒ ลูกค้าที่พิมพ์ชื่อวันเกิดซ้ำ 5 ครั้ง (เพราะบอทตอบช้า/ไม่ตอบ) โดนปิดปาก 1 ชม.
        //   และด่าน bypass ตาม status ด้านบนช่วยไม่ได้ เพราะเลนดวงรายวันคืน
        //   `'reading' => null` — ไม่มีแถว reading ให้ whereIn() เจอสักสถานะ
        //
        //   ⚠️ ใส่ "ชื่อวันล้วน" เท่านั้น — ประโยคที่มีชื่อวันปนยังนับ strike ตามปกติ
        //      (in_array เทียบเป๊ะ ไม่ใช่ substring)
        $stateExpectedInputs = [
            'พร้อม', 'ใช่', 'ไม่ใช่', 'ใช่เลย', 'ไม่', 'ตกลง', 'ok', 'OK',
            'ดูดวง', 'เริ่มถามคำถาม', 'พอแค่นี้', 'พอ', 'หยุด',
            'อ่านคำทำนาย', 'รับคำทำนาย', 'ยกเลิก', 'ดูคำทำนายล่าสุด',
            'ดวงรายวัน', 'ดูดวงรายวัน', 'เริ่มใหม่',
            // 🌙 คำขอดวงฟรีรายวัน (รวมป้ายปุ่มที่ไหลกลับมาเป็นข้อความ)
            'ดวงฟรี', 'ดูดวงฟรี', 'ดวงฟรีประจำวัน', 'รับดวงฟรีประจำวัน',
            'ดวงประจำวัน', 'ขอดวงวันนี้', 'ดูดวงวันนี้เลย',
            // 🌙 ชื่อวันเกิด 7 วัน — ทั้งแบบมี "วัน" นำหน้าและไม่มี
            'อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'พฤหัส', 'ศุกร์', 'เสาร์',
            'วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันพฤหัส', 'วันศุกร์', 'วันเสาร์',
        ];
        $normalizedText = trim($text);
        // 🌙 (2026-09-01) ปอกคำลงท้ายสุภาพก่อนเทียบ whitelist — "จันทร์ค่ะ" (7 ตัว เกิน 4)
        //   ต้องรอดเหมือน "จันทร์" (parser ฝั่งเลนรายวันปอกคำลงท้ายรับได้อยู่แล้ว ด่านนี้ต้องอดทนเท่ากัน)
        $strippedText = trim((string) preg_replace(
            '/(ครับผม|นะครับ|นะคะ|ครับ|ค้าบ|คับ|ค่ะ|คะ|จ้า|จ้ะ|จ๊ะ|น้า|นะ|ฮะ|ฮับ)+$/u',
            '',
            $normalizedText
        ));
        $isStateInput = in_array($normalizedText, $stateExpectedInputs, true)
            || ($strippedText !== '' && in_array($strippedText, $stateExpectedInputs, true))
            || mb_strlen($normalizedText) <= 4; // สั้นมาก = state input / เลขราคา (39, 99)

        if (! empty($text) && ! $isStateInput) {
            $lastText = \Illuminate\Support\Facades\Cache::get($lastTextKey);
            $repeatKey = "fortune:spam:repeat_count:{$platform}:{$userId}";
            if ($lastText === $text) {
                // ⚖️ (2026-09-01) parity FB: ซ้ำครั้งที่ 2 ยังไม่นับ (คนทวนเพราะบอทช้า/ไม่ตอบ)
                //   strike ตั้งแต่การซ้ำครั้งที่ 2 ขึ้นไป (= ข้อความเดียวกันโผล่ครั้งที่ 3)
                $repeatCount = (int) \Illuminate\Support\Facades\Cache::get($repeatKey, 0) + 1;
                \Illuminate\Support\Facades\Cache::put($repeatKey, $repeatCount, now()->addMinutes(10));
                if ($repeatCount >= 2) {
                    $newStrikes++;
                }
            } else {
                \Illuminate\Support\Facades\Cache::forget($repeatKey);
            }
            \Illuminate\Support\Facades\Cache::put($lastTextKey, $text, now()->addMinutes(10));
        }

        // 🚨 (2026-08-18) Strike 4: RATE FLOOD — parity กับ FB Rule 1
        //   ต้องมีตัวนี้เพราะ whitelist ข้างบนเปิดช่องให้พิมพ์ "ดูดวง" รัวได้ไม่จำกัด
        //   flood จริงดูที่ "ความถี่" ไม่ใช่ "เนื้อความ" — > 10 ข้อความใน 30 วินาที = ตั้งใจป่วน
        $rateKey = "fortune:spam:rate:{$platform}:{$userId}";
        $now = time();
        $rateLog = array_values(array_filter(
            (array) \Illuminate\Support\Facades\Cache::get($rateKey, []),
            fn ($t) => ($now - (int) $t) < 30
        ));
        $rateLog[] = $now;
        \Illuminate\Support\Facades\Cache::put($rateKey, $rateLog, now()->addMinute());
        if (count($rateLog) > 10) {
            $newStrikes++;
        }

        if ($newStrikes === 0) {
            return false;
        }

        $totalStrikes = $strikes + $newStrikes;
        \Illuminate\Support\Facades\Cache::put($strikeKey, $totalStrikes, now()->addHour());

        if ($totalStrikes >= $maxStrikes) {
            // ⚖️ (2026-09-01) parity FB rewrite 2026-05-02: โทษปิดปาก **5 นาที** ไม่ใช่ 1 ชม.
            //   (เคสจริง 2026-08-18: ลูกค้าใหม่โดน 1 ชม. ตอนกำลังจะจ่าย 99฿ — FB โดนแค่ 5 นาที)
            //   + ล้าง strike ตอนลงโทษ — ไม่งั้นพ้นโทษแล้ว strike เก่ายังค้างทั้งชั่วโมง
            //   พลาดอีกครั้งเดียวโดนปิดปากซ้ำทันที
            \Illuminate\Support\Facades\Cache::put($silencedKey, true, now()->addMinutes(5));
            \Illuminate\Support\Facades\Cache::forget($strikeKey);
            Log::warning('🚫 '.$label.' Fortune spam guard: silenced user for 5 minutes', [
                'user_id' => $userId,
                'total_strikes' => $totalStrikes,
                'message_type' => $messageType,
                'last_text' => mb_substr($text, 0, 80),
            ]);

            return true;
        }

        Log::info($label.' Fortune spam guard: strike recorded', [
            'user_id' => $userId,
            'strikes' => "{$totalStrikes}/{$maxStrikes}",
            'message_type' => $messageType,
        ]);

        return false;
    }
}
