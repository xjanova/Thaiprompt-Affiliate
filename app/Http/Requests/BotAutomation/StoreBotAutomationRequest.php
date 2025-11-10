<?php

namespace App\Http\Requests\BotAutomation;

use Illuminate\Foundation\Http\FormRequest;

class StoreBotAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'automation_type' => 'required|in:scheduled_post,customer_support,sales_assistant,engagement,analytics',
            'trigger_type' => 'required|in:schedule,event,webhook,manual',
            'schedule_type' => 'nullable|required_if:trigger_type,schedule|in:once,hourly,daily,weekly,monthly,cron',
            'cron_expression' => 'nullable|required_if:schedule_type,cron|string',
            'scheduled_at' => 'nullable|required_if:schedule_type,once|date',
            'schedule_config' => 'nullable|array',
            'content_source' => 'required|in:custom,template,ai_generated',
            'template_id' => 'nullable|required_if:content_source,template|exists:bot_content_templates,id',
            'custom_content' => 'nullable|required_if:content_source,custom|string',
            'ai_generation_prompt' => 'nullable|required_if:content_source,ai_generated|string',
            'ai_bot_profile_id' => 'nullable|exists:ai_bot_profiles,id',
            'target_platforms' => 'nullable|array',
            'target_platforms.*' => 'exists:bot_platform_connections,id',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณาระบุชื่อ automation',
            'automation_type.required' => 'กรุณาเลือกประเภท automation',
            'trigger_type.required' => 'กรุณาเลือกประเภท trigger',
            'content_source.required' => 'กรุณาเลือกแหล่งที่มาของคอนเทนต์',
            'custom_content.required_if' => 'กรุณาระบุคอนเทนต์',
            'ai_generation_prompt.required_if' => 'กรุณาระบุ prompt สำหรับ AI',
            'template_id.required_if' => 'กรุณาเลือกเทมเพลต',
        ];
    }
}
