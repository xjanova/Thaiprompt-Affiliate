<?php

namespace App\Services;

use App\Models\MlmProspect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Conversation Context Service
 *
 * Manages conversation context and history for intelligent
 * dialogue with users during LINE signup process:
 * - Maintains conversation history
 * - Tracks user preferences and behavior
 * - Provides context for AI responses
 * - Stores temporary data
 * - Manages conversation state
 */
class ConversationContextService
{
    /**
     * Cache TTL for context (1 hour)
     */
    private const CONTEXT_TTL = 3600;

    /**
     * Maximum messages to keep in history
     */
    private const MAX_HISTORY = 20;

    /**
     * Get conversation context for prospect
     *
     * @param MlmProspect $prospect
     * @return array
     */
    public function getContext(MlmProspect $prospect): array
    {
        $cacheKey = "conversation_context:{$prospect->id}";

        $context = Cache::get($cacheKey, [
            'prospect_id' => $prospect->id,
            'line_user_id' => $prospect->line_user_id,
            'line_display_name' => $prospect->line_display_name,
            'conversation_data' => $prospect->conversation_data ?? [],
            'current_step' => $prospect->conversation_step,
            'history' => [],
            'user_preferences' => [],
            'metadata' => [
                'started_at' => $prospect->conversation_started_at?->toIso8601String(),
                'progress' => $prospect->conversation_progress_percent ?? 0,
                'message_count' => $prospect->conversation_message_count ?? 0,
            ],
        ]);

        return $context;
    }

    /**
     * Update conversation context
     *
     * @param MlmProspect $prospect
     * @param array $updates
     * @return void
     */
    public function updateContext(MlmProspect $prospect, array $updates): void
    {
        $cacheKey = "conversation_context:{$prospect->id}";
        $context = $this->getContext($prospect);

        // Merge updates
        $context = array_merge($context, $updates);

        // Save to cache
        Cache::put($cacheKey, $context, now()->addSeconds(self::CONTEXT_TTL));

        Log::debug('Conversation context updated', [
            'prospect_id' => $prospect->id,
            'updates' => array_keys($updates),
        ]);
    }

    /**
     * Add message to conversation history
     *
     * @param MlmProspect $prospect
     * @param string $role 'user' or 'assistant'
     * @param string $message
     * @param array $metadata
     * @return void
     */
    public function addMessageToHistory(MlmProspect $prospect, string $role, string $message, array $metadata = []): void
    {
        $context = $this->getContext($prospect);
        $history = $context['history'] ?? [];

        // Add new message
        $history[] = [
            'role' => $role,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ];

        // Keep only last N messages
        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }

