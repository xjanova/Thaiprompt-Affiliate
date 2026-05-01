<?php

namespace App\Http\Resources\Admin\Ai;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ AiBotProfile (Admin Mobile API)
 *
 * ไม่เปิดเผย LINE OA tokens (line_oa_*_token, line_oa_channel_secret)
 */
class AiBotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name ?? $this->name,
            'description' => $this->description,
            'avatar_url' => $this->avatar_url,

            // Provider + model
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->display_name ?? $this->provider?->name,
                'type' => $this->provider?->provider_type,
            ]),
            'model' => $this->whenLoaded('model', fn () => [
                'id' => $this->model?->id,
                'name' => $this->model?->display_name ?? $this->model?->name,
            ]),

            // Tuning params (read-only display)
            'tuning' => [
                'temperature' => (float) $this->temperature,
                'max_tokens' => (int) $this->max_tokens,
                'top_p' => (float) $this->top_p,
                'frequency_penalty' => (float) $this->frequency_penalty,
                'presence_penalty' => (float) $this->presence_penalty,
            ],

            // Capabilities
            'capabilities' => [
                'knowledge_base' => (bool) $this->enable_knowledge_base,
                'web_search' => (bool) $this->enable_web_search,
                'code_interpreter' => (bool) $this->enable_code_interpreter,
            ],

            // LINE OA — แสดงเฉพาะ channel_id (กัน token)
            'line_oa' => [
                'channel_id' => $this->line_oa_channel_id,
                'is_connected' => ! empty($this->line_oa_access_token),
            ],

            // Status
            'is_active' => (bool) $this->is_active,
            'is_public' => (bool) $this->is_public,
            'is_rentable' => (bool) $this->is_rentable,

            // Rental
            'rental' => [
                'price_per_month' => (float) $this->rental_price_per_month,
                'price_per_message' => (float) $this->rental_price_per_message,
                'commission_rate' => (float) $this->commission_rate,
            ],

            // Owner
            'owner_id' => $this->owner_id,

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
