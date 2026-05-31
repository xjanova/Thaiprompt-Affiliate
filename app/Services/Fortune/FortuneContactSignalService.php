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
     * บันทึกสัญญาณจากข้อความขาเข้า 1 ข้อความ
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $platformUserId  PSID / LINE userId
     * @param  string  $text  ข้อความ (อาจว่าง)
     * @param  array  $attachments  Facebook attachments array
     * @param  bool  $buttonPressed  กดปุ่ม/quick-reply/postback หรือไม่ (= engagement)
     * @param  bool  $hasReadingOrPaid  เคยมี reading หรือจ่ายเงินหรือไม่ (= ลูกค้าจริง)
     * @param  string|null  $displayName  ชื่อ (snapshot)
     */
    public function record(
        string $platform,
        string $platformUserId,
        string $text,
        array $attachments = [],
        bool $buttonPressed = false,
        bool $hasReadingOrPaid = false,
        ?string $displayName = null,
    ): void {
        try {
            $hasWords = $this->hasRealWords($text);
            $hasExternalLink = $this->hasExternalLink($text);
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
                $sample = $hasExternalLink
                    ? mb_substr(trim($text), 0, 300)
                    : '[media:'.$this->mediaTypes($attachments).']';
            } elseif ($isLinkCaption) {
                $sample = mb_substr(trim($text), 0, 300);
            }

            $signal = FortuneContactSignal::firstOrNew([
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ]);

            if (! $signal->exists) {
                $signal->first_seen_at = now();
                $signal->status = 'tracking';
            }

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

            $signal->save();
        } catch (\Throwable $e) {
            // 🛡️ ห้าม block flow หลัก — log debug เฉยๆ
            Log::debug('FortuneContactSignal: record fail (non-blocking)', [
                'platform' => $platform,
                'user_id' => $platformUserId,
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
     * attachment นี้เป็นสื่อจริงไหม (ไม่ใช่ sticker / location)
     */
    protected function isMedia(array $att): bool
    {
        $type = $att['type'] ?? '';

        // sticker มาเป็น type=image + payload.sticker_id → ไม่นับเป็นสแปม
        if ($type === 'image' && ! empty($att['payload']['sticker_id'])) {
            return false;
        }

        return in_array($type, ['image', 'video', 'audio', 'file', 'fallback'], true);
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
