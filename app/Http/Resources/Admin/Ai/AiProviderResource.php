<?php

namespace App\Http\Resources\Admin\Ai;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ AiProvider (Admin Mobile API)
 *
 * เปิดเผยเฉพาะข้อมูลที่ admin app ต้องใช้
 * — ไม่เปิดเผย api_key หรือ sensitive config
 */
class AiProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'type' => $this->provider_type,
            'is_active' => (bool) $this->is_active,
            'is_available' => (bool) $this->is_available,
            'api_endpoint' => $this->api_endpoint,
            'api_version' => $this->api_version,

            // Pricing (filter sensitive details)
            'pricing' => $this->pricing,

            // Models (when loaded)
            'models' => $this->whenLoaded('models', fn () => $this->models->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'display_name' => $m->display_name ?? $m->name,
                'is_active' => (bool) $m->is_active,
            ])),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