        // Update context
        $this->updateContext($prospect, ['history' => $history]);
    }

    /**
     * Get conversation history
     *
     * @param MlmProspect $prospect
     * @param int $limit
     * @return array
     */
    public function getHistory(MlmProspect $prospect, int $limit = 10): array
    {
        $context = $this->getContext($prospect);
        $history = $context['history'] ?? [];

        return array_slice($history, -$limit);
    }

    /**
     * Get conversation summary
     *
     * @param MlmProspect $prospect
     * @return string
     */
    public function getSummary(MlmProspect $prospect): string
    {
        $context = $this->getContext($prospect);
        $data = $context['conversation_data'] ?? [];

        $summary = "📊 สรุปข้อมูลการสมัคร:\n\n";

        if (!empty($data['name'])) {
            $summary .= "👤 ชื่อ: {$data['name']}\n";
        }

        if (!empty($data['phone'])) {
            $summary .= "📱 เบอร์: {$data['phone']}\n";
        }

        if (!empty($data['email'])) {
            $summary .= "📧 อีเมล: {$data['email']}\n";
        }

        $summary .= "\n📈 ความคืบหน้า: {$context['metadata']['progress']}%";

        return $summary;
    }

    /**
     * Track user preference
     *
     * @param MlmProspect $prospect
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setPreference(MlmProspect $prospect, string $key, $value): void
    {
        $context = $this->getContext($prospect);
        $preferences = $context['user_preferences'] ?? [];

        $preferences[$key] = $value;

        $this->updateContext($prospect, ['user_preferences' => $preferences]);
    }

    /**
     * Get user preference
     *
     * @param MlmProspect $prospect
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference(MlmProspect $prospect, string $key, $default = null)
    {
        $context = $this->getContext($prospect);
        $preferences = $context['user_preferences'] ?? [];

        return $preferences[$key] ?? $default;
    }

    /**
     * Store temporary data in context
     *
     * @param MlmProspect $prospect
     * @param string $key
     * @param mixed $value
     * @param int $ttl Seconds
     * @return void
     */
    public function setTemporaryData(MlmProspect $prospect, string $key, $value, int $ttl = 300): void
    {
        $cacheKey = "temp_data:{$prospect->id}:{$key}";
        Cache::put($cacheKey, $value, now()->addSeconds($ttl));
    }

    /**
     * Get temporary data
     *
     * @param MlmProspect $prospect
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getTemporaryData(MlmProspect $prospect, string $key, $default = null)
    {
        $cacheKey = "temp_data:{$prospect->id}:{$key}";
        return Cache::get($cacheKey, $default);
    }

    /**
     * Clear temporary data
     *
     * @param MlmProspect $prospect
     * @param string $key
     * @return void
     */
    public function clearTemporaryData(MlmProspect $prospect, string $key): void
    {
        $cacheKey = "temp_data:{$prospect->id}:{$key}";
        Cache::forget($cacheKey);
    }

    /**
     * Get relevant context for AI prompt
     *
     * @param MlmProspect $prospect
     * @return string
     */
    public function getAiContext(MlmProspect $prospect): string
    {
        $context = $this->getContext($prospect);

        $aiContext = "Conversation Context:\n";
        $aiContext .= "- Current Step: {$context['current_step']}\n";
        $aiContext .= "- Progress: {$context['metadata']['progress']}%\n";
        $aiContext .= "- Message Count: {$context['metadata']['message_count']}\n\n";

        // Add collected data
        $data = $context['conversation_data'] ?? [];
        if (!empty($data)) {
            $aiContext .= "Collected Data:\n";
            foreach ($data as $key => $value) {
                $aiContext .= "- {$key}: {$value}\n";
            }
            $aiContext .= "\n";
        }

        // Add recent history
        $history = array_slice($context['history'] ?? [], -5);
        if (!empty($history)) {
            $aiContext .= "Recent Messages:\n";
            foreach ($history as $msg) {
                $role = $msg['role'] === 'user' ? 'User' : 'Bot';
                $aiContext .= "- {$role}: {$msg['message']}\n";
            }
        }

        return $aiContext;
    }

    /**
     * Clear conversation context
     *
     * @param MlmProspect $prospect
     * @return void
     */
    public function clearContext(MlmProspect $prospect): void
    {
        $cacheKey = "conversation_context:{$prospect->id}";
        Cache::forget($cacheKey);

        Log::info('Conversation context cleared', [
            'prospect_id' => $prospect->id,
        ]);
    }

    /**
     * Export context for debugging/analysis
     *
     * @param MlmProspect $prospect
     * @return array
     */
    public function exportContext(MlmProspect $prospect): array
    {
        return $this->getContext($prospect);
    }

    /**
     * Check if user has shown specific behavior
     *
     * @param MlmProspect $prospect
     * @param string $behavior 'confusion', 'frustration', 'eagerness'
     * @return bool
     */
    public function hasShownBehavior(MlmProspect $prospect, string $behavior): bool
    {
        $context = $this->getContext($prospect);
        $preferences = $context['user_preferences'] ?? [];

        return isset($preferences["behavior_{$behavior}"]) && $preferences["behavior_{$behavior}"] === true;
    }

    /**
     * Mark user behavior
     *
     * @param MlmProspect $prospect
     * @param string $behavior
     * @return void
     */
    public function markBehavior(MlmProspect $prospect, string $behavior): void
    {
        $this->setPreference($prospect, "behavior_{$behavior}", true);

        Log::info('User behavior marked', [
            'prospect_id' => $prospect->id,
            'behavior' => $behavior,
        ]);
    }

    /**
     * Get conversation statistics
     *
     * @param MlmProspect $prospect
     * @return array
     */
    public function getStatistics(MlmProspect $prospect): array
    {
        $context = $this->getContext($prospect);
        $history = $context['history'] ?? [];

        $userMessages = array_filter($history, fn($msg) => $msg['role'] === 'user');
        $botMessages = array_filter($history, fn($msg) => $msg['role'] === 'assistant');

        return [
            'total_messages' => count($history),
            'user_messages' => count($userMessages),
            'bot_messages' => count($botMessages),
            'avg_response_time' => $this->calculateAvgResponseTime($history),
            'conversation_duration' => $prospect->conversation_started_at
                ? now()->diffInMinutes($prospect->conversation_started_at)
                : 0,
        ];
    }

    /**
     * Calculate average response time
     *
     * @param array $history
     * @return float Minutes
     */
    private function calculateAvgResponseTime(array $history): float
    {
        if (count($history) < 2) {
            return 0.0;
        }

        $times = [];
        for ($i = 1; $i < count($history); $i++) {
            if ($history[$i]['role'] === 'user' && $history[$i-1]['role'] === 'assistant') {
                $t1 = \Carbon\Carbon::parse($history[$i-1]['timestamp']);
                $t2 = \Carbon\Carbon::parse($history[$i]['timestamp']);
                $times[] = $t2->diffInSeconds($t1);
            }
        }

        if (empty($times)) {
            return 0.0;
        }

        return round(array_sum($times) / count($times) / 60, 2); // Convert to minutes
    }
}
