<?php

namespace App\Services\AiContentWriter;

use App\Models\AiContentSetting;
use App\Models\AiContentTemplate;
use App\Models\AiContentProject;
use App\Models\AiContentGeneration;
use App\Models\AiContentUsageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Content Writer Service
 *
 * Service หลักสำหรับระบบ AI Content Writer
 * รวมการทำงานของ OpenAI, Claude, และ Gemini
 *
 * @package App\Services\AiContentWriter
 */
class ContentWriterService
{
    /**
     * OpenAI Service
     */
    protected OpenAiService $openAi;

    /**
     * Claude Service
     */
    protected ClaudeService $claude;

    /**
     * Gemini Service
     */
    protected GeminiService $gemini;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->openAi = new OpenAiService();
        $this->claude = new ClaudeService();
        $this->gemini = new GeminiService();
    }

    // =========================================
    // Provider Management
    // =========================================

    /**
     * ดึงรายการ Providers ที่พร้อมใช้งาน
     *
     * @return array
     */
    public function getAvailableProviders(): array
    {
        return [
            'openai' => [
                'name' => 'OpenAI',
                'configured' => $this->openAi->isConfigured(),
                'models' => OpenAiService::MODELS,
            ],
            'claude' => [
                'name' => 'Claude (Anthropic)',
                'configured' => $this->claude->isConfigured(),
                'models' => ClaudeService::MODELS,
            ],
            'gemini' => [
                'name' => 'Gemini (Google)',
                'configured' => $this->gemini->isConfigured(),
                'models' => GeminiService::MODELS,
            ],
        ];
    }

    /**
     * ตรวจสอบสถานะ API ทั้งหมด
     *
     * @return array
     */
    public function checkAllApiStatus(): array
    {
        return [
            'openai' => [
                'configured' => $this->openAi->isConfigured(),
                'status' => $this->openAi->isConfigured() ? 'ready' : 'not_configured',
            ],
            'claude' => [
                'configured' => $this->claude->isConfigured(),
                'status' => $this->claude->isConfigured() ? 'ready' : 'not_configured',
            ],
            'gemini' => [
                'configured' => $this->gemini->isConfigured(),
                'status' => $this->gemini->isConfigured() ? 'ready' : 'not_configured',
            ],
        ];
    }

    /**
     * ทดสอบการเชื่อมต่อ Provider
     *
     * @param string $provider
     * @return array
     */
    public function testProvider(string $provider): array
    {
        return match ($provider) {
            'openai' => $this->openAi->testConnection(),
            'claude' => $this->claude->testConnection(),
            'gemini' => $this->gemini->testConnection(),
            default => ['success' => false, 'message' => 'Provider ไม่รู้จัก'],
        };
    }

    // =========================================
    // Content Generation
    // =========================================

    /**
     * สร้าง Content จาก Template
     *
     * @param AiContentTemplate $template
     * @param array $variables
     * @param int $userId
     * @param array $options
     * @return array
     */
    public function generateFromTemplate(
        AiContentTemplate $template,
        array $variables,
        int $userId,
        array $options = []
    ): array {
        // สร้าง Prompt จาก Template
        $userPrompt = $template->buildPrompt($variables);
        $systemPrompt = $template->system_prompt ?? '';

        // ใช้ค่าจาก options หรือจาก template
        $provider = $options['provider'] ?? $template->default_provider;
        $model = $options['model'] ?? $template->default_model;
        $temperature = $options['temperature'] ?? $template->temperature;
        $maxTokens = $options['max_tokens'] ?? $template->max_tokens;

        return $this->generate(
            $systemPrompt,
            $userPrompt,
            $userId,
            [
                'provider' => $provider,
                'model' => $model,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'template_id' => $template->id,
                'variables' => $variables,
            ]
        );
    }

    /**
     * สร้าง Content สำหรับ Project
     *
     * @param AiContentProject $project
     * @param array $options
     * @return AiContentGeneration
     */
    public function generateForProject(
        AiContentProject $project,
        array $options = []
    ): AiContentGeneration {
        // สร้าง Generation record
        $generation = AiContentGeneration::create([
            'project_id' => $project->id,
            'template_id' => $project->template_id,
            'user_id' => $project->user_id,
            'system_prompt' => $project->template?->system_prompt ?? $options['system_prompt'] ?? '',
            'user_prompt' => $options['user_prompt'] ?? $project->template?->buildPrompt($project->input_variables ?? []) ?? '',
            'input_variables' => $project->input_variables,
            'ai_provider' => $project->ai_provider,
            'ai_model' => $project->ai_model ?? $this->getDefaultModel($project->ai_provider),
            'temperature' => $options['temperature'] ?? $project->template?->temperature ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? $project->template?->max_tokens ?? 2000,
            'status' => 'pending',
            'version' => $project->generations()->count() + 1,
        ]);

        // อัพเดทสถานะโปรเจกต์
        $project->update(['status' => 'generating']);

        try {
            // เริ่มประมวลผล
            $generation->markAsProcessing();

            // สร้าง Content
            $result = $this->generate(
                $generation->system_prompt,
                $generation->user_prompt,
                $project->user_id,
                [
                    'provider' => $generation->ai_provider,
                    'model' => $generation->ai_model,
                    'temperature' => $generation->temperature,
                    'max_tokens' => $generation->max_tokens,
                    'generation_id' => $generation->id,
                ]
            );

            if ($result['success']) {
                $generation->markAsCompleted($result['content'], $result['usage'] ?? []);

                // ถ้าเป็น generation แรก ให้เลือกอัตโนมัติ
                if ($project->generations()->count() === 1) {
                    $project->selectGeneration($generation);
                }
            } else {
                $generation->markAsFailed($result['error'], $result['error_code'] ?? null);
                $project->update(['status' => 'failed']);
            }

        } catch (\Exception $e) {
            $generation->markAsFailed($e->getMessage(), 'EXCEPTION');
            $project->update(['status' => 'failed']);

            Log::error('Content generation failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $generation->fresh();
    }

    /**
     * สร้าง Content
     *
     * @param string $systemPrompt
     * @param string $userPrompt
     * @param int $userId
     * @param array $options
     * @return array
     */
    public function generate(
        string $systemPrompt,
        string $userPrompt,
        int $userId,
        array $options = []
    ): array {
        $provider = $options['provider'] ?? 'openai';
        $model = $options['model'] ?? $this->getDefaultModel($provider);

        // สร้าง Content ตาม Provider
        $result = match ($provider) {
            'openai' => $this->openAi->generateContent($systemPrompt, $userPrompt, array_merge($options, ['model' => $model])),
            'claude' => $this->claude->generateContent($systemPrompt, $userPrompt, array_merge($options, ['model' => $model])),
            'gemini' => $this->gemini->generateContent($systemPrompt, $userPrompt, array_merge($options, ['model' => $model])),
            default => ['success' => false, 'error' => 'Provider ไม่รู้จัก'],
        };

        // บันทึก Usage Log
        AiContentUsageLog::log([
            'user_id' => $userId,
            'generation_id' => $options['generation_id'] ?? null,
            'ai_provider' => $provider,
            'ai_model' => $model,
            'request_type' => 'chat',
            'prompt_tokens' => $result['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $result['usage']['completion_tokens'] ?? 0,
            'total_tokens' => $result['usage']['total_tokens'] ?? 0,
            'cost_usd' => $result['usage']['cost'] ?? 0,
            'response_time_ms' => $result['response_time_ms'] ?? null,
            'is_success' => $result['success'],
            'error_code' => $result['error_code'] ?? null,
            'error_message' => $result['error'] ?? null,
        ]);

        // อัพเดทสถิติ Template
        if (isset($options['template_id'])) {
            AiContentTemplate::find($options['template_id'])?->incrementUsage();
        }

        return $result;
    }

    /**
     * ดึง Default Model ของ Provider
     *
     * @param string $provider
     * @return string
     */
    protected function getDefaultModel(string $provider): string
    {
        return match ($provider) {
            'openai' => 'gpt-4o-mini',
            'claude' => 'claude-3-haiku-20240307',
            'gemini' => 'gemini-1.5-flash',
            default => 'gpt-4o-mini',
        };
    }

    // =========================================
    // Statistics
    // =========================================

    /**
     * ดึงสถิติระบบ
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'templates' => [
                'total' => AiContentTemplate::count(),
                'active' => AiContentTemplate::active()->count(),
            ],
            'projects' => [
                'total' => AiContentProject::count(),
                'completed' => AiContentProject::withStatus('completed')->count(),
                'generating' => AiContentProject::withStatus('generating')->count(),
            ],
            'generations' => [
                'total' => AiContentGeneration::count(),
                'completed' => AiContentGeneration::completed()->count(),
                'today' => AiContentGeneration::whereDate('created_at', today())->count(),
            ],
            'usage' => [
                'total_tokens' => AiContentUsageLog::sum('total_tokens'),
                'total_cost_usd' => AiContentUsageLog::sum('cost_usd'),
                'today_tokens' => AiContentUsageLog::today()->sum('total_tokens'),
                'today_cost_usd' => AiContentUsageLog::today()->sum('cost_usd'),
            ],
        ];
    }

    /**
     * ดึงสถิติการใช้งานของ User
     *
     * @param int $userId
     * @return array
     */
    public function getUserStatistics(int $userId): array
    {
        return [
            'projects' => AiContentProject::ofUser($userId)->count(),
            'generations' => AiContentGeneration::ofUser($userId)->count(),
            'usage' => AiContentUsageLog::getUserStats($userId, 'month'),
        ];
    }
}
