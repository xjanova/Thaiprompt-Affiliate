<?php

namespace App\Http\Controllers\Api\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin Mobile API: Ai/AiPlaygroundController
 *
 * Powers the Warroom Juntra `/predict` page: run one prompt against multiple
 * providers in turn and return all responses side-by-side with timing.
 *
 * Sequential, not parallel — Laravel/PHP isn't naturally async. For the
 * warroom workbench this is fine: it's an interactive page, the operator
 * waits a few seconds for the side-by-side comparison. If we ever need
 * parallel we can switch to Http::pool() + the SDK provider classes
 * directly (current call path goes through FortuneAIService which is too
 * tightly coupled to settings to fork safely).
 */
class AiPlaygroundController extends Controller
{
    /**
     * GET /api/admin/ai/playground/providers
     *
     * Lists active providers with their active models. Lets the warroom
     * populate a "which providers to compare" multi-select dropdown.
     */
    public function providers(): JsonResponse
    {
        $providers = AiProvider::query()
            ->where('is_active', true)
            ->where('is_available', true)
            ->with(['activeModels:id,provider_id,model_identifier,display_name,context_window,is_active'])
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'provider_type']);

        return response()->json([
            'success' => true,
            'data' => [
                'providers' => $providers->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'display_name' => $p->display_name,
                    'provider_type' => $p->provider_type,
                    'models' => $p->activeModels->map(fn ($m) => [
                        'id' => $m->id,
                        'name' => $m->model_identifier,
                        'display_name' => $m->display_name,
                        'context_window' => $m->context_window,
                    ])->values(),
                ])->values(),
            ],
        ]);
    }

    /**
     * POST /api/admin/ai/playground/run
     *
     * Body:
     *   {
     *     system_prompt: string (required),
     *     user_message: string (required),
     *     providers: [
     *       { provider: 'gemini'|'anthropic'|'openai'|...,
     *         model?: string,
     *         api_key?: string,        // optional override — defaults to settings key
     *         temperature?: float,     // default 0.7
     *         max_tokens?: int         // default 600
     *       },
     *       ...
     *     ]
     *   }
     *
     * Returns: { results: [{ provider, model, success, latency_ms, response?, error? }, ...] }
     */
    public function run(Request $request, FortuneAIService $aiService): JsonResponse
    {
        $data = $request->validate([
            'system_prompt' => 'required|string|max:8000',
            'user_message' => 'required|string|max:4000',
            'providers' => 'required|array|min:1|max:5',
            'providers.*.provider' => 'required|string|max:50',
            'providers.*.model' => 'nullable|string|max:120',
            'providers.*.api_key' => 'nullable|string|max:512',
            'providers.*.temperature' => 'nullable|numeric|min:0|max:2',
            'providers.*.max_tokens' => 'nullable|integer|min:50|max:4096',
        ]);

        $results = [];
        foreach ($data['providers'] as $cfg) {
            $started = microtime(true);
            $providerName = $cfg['provider'];
            $modelName = $cfg['model'] ?? null;

            $entry = [
                'provider' => $providerName,
                'model' => $modelName,
                'success' => false,
                'latency_ms' => 0,
                'response' => null,
                'error' => null,
                'tokens' => null,
            ];

            try {
                $config = [
                    'temperature' => $cfg['temperature'] ?? 0.7,
                    'max_tokens' => $cfg['max_tokens'] ?? 600,
                ];

                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $data['system_prompt'],
                    userMessage: $data['user_message'],
                    config: $config,
                    providerOverride: $providerName,
                    modelOverride: $modelName,
                    apiKeyOverride: $cfg['api_key'] ?? null,
                );

                // FortuneAIService::chatWithCustomSystemPrompt returns
                // ['response' => string, ...] via sanitizeChatResult — the
                // key is 'response', NOT 'content' / 'text'.
                $entry['success'] = true;
                $entry['response'] = is_array($result)
                    ? ($result['response'] ?? $result['content'] ?? $result['text'] ?? null)
                    : (string) $result;
                $entry['tokens'] = is_array($result) ? ($result['tokens_used'] ?? $result['tokens'] ?? null) : null;
                if (is_array($result)) {
                    $entry['raw'] = array_intersect_key($result, array_flip(['response', 'content', 'text', 'tokens_used', 'finish_reason']));
                }
            } catch (Throwable $e) {
                $entry['error'] = $e->getMessage();
                Log::warning('AiPlayground: provider call failed', [
                    'provider' => $providerName,
                    'model' => $modelName,
                    'error' => $e->getMessage(),
                ]);
            }

            $entry['latency_ms'] = (int) round((microtime(true) - $started) * 1000);
            $results[] = $entry;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
