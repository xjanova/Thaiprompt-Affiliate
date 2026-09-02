<?php

namespace App\Services\Fortune;

use App\Models\FortuneContactSignal;
use Illuminate\Support\Facades\Log;

/**
 * FortuneContactSignalService
 *
 * บันทึก "พฤติกรรมการส่งข้อความ" ของแต่ละ contact แบบเบาๆ (counter)
 * เพื่อตรวจจับคนที่ "ส่งแต่ลิงก์/รูป ไม่เคยพิมพ์คุยจริง" → candidate สำหรับแบน
 *
 * 🛡️ หลักการกัน false-positive (สำคัญมาก — ห้ามแบนลูกค้าจริง):
 *  - ถ้าเคย "พิมพ์คุยจริง" (มีคำ ไม่ใช่ลิงก์ล้วน) → whitelist ทันที
 *  - ถ้าเคยกดปุ่ม/quick-reply → engagement → whitelist
 *  - ถ้าเคยมี reading หรือจ่ายเงิน → whitelist
 *  - sticker / emoji / location → นับเป็น "กลางๆ" ไม่ใช่สแปม (คนแก่ส่ง sticker ปลอดภัย)
 *
 * เชื่อมโยง: [[rule_paid_customer_bypass_all_guards]] — ลูกค้าจ่ายเงิน ห้ามโดน guard ใดบล็อก
 */
class FortuneContactSignalService
{
    /**
     * ความยาวข้อความ (ไม่นับ URL) ที่ถือว่า "คุยจริง" แม้มีลิงก์ประกบ
     * — กันลูกค้าจริงที่พิมพ์ยาวๆ พร้อมแปะลิงก์ (เช่น ถามรายละเอียดแพคเกจ)
     */
    protected const LONG_TEXT_CHARS = 40;

    /**
     * 🚫 (2026-09-02, เจ้าของสั่ง) ส่งลิงก์/รูป "ติดต่อกัน" ครบกี่ครั้งถึงแบน
     *
     * ครบเกณฑ์ครั้งแรก → แบน 7 วัน (หมดอายุเอง) · ทำซ้ำอีกรอบ → ถาวร
     */
    public const BURST_BAN_THRESHOLD = 5;

    /**
     * แบน 7 วัน = กี่นาที (ใช้กับ FortuneBanService::ban)
     */
    public const BURST_BAN_MINUTES = 7 * 24 * 60;

    /**
     * streak เก่ากว่ากี่วันถือว่า "คนละรอบ" แล้วเริ่มนับหนึ่งใหม่
     *
     * เหตุผล: "ติดต่อกัน" ต้องมีขอบเขตเวลา ไม่งั้นคนที่ส่งลิงก์เดือนละใบ
     * ห้าเดือนติดจะถูกนับเป็นสแปมรัว ทั้งที่เว้นห่างกันคนละเดือน
     */
    protected const BURST_WINDOW_DAYS = 7;

