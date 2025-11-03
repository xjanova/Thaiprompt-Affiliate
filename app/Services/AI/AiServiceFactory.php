<?php

namespace App\Services\AI;

use App\Models\AiProvider;
use App\Models\AiModel;
use App\Models\AiBotProfile;

class AiServiceFactory
{
    /**
     * สร้าง AI Service จาก Provider และ Model
     */
    public static function create(AiProvider $provider, AiModel $model): BaseAiService
    {
        return match ($provider->name) {
            'openai' => new OpenAiService($provider, $model),
            'anthropic' => new ClaudeService($provider, $model),
            'deepseek' => new OpenAiService($provider, $model), // DeepSeek ใช้ OpenAI-compatible API
            'deepseek-local' => new LocalAiService($provider, $model),
            'google' => new OpenAiService($provider, $model), // Gemini ก็ใช้ OpenAI-compatible
            default => throw new \Exception("Unsupported provider: {$provider->name}"),
        };
    }

    /**
     * สร้าง AI Service จาก Bot Profile
     */
    public static function createFromBot(AiBotProfile $bot): BaseAiService
    {
        $bot->load(['provider', 'model']);

        if (!$bot->provider || !$bot->model) {
            throw new \Exception('Bot profile missing provider or model');
        }

        if (!$bot->provider->is_active) {
            throw new \Exception("Provider {$bot->provider->display_name} is not active");
        }

        if (!$bot->model->is_active) {
            throw new \Exception("Model {$bot->model->display_name} is not active");
        }

        return self::create($bot->provider, $bot->model);
    }

    /**
     * สร้าง AI Service จาก provider และ model ID
     */
    public static function createById(int $providerId, int $modelId): BaseAiService
    {
        $provider = AiProvider::findOrFail($providerId);
        $model = AiModel::findOrFail($modelId);

        return self::create($provider, $model);
    }

    /**
     * ทดสอบการเชื่อมต่อ Provider ทั้งหมดที่ active
     */
    public static function testAllProviders(): array
    {
        $providers = AiProvider::where('is_active', true)
            ->with(['models' => function ($query) {
                $query->where('is_active', true)->limit(1);
            }])
            ->get();

        $results = [];

        foreach ($providers as $provider) {
            if ($provider->models->isEmpty()) {
                $results[] = [
                    'provider' => $provider->display_name,
                    'success' => false,
                    'message' => 'No active models',
                ];
                continue;
            }

            try {
                $service = self::create($provider, $provider->models->first());
                $testResult = $service->testConnection();

                $results[] = [
                    'provider' => $provider->display_name,
                    'success' => $testResult['success'],
                    'message' => $testResult['message'],
                    'details' => $testResult['details'] ?? null,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'provider' => $provider->display_name,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
