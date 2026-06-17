<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ FortuneReviewInviteService (2026-06-17)
 *
 * ชวนลูกค้ารีวิว/แนะนำเพจ Facebook "หลังดูดวงจบ" — เฉพาะลูกค้าที่จ่ายเงิน
 *
 * เจ้าของสั่ง (2026-06-17): "นำลูกค้าที่ดูดวงเสร็จทุกคน (ที่จ่ายเงิน) ไปหน้ารีวิวอัตโนมัติ
 *   ส่งหลังสรุปคำทำนาย VIP — เอาทุกคน ไม่ต้องจับพอใจ/ไม่พอใจ เพื่อลดลอจิก"
 *
 * Gating (ตัดสินที่ chokepoint จบ session — ไม่แตะ hot path ตอนกำลังทำนาย):
 *   - เปิดฟีเจอร์ (review_invite_enabled)
 *   - มีลิงก์รีวิว (review_facebook_url หรือ derive จาก facebook_page_id)
 *   - ลูกค้าจ่ายเงินแล้ว (is_paid) — Celtic 99 / Deep 39 ที่ถึงหน้าสรุปล้วน paid
 *   - ยังไม่เคยส่ง (conversation_state.review_invite_sent_at) — idempotent กันส่งซ้ำ
 *
 * จุดเรียกใช้ (แนบ payload เข้า response → ChannelManager ส่ง bubble ถัดจากข้อความปิด):
 *   - CelticCrossConversationTrait::endCelticSession()        (Celtic 99 — webhook + cron)
 *   - ProSessionTrait::finalizeDeepProSessionTimeout()        (Deep 39 — cron)
 */
class FortuneReviewInviteService
{
    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ฟีเจอร์เปิดอยู่ไหม (default ปิด — admin เปิดเอง)
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->settings->review_invite_enabled ?? false);
    }

    /**
     * ลิงก์รีวิวที่จะพาลูกค้าไป
     *
     * ลำดับ:
     *   1. review_facebook_url ที่ admin ตั้งเอง
     *   2. default ที่ "ถูกต้องเสมอ" — derive จากเพจจริง: facebook.com/{page_id}/reviews/
     *   3. null (ไม่มีเพจ → ไม่ส่ง)
     */
    public function getReviewUrl(): ?string
    {
        $url = trim((string) ($this->settings->review_facebook_url ?? ''));
        if ($url !== '') {
            return $url;
        }

        $pageId = trim((string) ($this->settings->facebook_page_id ?? ''));
        if ($pageId !== '') {
            return "https://www.facebook.com/{$pageId}/reviews/";
        }

        return null;
    }

    /**
     * ข้อความชวนรีวิว (admin custom ได้ — เว้นว่าง = ใช้ default)
     *
     * แม่หมอเป็นผู้หญิงเสมอ — ใช้ "ค่ะ/นะคะ" ห้าม "ครับ/ผม"
     */
    public function getInviteText(?string $name = null): string
    {
        $name = $name ?: 'เจ้าชะตา';

        $custom = trim((string) ($this->settings->review_invite_text ?? ''));
        if ($custom !== '') {
            return str_replace('{name}', $name, $custom);
        }

        return "🌟 ถ้าคำทำนายวันนี้ถูกใจ — แม่หมอขอแรง{$name}ช่วยรีวิว/ให้กำลังใจที่เพจหน่อยนะคะ 💜\n\n"
            ."รีวิวของ{$name} = พลังให้แม่หมอได้ช่วยเหลือเจ้าชะตาท่านอื่นต่อไป ✨\n"
            .'กดปุ่มด้านล่างเพื่อเขียนรีวิวได้เลยค่ะ 🙏';
    }

    /**
     * ข้อความบนปุ่ม (FB จำกัด 20 ตัวอักษร — เก็บสั้นไว้)
     */
    public function getButtonTitle(): string
    {
        return '⭐ เขียนรีวิว';
    }

    /**
     * เข้าเงื่อนไขส่งคำชวนรีวิวไหม
     */
    public function shouldInvite(FortuneReading $reading): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }
        if (empty($this->getReviewUrl())) {
            return false;
        }
        if (! (bool) ($reading->is_paid ?? false)) {
            return false;
        }
        // idempotent — ส่งครั้งเดียวต่อบิล
        if ($reading->getConversationState('review_invite_sent_at')) {
            return false;
        }

        return true;
    }

    /**
     * สร้าง payload คำชวนรีวิว (ให้ ChannelManager เอาไป render ปุ่ม FB/LINE)
     *
     * @return array{text:string,url:string,button_title:string}|null
     */
    public function buildInvite(FortuneReading $reading): ?array
    {
        $url = $this->getReviewUrl();
        if (empty($url)) {
            return null;
        }

        $name = method_exists($reading, 'resolveCustomerName')
            ? $reading->resolveCustomerName()
            : ($reading->facebook_user_name ?? 'เจ้าชะตา');

        return [
            'text' => $this->getInviteText($name),
            'url' => $url,
            'button_title' => $this->getButtonTitle(),
        ];
    }

    /**
     * mark ว่าส่งแล้ว (persist ทันที — กันส่งซ้ำข้าม cron run)
     */
    public function markInvited(FortuneReading $reading): void
    {
        try {
            $reading->setConversationState('review_invite_sent_at', now()->toIso8601String());
        } catch (\Throwable $e) {
            Log::warning('ReviewInvite: mark sent fail', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ตรวจเงื่อนไข + สร้าง payload + mark sent (ทำครบในตัว, non-blocking)
     *
     * เรียกที่จุดจบ session — ถ้าเข้าเงื่อนไขคืน payload (และ mark sent ทันที),
     * ไม่เข้าเงื่อนไข/error คืน null (ไม่กระทบคำทำนาย)
     *
     * @return array{text:string,url:string,button_title:string}|null
     */
    public function attachIfEligible(FortuneReading $reading): ?array
    {
        try {
            if (! $this->shouldInvite($reading)) {
                return null;
            }

            $invite = $this->buildInvite($reading);
            if (empty($invite)) {
                return null;
            }

            // mark ก่อนส่ง — กัน double-send (push fail = ยอมไม่ส่งซ้ำ ดีกว่าสแปม)
            $this->markInvited($reading);

            Log::info('⭐ ReviewInvite: attached', [
                'reading_id' => $reading->id ?? null,
                'reading_type' => $reading->reading_type ?? null,
                'platform' => $reading->platform ?? null,
            ]);

            return $invite;
        } catch (\Throwable $e) {
            Log::warning('ReviewInvite: attach fail (non-blocking)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