    /**
     * บันทึกสัญญาณจากข้อความขาเข้า 1 ข้อความ
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $platformUserId  PSID / LINE userId
     * @param  string  $text  ข้อความ (อาจว่าง)
     * @param  array  $attachments  Facebook attachments array
     * @param  bool  $buttonPressed  กดปุ่ม/quick-reply/postback หรือไม่ (= engagement)
     * @param  bool  $hasReadingOrPaid  เคย "จ่ายเงิน/แจ้งโอน/ส่งสลิป" หรือไม่ (= ลูกค้าจ่ายจริง)
     *                                  ⚠️ (2026-08-08) ไม่ใช่ "มีแถว reading" เฉยๆ อีกแล้ว —
     *                                  แค่กดเปิดเมนูดูดวงแล้วหายไป ไม่ถือเป็นลูกค้าจริง
     * @param  string|null  $displayName  ชื่อ (snapshot)
     * @return FortuneContactSignal|null แถวที่บันทึกแล้ว (null = บันทึกไม่สำเร็จ)
     *                                   ผู้เรียกใช้ตรวจ streak ต่อได้ทันทีโดยไม่ต้องคิวรีซ้ำ
     */
    public function record(
        string $platform,
        string $platformUserId,
        string $text,
        array $attachments = [],
        bool $buttonPressed = false,
        bool $hasReadingOrPaid = false,
        ?string $displayName = null,
    ): ?FortuneContactSignal {
        try {
            $hasWords = $this->hasRealWords($text);

            // 🔗 (2026-08-08) ลิงก์ที่มาจากปุ่ม "แชร์ไป Messenger" มาเป็น attachment type=fallback
            //    โดย text ว่างเปล่า → ถ้าดูแต่ $text จะมองไม่เห็นลิงก์เลย (เคสจริง PSID 27713676774998286
            //    ยิงลิงก์แชร์ 13 ครั้งใน 11 นาที แต่ระบบนับเป็น "ไม่มีลิงก์")
            $attachmentLink = $this->firstExternalLinkFromAttachments($attachments);
            $hasExternalLink = $this->hasExternalLink($text) || $attachmentLink !== null;

            $hasMedia = $this->hasMediaAttachment($attachments);
            $wordLen = $this->wordLen($text); // ความยาวข้อความที่ไม่นับ URL

            // ✅ "คุยจริง" (→ whitelist): กดปุ่ม / จ่ายเงิน /
            //    มีคำจริงโดยไม่มีลิงก์ / หรือมีลิงก์แต่พิมพ์ยาว (ลูกค้าถามจริงพร้อมแปะลิงก์)
            $genuineConvo = $buttonPressed
                || $hasReadingOrPaid
                || ($hasWords && ! $hasExternalLink)
                || ($hasWords && $hasExternalLink && $wordLen > self::LONG_TEXT_CHARS);

            // 🚩 (1) ลิงก์ + คำประกบสั้นๆ (เช่น "ดูเลย🔥 + ลิงก์") = promo caption
            $isLinkCaption = ! $genuineConvo && $hasExternalLink && $hasWords && $wordLen <= self::LONG_TEXT_CHARS;

            // 🚩 ลิงก์/รูปล้วน ไม่มีคำเลย
            $isPureLinkImage = ! $hasWords && ($hasExternalLink || $hasMedia);

            $isSpamType = $isPureLinkImage || $isLinkCaption;

            $sample = null;
            if ($isPureLinkImage) {
                $sample = $this->hasExternalLink($text)
                    ? mb_substr(trim($text), 0, 300)
                    : ($attachmentLink ?? '[media:'.$this->mediaTypes($attachments).']');
            } elseif ($isLinkCaption) {
                $sample = mb_substr(trim($text), 0, 300);
                if ($attachmentLink !== null && ! $this->hasExternalLink($text)) {
                    $sample = mb_substr($sample.' '.$attachmentLink, 0, 300);
                }
            }

            $signal = FortuneContactSignal::firstOrNew([
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ]);

            if (! $signal->exists) {
                $signal->first_seen_at = now();
                $signal->status = 'tracking';
            }

            // สถานะ whitelist "ก่อน" ข้อความนี้ — ใช้ตัดสินว่าลิงก์นี้ถูกส่งมาหลังได้เกราะแล้วหรือยัง
            $wasWhitelisted = (bool) $signal->whitelisted;

            $signal->display_name = $displayName ?: $signal->display_name;
            $signal->inbound_total = (int) $signal->inbound_total + 1;
            $signal->last_seen_at = now();

            if ($genuineConvo) {
                // คุยจริง → whitelist ถาวร (กัน false-positive)
                $signal->whitelisted = true;

                if ($hasWords) {
                    $signal->real_text_count = (int) $signal->real_text_count + 1;
                }
                $signal->interaction_count = (int) $signal->interaction_count + 1;

                // ถ้าเคยถูก flag ไว้ → คืนสถานะ (เคลียร์ความเข้าใจผิด)
                if ($signal->status === 'flagged') {
                    $signal->status = 'cleared';
                }
            } elseif ($isPureLinkImage) {
                // ลิงก์/รูปล้วน
                $signal->link_image_count = (int) $signal->link_image_count + 1;
                $signal->last_sample = $sample;
            } elseif ($isLinkCaption) {
                // ลิงก์ + คำประกบสั้น (promo)
                $signal->link_caption_count = (int) $signal->link_caption_count + 1;
                $signal->last_sample = $sample;
            }
            // sticker / emoji / empty / location → นับแค่ inbound_total เฉยๆ

            // 📅 (2) ความถี่: นับ "จำนวนวันที่ส่งสแปม" (distinct calendar days)
            //    gate ด้วย !genuineConvo — ลูกค้าจริง/จ่ายเงินที่บังเอิญแปะลิงก์ ไม่นับเป็นวันสแปม
            if ($isSpamType && ! $genuineConvo) {
                $today = now()->toDateString();
                $lastDay = $signal->last_spam_day
                    ? (\is_string($signal->last_spam_day) ? $signal->last_spam_day : $signal->last_spam_day->toDateString())
                    : null;
                if ($lastDay !== $today) {
                    $signal->active_days = (int) $signal->active_days + 1;
                    $signal->last_spam_day = $today;
                }
            }

            // 🔁 (2026-08-08) รางที่ 2: นับ "ลิงก์" ที่ยังยิงมาทั้งที่ถูก whitelist ไปแล้ว
            //    ปัญหาเดิม: whitelist = เกราะตลอดชีพ — พิมพ์คุยจริงครั้งเดียว/กดปุ่มครั้งเดียว
            //    แล้วหันมายิงลิงก์รัวๆ ตลอดกาล ระบบไม่นับอะไรเลย (เคสจริง อุดม ศรีโปฎก 2026-08-08)
            //
            //    🛡️ กัน false-positive 2 ชั้น:
            //      (a) นับเฉพาะ "ลิงก์" — ไม่นับรูป/วิดีโอ → คนส่งสลิปซ้ำๆ ไม่มีวันโดนนับ
            //      (b) ข้ามลูกค้าที่จ่ายเงิน/แจ้งโอนแล้ว ($hasReadingOrPaid) ทั้งหมด
            //      (c) ลิงก์ที่มาพร้อมข้อความยาว (ถามจริงพร้อมแปะลิงก์) ไม่นับ
            $isLinkSpamShape = $hasExternalLink
                && (! $hasWords || $wordLen <= self::LONG_TEXT_CHARS);

            if ($isLinkSpamShape
                && ! $hasReadingOrPaid
                && ($wasWhitelisted || $genuineConvo)) {
                $signal->wl_link_count = (int) $signal->wl_link_count + 1;
                $signal->last_sample = $sample ?: ($attachmentLink ?? $signal->last_sample);

                $today = now()->toDateString();
                $lastWlDay = $signal->wl_last_link_day
                    ? (\is_string($signal->wl_last_link_day) ? $signal->wl_last_link_day : $signal->wl_last_link_day->toDateString())
                    : null;
                if ($lastWlDay !== $today) {
                    $signal->wl_link_days = (int) $signal->wl_link_days + 1;
                    $signal->wl_last_link_day = $today;
                }
            }

            // 🚫 (2026-09-02, เจ้าของสั่ง) นับ "ลิงก์/รูปติดต่อกัน" → ครบ 5 แบน 7 วัน · ซ้ำ = ถาวร
            //
            //   🛡️ กัน false-ban 3 ชั้น:
            //     (a) คุยจริง/กดปุ่ม/จ่ายเงิน → รีเซ็ตเป็น 0 ทันที (พิมพ์อะไรก็หลุดแล้ว)
            //     (b) ข้ามคนที่จ่ายเงิน/แจ้งโอน/ส่งสลิป ($hasReadingOrPaid) — ไม่นับให้เลยสักครั้ง
            //     (c) เว้นห่างเกิน BURST_WINDOW_DAYS = เริ่มนับหนึ่งใหม่ ไม่สะสมข้ามเดือน
            //   sticker / เสียง / อีโมจิ / ข้อความว่าง ไม่เข้าเงื่อนไขทั้งเพิ่มและรีเซ็ต — เงียบไว้เฉยๆ
            if ($genuineConvo) {
                $signal->burst_streak = 0;
            } elseif ($isSpamType && ! $hasReadingOrPaid) {
                $lastBurst = $signal->burst_last_at;
                $isStaleStreak = $lastBurst === null
                    || $lastBurst->lt(now()->subDays(self::BURST_WINDOW_DAYS));

                $signal->burst_streak = $isStaleStreak
                    ? 1
                    : ((int) $signal->burst_streak + 1);
                $signal->burst_last_at = now();
            }

            $signal->save();

            return $signal;
        } catch (\Throwable $e) {
            // 🛡️ ห้าม block flow หลัก — log debug เฉยๆ
            Log::debug('FortuneContactSignal: record fail (non-blocking)', [
                'platform' => $platform,
                'user_id' => $platformUserId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ถึงเกณฑ์แบน "ยิงลิงก์/รูปติดต่อกัน" แล้วหรือยัง
     *
     * @return string|null 'temporary' = แบน 7 วัน · 'permanent' = ถาวร · null = ยังไม่ถึงเกณฑ์
     */
    public function burstBanVerdict(?FortuneContactSignal $signal): ?string
    {
        if ($signal === null) {
            return null;
        }

        if ((int) $signal->burst_streak < self::BURST_BAN_THRESHOLD) {
            return null;
        }

        // เคยโดนแบนด้วยกฎนี้มาแล้ว → รอบนี้ถาวร (เจ้าของสั่ง "ถ้าเป็นอีกก็ถาวร")
        return ((int) $signal->burst_ban_count) >= 1 ? 'permanent' : 'temporary';
    }

    /**
     * ปิดบัญชี streak หลังลงมือแบนแล้ว
     *
     * รีเซ็ต streak เป็น 0 เพื่อให้รอบหน้าเริ่มนับใหม่ตั้งแต่หนึ่ง
     * และบวก burst_ban_count ไว้เป็นความจำว่า "เคยโดนมาแล้ว" → รอบต่อไปถาวร
     */
    public function markBurstBanned(FortuneContactSignal $signal, ?string $displayName = null): void
    {
        try {
            $signal->burst_ban_count = ((int) $signal->burst_ban_count) + 1;
            $signal->burst_streak = 0;
            $signal->status = 'banned';
            $signal->banned_at = now();

            if (! empty($displayName)) {
                $signal->display_name = $displayName;
            }

            $signal->save();
        } catch (\Throwable $e) {
            Log::warning('FortuneContactSignal: markBurstBanned fail (non-blocking)', [
                'signal_id' => $signal->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ความยาวข้อความที่ "ไม่นับ URL" (ตัดลิงก์ออกแล้ว trim)
     *
     * ใช้แยก "ลิงก์ + คำประกบสั้น" (promo) ออกจาก "ลูกค้าถามจริงพร้อมลิงก์" (ยาว)
     */
    protected function wordLen(string $text): int
    {
        $stripped = preg_replace('#https?://\S+#i', '', $text);

        return mb_strlen(trim((string) $stripped));
    }

    /**
     * มี "คำจริง" ไหม (ตัด URL ออกแล้วยังเหลือตัวอักษร ≥ 2)
     *
     * เหตุผล: ข้อความที่มีคำจริง = ลูกค้าพยายามสื่อสาร → ไม่ใช่สแปมลิงก์ล้วน
     */
    protected function hasRealWords(string $text): bool
    {
        return $this->wordLen($text) >= 2;
    }

    /**
     * มีลิงก์ภายนอก (ไม่ใช่ domain ของเรา) ไหม
     *
     * — mirror logic จาก FacebookWebhookController::isUserSpamming Rule 3
     */
    protected function hasExternalLink(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        return (bool) preg_match(
            '#https?://(?!(www\.)?(main\.)?thaiprompt\.online)|t\.me/|bit\.ly/|tinyurl\.com#i',
            $text
        );
    }

    /**
     * ดึง URL ภายนอกตัวแรกจาก attachment ชนิด "ลิงก์แชร์" (type=fallback)
     *
     * 🔗 (2026-08-08) ปุ่ม "แชร์ไป Messenger" ของ Facebook ส่งมาเป็น
     *   attachments:[{type:"fallback", payload:{url:"https://...", title:"..."}}]
     *   โดย message.text ว่างเปล่า → ถ้าอ่านแต่ $text จะไม่เห็นลิงก์เลย
     *
     * ⚠️ อ่านเฉพาะ type=fallback เท่านั้น — ห้ามอ่าน payload.url ของ image/video/file
     *   เพราะนั่นคือ URL ของ CDN Facebook (รูปที่ลูกค้าอัพเอง เช่น "สลิป")
     *   ถ้านับเป็นลิงก์ = ลูกค้าส่งสลิปพร้อมแคปชั่นสั้น จะกลายเป็น "สแปมลิงก์" ทันที
     *
     * @return string|null URL ที่เจอ (null = ไม่มี)
     */
    protected function firstExternalLinkFromAttachments(array $attachments): ?string
    {
        foreach ($attachments as $att) {
            if (! is_array($att) || ($att['type'] ?? '') !== 'fallback') {
                continue;
            }

            $url = $att['payload']['url'] ?? null;
            if (is_string($url) && $url !== '' && $this->hasExternalLink($url)) {
                return mb_substr($url, 0, 300);
            }
        }

        return null;
    }

    /**
     * มี attachment ประเภทสื่อ (รูป/วิดีโอ/ไฟล์/ลิงก์แชร์) ไหม — ไม่นับ sticker
     */
    protected function hasMediaAttachment(array $attachments): bool
    {
        foreach ($attachments as $att) {
            if (is_array($att) && $this->isMedia($att)) {
                return true;
            }
        }

        return false;
    }

    /**
     * attachment นี้เป็นสื่อจริงไหม (ไม่ใช่ sticker / location / เสียง)
     *
     * 🛡️ (2026-06-04) ตัด 'audio' ออกจากการนับสแปม:
     *   ข้อความเสียงแปะลิงก์/รูปโปรโมทไม่ได้ → คนส่ง voice note เกือบทั้งหมด = ลูกค้าจริง
     *   ที่พยายามถามด้วยเสียง (บอทไม่รองรับเสียงเลยเงียบ → ลูกค้าส่งซ้ำ) ไม่ใช่สแปม
     *   ถ้านับ audio = link_image จะ false-ban ลูกค้าจริง (เคสจริง 2026-06-04: 2/6 suspects เป็นคนส่งเสียง)
     */
    protected function isMedia(array $att): bool
    {
        $type = $att['type'] ?? '';

        // sticker มาเป็น type=image + payload.sticker_id → ไม่นับเป็นสแปม
        if ($type === 'image' && ! empty($att['payload']['sticker_id'])) {
            return false;
        }

        // ❌ ไม่รวม 'audio' — เสียงไม่ใช่สแปมลิงก์/รูป (กัน false-ban ลูกค้าที่ถามด้วยเสียง)
        return in_array($type, ['image', 'video', 'file', 'fallback'], true);
    }

    /**
     * รายชื่อประเภทสื่อ (สำหรับเก็บเป็น sample)
     */
    protected function mediaTypes(array $attachments): string
    {
        $types = [];
        foreach ($attachments as $att) {
            if (is_array($att) && $this->isMedia($att)) {
                $types[] = $att['type'] ?? '?';
            }
        }

        return implode(',', array_unique($types)) ?: 'unknown';
    }
}
