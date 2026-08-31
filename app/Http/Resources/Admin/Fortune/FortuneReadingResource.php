<?php

namespace App\Http\Resources\Admin\Fortune;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ FortuneReading (Admin Mobile API)
 *
 * เปิดเผย ai_response (เพราะ admin ต้องดูคำตอบ)
 * แต่ไม่เปิดเผย user_profile + user_posts_context (PII facebook)
 */
class FortuneReadingResource extends JsonResource
{
    /** ความยาวของ ai_response ที่ส่งตอน ?preview=1 — มากพอให้ fallback bubble
     *  ใน Warroom อ่านรู้เรื่อง แต่เล็กพอที่จะดึงหลายสิบแถวต่อรอบได้ */
    public const PREVIEW_CHARS = 200;

    protected function previewText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return mb_strlen($text) > self::PREVIEW_CHARS
            ? mb_substr($text, 0, self::PREVIEW_CHARS).'…'
            : $text;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // User
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'facebook_user_name' => $this->facebook_user_name,
            'facebook_user_id' => $this->facebook_user_id,
            // 🪪 (2026-06-19) Canonical platform identity. `platform` is
            //   authoritative for FB-vs-LINE (defaults 'facebook'); for LINE the
            //   ONLY stable user id is platform_user_id (facebook_user_id is null).
            //   Warroom uses these for correct channel badges + collapsing a
            //   customer's many readings into ONE card (was guessing via email).
            'platform' => $this->platform,
            'platform_user_id' => $this->platform_user_id,

            // Question + answer
            'questions' => $this->questions,
            'categories' => $this->categories,
            'reading_type' => $this->reading_type,
            // ✂️ (2026-08-31) ?preview=1 — ส่งคำทำนายแค่หัวๆ สำหรับหน้าที่เอาไปทำ
            //   preview อย่างเดียว (Warroom /chat ตัดเหลือ 80 ตัวอยู่ดี) คำทำนาย Celtic
            //   เต็ม ๆ ยาว 2-4 พันตัวอักษร คูณ 30-100 แถว = payload หนักมากทุกรอบ poll
            //   ตัดแล้วดึงได้เยอะขึ้นโดยไม่เปลืองแบนด์วิดท์
            //   เป็น opt-in ล้วน ๆ — ไม่ส่ง preview มาก็ได้ข้อความเต็มเหมือนเดิม
            //   (แอปแอดมิน Flutter และ /{reading} จึงไม่กระทบ)
            'ai_response' => $request->boolean('preview')
                ? $this->previewText($this->ai_response)
                : $this->ai_response,
            'ai_response_truncated' => $request->boolean('preview')
                && $this->ai_response !== null
                && mb_strlen($this->ai_response) > self::PREVIEW_CHARS,
            'reading_image_url' => $this->reading_image_url,
            // 📸 (2026-05-24) Payment slip / customer-sent image. Warroom /chat
            //    surfaces this as an image bubble so the operator can see the
            //    slip before pressing "✓ อนุมัติ 39/99".
            'user_image_url' => $this->user_image_url,

            // Payment
            'is_paid' => (bool) $this->is_paid,
            'amount_paid' => (float) $this->amount_paid,
            'paid_at' => $this->paid_at?->toIso8601String(),

            // Response status
            'response_type' => $this->response_type,
            'responded_at' => $this->responded_at?->toIso8601String(),

            // 🔲 (2026-06-12) Funnel state for the warroom multi-view — the fine
            //    conversation stage (celtic_picking / celtic_generating /
            //    pending_payment …) plus the cancellation verdict. Cancellation
            //    lives in conversation_state JSON, NOT a status column — bills
            //    cancelled by cron and by the customer both end up
            //    STATUS_COMPLETED + is_paid=false, so isCancelled() is the only
            //    correct discriminator.
            'conversation_status' => $this->conversation_status,
            'cancellation' => $this->isCancelled() ? [
                'reason' => (string) ($this->getConversationState('cancellation_reason') ?? 'unknown'),
                'label' => $this->getCancellationReasonLabelOrNull(),
                'cancelled_at' => $this->getConversationState('cancelled_at'),
            ] : null,
            // 🔮 (2026-06-19) Fine Celtic-99 sub-state for the warroom funnel so
            //    the operator sees exactly what each customer is doing — these
            //    live in conversation_state, NOT conversation_status. Cheap:
            //    getCelticPickedCount reads the already-loaded JSON (no query).
            //    celtic_birthdate_pending = picked all 10, now collecting วันเกิด.
            'celtic_picked_count' => $this->getCelticPickedCount(),
            'celtic_birthdate_pending' => (bool) $this->getConversationState('celtic_birthdate_pending'),
            'celtic_questions_used' => (int) ($this->celtic_questions_used ?? 0),

            // AI provenance
            'ai' => [
                'provider' => $this->ai_provider,
                'model' => $this->ai_model,
                'tokens_used' => $this->tokens_used,
            ],

            // Engagement
            'view_count' => (int) $this->view_count,
            'rating' => $this->rating,
            'feedback' => $this->feedback,

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
