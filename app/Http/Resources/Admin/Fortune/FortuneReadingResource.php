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

            // Question + answer
            'questions' => $this->questions,
            'categories' => $this->categories,
            'reading_type' => $this->reading_type,
            'ai_response' => $this->ai_response,
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
