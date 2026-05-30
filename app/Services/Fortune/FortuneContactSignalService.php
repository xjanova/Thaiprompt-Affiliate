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

            // engagement → whitelist (ลูกค้าจริง ห้ามแบนอัตโนมัติ)
            $engaged = $hasWords || $buttonPressed || $hasReadingOrPaid;

            // เป็น "ลิงก์/รูปล้วน" เมื่อ: ไม่มีคำจริง + (มีลิงก์ภายนอก หรือ มีรูป/วิดีโอ)
            $isLinkImage = ! $hasWords && ($hasExternalLink || $hasMedia);

            $sample = null;
            if ($isLinkImage) {
                $sample = $hasExternalLink
                    ? mb_substr(trim($text), 0, 300)
                    : '[media:'.$this->mediaTypes($attachments).']';
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

            if ($engaged) {
                // เคยคุยจริง/จ่ายเงิน/กดปุ่ม → whitelist ถาวร (กัน false-positive)
                $signal->whitelisted = true;

                if ($hasWords) {
                    $signal->real_text_count = (int) $signal->real_text_count + 1;
                }
                if ($buttonPressed || $hasWords) {
                    $signal->interaction_count = (int) $signal->interaction_count + 1;
                }

                // ถ้าเคยถูก flag ไว้ → คืนสถานะ (เคลียร์ความเข้าใจผิด)
                if ($signal->status === 'flagged') {
                    $signal->status = 'cleared';
                }
            } elseif ($isLinkImage) {
                // ลิงก์/รูปล้วน — สะสมเข้า counter
                $signal->link_image_count = (int) $signal->link_image_count + 1;
                $signal->last_sample = $sample;
            }
            // sticker / emoji / empty / location → นับแค่ inbound_total เฉยๆ

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
     * มี "คำจริง" ไหม (ตัด URL ออกแล้วยังเหลือตัวอักษร ≥ 2)
     *
     * เหตุผล: ข้อความที่มีคำจริง = ลูกค้าพยายามสื่อสาร → ไม่ใช่สแปมลิงก์ล้วน
     */
    protected function hasRealWords(string $text): bool
    {
        $stripped = preg_replace('#https?://\S+#i', '', $text);
        $stripped = trim((string) $stripped);

        return mb_strlen($stripped) >= 2;
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
