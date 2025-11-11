<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Conversation Service
 *
 * Provides Natural Language Understanding (NLU) and intelligent
 * conversation features for LINE OA signup process:
 * - Intent detection (what user wants)
 * - Entity extraction (extract data from messages)
 * - Sentiment analysis (user mood/feeling)
 * - Smart suggestions (help user complete forms)
 * - Context-aware responses
 */
class AiConversationService
{
    /**
     * OpenAI API settings (can be replaced with other AI services)
     */
    private const AI_MODEL = 'gpt-3.5-turbo';
    private const MAX_TOKENS = 150;
    private const TEMPERATURE = 0.7;

    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Detect user intent from message
     *
     * @param string $message
     * @param string $currentContext Current step/context
     * @return array
     */
    public function detectIntent(string $message, string $currentContext = ''): array
    {
        $cacheKey = "ai_intent:" . md5($message . $currentContext);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($message, $currentContext) {
            $prompt = $this->buildIntentPrompt($message, $currentContext);

            try {
                $response = $this->callAiApi($prompt, 100);
                return $this->parseIntentResponse($response);
            } catch (\Exception $e) {
                Log::error('AI intent detection failed', [
                    'message' => $message,
                    'error' => $e->getMessage(),
                ]);

                // Fallback to rule-based detection
                return $this->fallbackIntentDetection($message);
            }
        });
    }

    /**
     * Extract entities from user message
     *
     * Entities: name, phone, email, address, etc.
     *
     * @param string $message
     * @param string $expectedType Type of data expected
     * @return array
     */
    public function extractEntities(string $message, string $expectedType = 'any'): array
    {
        $cacheKey = "ai_entities:" . md5($message . $expectedType);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($message, $expectedType) {
            // Use regex patterns first (faster)
            $entities = $this->extractEntitiesWithRegex($message);

            // If AI is available and we need more context, use it
            if (empty($entities) || $expectedType !== 'any') {
                $prompt = $this->buildEntityExtractionPrompt($message, $expectedType);

                try {
                    $aiResponse = $this->callAiApi($prompt, 100);
                    $aiEntities = $this->parseEntityResponse($aiResponse);
                    $entities = array_merge($entities, $aiEntities);
                } catch (\Exception $e) {
                    Log::warning('AI entity extraction failed, using regex only', [
                        'message' => $message,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $entities;
        });
    }

    /**
     * Analyze sentiment of user message
     *
     * @param string $message
     * @return array ['sentiment' => 'positive|neutral|negative', 'score' => float, 'emotion' => string]
     */
    public function analyzeSentiment(string $message): array
    {
        $cacheKey = "ai_sentiment:" . md5($message);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($message) {
            // Quick rule-based sentiment for Thai
            $sentiment = $this->quickSentimentAnalysis($message);

            // If AI is available, get more detailed analysis
            try {
                $prompt = "วิเคราะห์อารมณ์ของข้อความนี้: \"{$message}\"\n\nตอบเป็น JSON: {\"sentiment\": \"positive/neutral/negative\", \"score\": 0-1, \"emotion\": \"happy/sad/angry/confused/neutral\"}";

                $aiResponse = $this->callAiApi($prompt, 50);
                $parsed = json_decode($aiResponse, true);

                if ($parsed) {
                    return $parsed;
                }
            } catch (\Exception $e) {
                Log::debug('AI sentiment analysis not available, using rule-based');
            }

            return $sentiment;
        });
    }

    /**
     * Generate smart suggestion for user
     *
     * @param string $fieldType Type of field (name, phone, email, etc.)
     * @param string $currentInput Current user input
     * @param array $context Conversation context
     * @return array
     */
    public function generateSuggestion(string $fieldType, string $currentInput, array $context = []): array
    {
        $suggestions = [];

        switch ($fieldType) {
            case 'name':
                $suggestions = $this->suggestName($currentInput, $context);
                break;

            case 'phone':
                $suggestions = $this->suggestPhone($currentInput);
                break;

            case 'email':
                $suggestions = $this->suggestEmail($currentInput, $context);
                break;

            case 'address':
                $suggestions = $this->suggestAddress($currentInput, $context);
                break;
        }

        return [
            'has_suggestions' => !empty($suggestions),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Auto-complete user input using AI
     *
     * @param string $fieldType
     * @param string $partialInput
     * @param array $context
     * @return array
     */
    public function autoComplete(string $fieldType, string $partialInput, array $context = []): array
    {
        // Extract what we can from LINE profile in context
        $lineProfile = $context['line_profile'] ?? [];

        $completions = [];

        switch ($fieldType) {
            case 'name':
                // If LINE display name exists, suggest it
                if (!empty($lineProfile['display_name'])) {
                    $completions[] = [
                        'value' => $lineProfile['display_name'],
                        'confidence' => 0.8,
                        'source' => 'line_profile',
                    ];
                }
                break;

            case 'email':
                // Try to construct email from name or phone
                if (!empty($context['name'])) {
                    $emailBase = strtolower(str_replace(' ', '', $context['name']));
                    $completions[] = [
                        'value' => $emailBase . '@gmail.com',
                        'confidence' => 0.5,
                        'source' => 'generated',
                    ];
                }
                break;

            case 'phone':
                // If partial phone is entered, format it
                if (preg_match('/^\d{9,10}$/', $partialInput)) {
                    $formatted = '0' . ltrim($partialInput, '0');
                    $completions[] = [
                        'value' => $formatted,
                        'confidence' => 0.9,
                        'source' => 'formatted',
                    ];
                }
                break;
        }

        return [
            'has_completions' => !empty($completions),
            'completions' => $completions,
        ];
    }

    /**
     * Generate helpful response based on user confusion
     *
     * @param string $userMessage
     * @param string $expectedInput What we're asking for
     * @return string
     */
    public function generateHelpResponse(string $userMessage, string $expectedInput): string
    {
        $helpMessages = [
            'name' => "กรุณากรอกชื่อ-นามสกุลของคุณ\n\nตัวอย่าง: สมชาย ใจดี",
            'phone' => "กรุณากรอกเบอร์โทรศัพท์มือถือ\n\nตัวอย่าง: 0812345678",
            'email' => "กรุณากรอกอีเมลของคุณ\n\nตัวอย่าง: somchai@gmail.com",
            'address' => "กรุณากรอกที่อยู่ของคุณ\n\nตัวอย่าง: 123 ถนนสุขุมวิท แขวงคลองเตย กรุงเทพฯ",
        ];

        return $helpMessages[$expectedInput] ?? "กรุณากรอกข้อมูลที่ถูกต้อง";
    }

    /**
     * Check if user is confused or needs help
     *
     * @param string $message
     * @return bool
     */
    public function isUserConfused(string $message): bool
    {
        $confusionKeywords = [
            'ไม่เข้าใจ', 'ไม่รู้', 'ช่วย', 'อะไร', 'ยังไง', 'ทำไม',
            'help', 'what', 'how', '?', '??', '???',
            'ไม่ทราบ', 'สงสัย', 'งง',
        ];

        $lowerMessage = mb_strtolower($message);

        foreach ($confusionKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if user wants to restart or cancel
     *
     * @param string $message
     * @return array ['action' => 'restart|cancel|continue', 'confidence' => float]
     */
    public function detectUserAction(string $message): array
    {
        $lowerMessage = mb_strtolower(trim($message));

        // Restart keywords
        $restartKeywords = ['เริ่มใหม่', 'ใหม่', 'เริ่มต้น', 'restart', 'start over', 'reset'];
        foreach ($restartKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                return ['action' => 'restart', 'confidence' => 0.9];
            }
        }

        // Cancel keywords
        $cancelKeywords = ['ยกเลิก', 'ไม่สมัคร', 'ไม่เอา', 'cancel', 'quit', 'stop'];
        foreach ($cancelKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                return ['action' => 'cancel', 'confidence' => 0.9];
            }
        }

        return ['action' => 'continue', 'confidence' => 1.0];
    }

    // ==================== Private Helper Methods ====================

    /**
     * Build intent detection prompt for AI
     */
    private function buildIntentPrompt(string $message, string $context): string
    {
        $prompt = "Context: User is in LINE signup process";
        if ($context) {
            $prompt .= " at step: {$context}";
        }

        $prompt .= "\n\nUser message: \"{$message}\"\n\n";
        $prompt .= "Detect intent. Return JSON: {\"intent\": \"provide_info|ask_help|confused|restart|cancel\", \"confidence\": 0-1}";

        return $prompt;
    }

    /**
     * Build entity extraction prompt
     */
    private function buildEntityExtractionPrompt(string $message, string $expectedType): string
    {
        $prompt = "Extract {$expectedType} from: \"{$message}\"\n\n";
        $prompt .= "Return JSON: {\"type\": \"name|phone|email|address\", \"value\": \"extracted_value\", \"confidence\": 0-1}";

        return $prompt;
    }

    /**
     * Call AI API (OpenAI or alternative)
     */
    private function callAiApi(string $prompt, int $maxTokens = 150): string
    {
        // Check if OpenAI API key is configured
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            throw new \Exception('AI API not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(10)->post('https://api.openai.com/v1/chat/completions', [
            'model' => self::AI_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant for LINE OA signup process. Respond in Thai when appropriate.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => self::TEMPERATURE,
        ]);

        if (!$response->successful()) {
            throw new \Exception('AI API request failed: ' . $response->body());
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Parse AI intent response
     */
    private function parseIntentResponse(string $response): array
    {
        $parsed = json_decode($response, true);

        if ($parsed && isset($parsed['intent'])) {
            return $parsed;
        }

        return ['intent' => 'unknown', 'confidence' => 0.0];
    }

    /**
     * Parse AI entity response
     */
    private function parseEntityResponse(string $response): array
    {
        $parsed = json_decode($response, true);

        if ($parsed && isset($parsed['value'])) {
            return [$parsed];
        }

        return [];
    }

    /**
     * Fallback intent detection using rules
     */
    private function fallbackIntentDetection(string $message): array
    {
        $lowerMessage = mb_strtolower($message);

        // Check for help requests
        if (str_contains($lowerMessage, 'ช่วย') || str_contains($lowerMessage, 'help')) {
            return ['intent' => 'ask_help', 'confidence' => 0.8];
        }

        // Check for confusion
        if ($this->isUserConfused($message)) {
            return ['intent' => 'confused', 'confidence' => 0.7];
        }

        // Check for restart/cancel
        $action = $this->detectUserAction($message);
        if ($action['action'] !== 'continue') {
            return ['intent' => $action['action'], 'confidence' => $action['confidence']];
        }

        // Default: user is providing information
        return ['intent' => 'provide_info', 'confidence' => 0.6];
    }

    /**
     * Extract entities using regex patterns
     */
    private function extractEntitiesWithRegex(string $message): array
    {
        $entities = [];

        // Phone number
        if (preg_match('/(?:0|\+?66)[6-9]\d{8}/', $message, $matches)) {
            $entities[] = [
                'type' => 'phone',
                'value' => $matches[0],
                'confidence' => 0.9,
            ];
        }

        // Email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $matches)) {
            $entities[] = [
                'type' => 'email',
                'value' => $matches[0],
                'confidence' => 0.9,
            ];
        }

        return $entities;
    }

    /**
     * Quick sentiment analysis (rule-based for Thai)
     */
    private function quickSentimentAnalysis(string $message): array
    {
        $positiveWords = ['ดี', 'ชอบ', 'สนใจ', 'ยินดี', 'สบาย', 'เยี่ยม', 'ขอบคุณ', 'ค่ะ', 'ครับ'];
        $negativeWords = ['ไม่', 'ไม่ชอบ', 'แย่', 'เกลียด', 'ลำบาก', 'ยาก', 'งง'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($message, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($message, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return ['sentiment' => 'positive', 'score' => 0.7, 'emotion' => 'happy'];
        } elseif ($negativeCount > $positiveCount) {
            return ['sentiment' => 'negative', 'score' => 0.3, 'emotion' => 'confused'];
        }

        return ['sentiment' => 'neutral', 'score' => 0.5, 'emotion' => 'neutral'];
    }

    /**
     * Suggest name completion
     */
    private function suggestName(string $input, array $context): array
    {
        // If LINE display name exists in context
        if (!empty($context['line_display_name'])) {
            return [
                [
                    'value' => $context['line_display_name'],
                    'source' => 'LINE Profile',
                    'confidence' => 0.8,
                ]
            ];
        }

        return [];
    }

    /**
     * Suggest phone formatting
     */
    private function suggestPhone(string $input): array
    {
        $cleaned = preg_replace('/[^\d]/', '', $input);

        if (strlen($cleaned) === 9) {
            return [
                ['value' => '0' . $cleaned, 'source' => 'formatted', 'confidence' => 0.9]
            ];
        }

        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '66')) {
            return [
                ['value' => '0' . substr($cleaned, 2), 'source' => 'formatted', 'confidence' => 0.9]
            ];
        }

        return [];
    }

    /**
     * Suggest email completion
     */
    private function suggestEmail(string $input, array $context): array
    {
        $suggestions = [];

        // Common domain suggestions
        $commonDomains = ['@gmail.com', '@hotmail.com', '@yahoo.com', '@outlook.com'];

        if (!str_contains($input, '@')) {
            foreach ($commonDomains as $domain) {
                $suggestions[] = [
                    'value' => $input . $domain,
                    'source' => 'suggested',
                    'confidence' => 0.6,
                ];
            }
        }

        return array_slice($suggestions, 0, 3); // Top 3 suggestions
    }

    /**
     * Suggest address completion
     */
    private function suggestAddress(string $input, array $context): array
    {
        // This could be enhanced with Google Places API
        // For now, return empty
        return [];
    }
}
