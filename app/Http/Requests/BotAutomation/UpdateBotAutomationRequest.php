<?php

namespace App\Http\Requests\BotAutomation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('automation'));
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'automation_type' => 'sometimes|required|in:scheduled_post,customer_support,sales_assistant,engagement,analytics',
            'content_source' => 'sometimes|required|in:custom,template,ai_generated',
            'template_id' => 'nullable|exists:bot_content_templates,id',
            'custom_content' => 'nullable|string',
            'ai_generation_prompt' => 'nullable|string',
            'ai_bot_profile_id' => 'nullable|exists:ai_bot_profiles,id',
            'target_platforms' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}
