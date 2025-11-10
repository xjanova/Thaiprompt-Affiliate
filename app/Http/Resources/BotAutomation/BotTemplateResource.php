<?php

namespace App\Http\Resources\BotAutomation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'content_type' => $this->content_type,
            'content' => $this->content,
            'media_urls' => $this->media_urls,
            'hashtags' => $this->hashtags,
            'variables' => $this->variables,
            'category' => $this->category,
            'is_ai_generated' => $this->is_ai_generated,
            'is_public' => $this->is_public,
            'usage_count' => $this->usage_count,
            'rating' => $this->rating,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
