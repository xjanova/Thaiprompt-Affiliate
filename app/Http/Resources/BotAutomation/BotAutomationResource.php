<?php

namespace App\Http\Resources\BotAutomation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotAutomationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'automation_type' => $this->automation_type,
            'trigger_type' => $this->trigger_type,
            'schedule_type' => $this->schedule_type,
            'cron_expression' => $this->cron_expression,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'content_source' => $this->content_source,
            'custom_content' => $this->custom_content,
            'ai_generation_prompt' => $this->ai_generation_prompt,
            'target_platforms' => $this->target_platforms,
            'is_active' => $this->is_active,
            'is_rental' => $this->is_rental,
            'last_executed_at' => $this->last_executed_at?->toIso8601String(),
            'next_execution_at' => $this->next_execution_at?->toIso8601String(),
            'statistics' => [
                'total_executions' => $this->total_executions,
                'successful_executions' => $this->successful_executions,
                'failed_executions' => $this->failed_executions,
                'success_rate' => $this->getSuccessRate(),
            ],
            'ai_bot_profile' => new AiBotProfileResource($this->whenLoaded('aiBotProfile')),
            'template' => new BotTemplateResource($this->whenLoaded('template')),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
