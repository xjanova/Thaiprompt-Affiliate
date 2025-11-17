<?php

namespace App\Services;

use App\Models\MessageSentiment;
use App\Models\MessageContext;
use App\Models\MessageSimilarity;
use App\Models\MessageEntity;
use App\Models\MessageIntent;
use App\Models\LineBotKeyword;
use App\Models\KeywordRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Advanced NLP Service
 *
 * ขยายความสามารถ NLP ด้วย context-aware analysis, conversation tracking,
 * semantic similarity matching, และ keyword relationship discovery
 */
class AdvancedNLPService
{
    /**
     * Analyze message with context awareness
     *
     * @param MessageSentiment $sentiment
     * @param string $message
     * @param string|null $lineUserId
     * @return array
     */
    public function analyzeWithContext(MessageSentiment $sentiment, string $message, ?string $lineUserId = null): array
    {
        return DB::transaction(function () use ($sentiment, $message, $lineUserId) {
            // 1. หา conversation context
            $context = $this->determineContext($sentiment, $message, $lineUserId);

            // 2. หา similar messages
            $similarMessages = $this->findSimilarMessages($message, 10);

            // 3. วิเคราะห์ conversation trends
            $conversationTrends = $this->analyzeConversationTrend($lineUserId, $sentiment);

            // 4. predict next intent
            $predictedIntent = $this->predictNextIntent($context, $sentimentData);

            // 5. suggest related keywords
            $relatedKeywords = $this->suggestRelatedKeywords($sentiment, $lineUserId);

            // 6. บันทึก context
            $this->recordContext($sentiment, $context, $lineUserId);

            return [
                'context' => $context,
                'similar_messages' => $similarMessages,
                'conversation_trends' => $conversationTrends,
                'predicted_intent' => $predictedIntent,
                'related_keywords' => $relatedKeywords,
            ];
        });
    }

    /**
     * Determine context type of message
     *
     * @param MessageSentiment $sentiment
     * @param string $message
     * @param string|null $lineUserId
     * @return array
     */
    private function determineContext(MessageSentiment $sentiment, string $message, ?string $lineUserId = null): array
    {
        $contextType = 'NEW_TOPIC';
        $parentMessage = null;
        $conversationId = null;
        $messagePosition = 1;
        $conversationLength = 1;
        $minutesSinceLast = null;

        // ถ้ามี user ID ให้หา previous message
        if ($lineUserId) {
            $previousContext = MessageContext::fromUser($lineUserId)
                ->latest()
                ->first();

            if ($previousContext) {
                $conversationId = $previousContext->conversation_id;
                $messagePosition = $previousContext->message_position + 1;
                $conversationLength = $previousContext->conversation_length + 1;
                $parentMessage = $previousContext->sentiment;

                // Calculate time gap
                $timeDiff = $sentiment->created_at->diffInMinutes($previousContext->sentiment->created_at);
                $minutesSinceLast = $timeDiff;

                // Determine context type based on content
                if ($sentiment->is_complaint && !$previousContext->sentiment->is_complaint) {
                    $contextType = 'ESCALATION';
                } elseif ($sentiment->sentiment_score > $previousContext->sentiment->sentiment_score) {
                    $contextType = 'RESOLUTION';
                } else {
                    $contextType = 'FOLLOW_UP';
                }
            } else {
                $contextType = 'FIRST_MESSAGE';
                $conversationId = uniqid('conv_', true);
            }
        }

        return [
            'context_type' => $contextType,
            'parent_message_id' => $parentMessage?->id,
            'conversation_id' => $conversationId,
            'message_position' => $messagePosition,
            'conversation_length' => $conversationLength,
            'minutes_since_last' => $minutesSinceLast,
        ];
    }

    /**
     * Find similar messages using semantic similarity
     *
     * @param string $message
     * @param int $limit
     * @return Collection
     */
    public function findSimilarMessages(string $message, int $limit = 10): Collection
    {
        // Extract words from message
        $words = $this->extractWords($message);
        $wordCount = count($words);

        // Find messages with word overlap
        $similarMessages = MessageSentiment::where('created_at', '>=', now()->subDays(90))
            ->where('id', '!=', null)
            ->get()
            ->filter(function ($msg) use ($words, $wordCount) {
                $msgWords = $this->extractWords($msg->message_text ?? '');
                $overlap = count(array_intersect($words, $msgWords));
                $similarity = $overlap / max($wordCount, 1);
                return $similarity >= 0.3; // 30% similarity threshold
            })
            ->sortByDesc(function ($msg) use ($words) {
                $msgWords = $this->extractWords($msg->message_text ?? '');
                $overlap = count(array_intersect($words, $msgWords));
                return $overlap / max(count($msgWords), 1);
            })
            ->take($limit);

        return $similarMessages;
    }

    /**
     * Analyze conversation sentiment trend
     *
     * @param string|null $lineUserId
     * @param MessageSentiment $currentSentiment
     * @return array
     */
    private function analyzeConversationTrend(?string $lineUserId, MessageSentiment $currentSentiment): array
    {
        if (!$lineUserId) {
            return ['trend' => 'NEUTRAL', 'direction' => 'STABLE'];
        }

        // Get last 5 messages from user
        $recentMessages = MessageSentiment::whereHas('context', function ($q) use ($lineUserId) {
            $q->where('line_user_id', $lineUserId);
        })
        ->latest()
        ->take(5)
        ->pluck('sentiment_score')
        ->toArray();

        if (count($recentMessages) < 2) {
            return ['trend' => 'NEUTRAL', 'direction' => 'STABLE'];
        }

        // Calculate trend
        $firstScore = end($recentMessages);
        $lastScore = reset($recentMessages);
        $diff = $lastScore - $firstScore;
        $avgScore = array_sum($recentMessages) / count($recentMessages);

        // Determine direction
        if ($diff > 0.2) {
            $direction = 'IMPROVING';
        } elseif ($diff < -0.2) {
            $direction = 'DECLINING';
        } else {
            $direction = 'STABLE';
        }

        // Calculate volatility
        $variance = 0;
        foreach ($recentMessages as $score) {
            $variance += pow($score - $avgScore, 2);
        }
        $volatility = sqrt($variance / count($recentMessages));

        if ($volatility > 0.3) {
            $trend = 'VOLATILE';
        } else {
            $trend = $direction;
        }

        return [
            'trend' => $trend,
            'direction' => $direction,
            'average_score' => $avgScore,
            'volatility' => round($volatility, 3),
        ];
    }

    /**
     * Predict next intent based on context
     *
     * @param array $context
     * @param array $sentimentData
     * @return array|null
     */
    private function predictNextIntent(array $context, array $sentimentData): ?array
    {
        // Based on current intent and sentiment
        $currentIntent = $sentimentData['primary_intent'] ?? null;
        $sentimentScore = $sentimentData['sentiment_score'] ?? 0;
        $isComplaint = $sentimentData['is_complaint'] ?? false;

        $predictions = [
            'COMPLAINT' => [
                'high_sentiment' => 'FEEDBACK',
                'low_sentiment' => 'SUPPORT',
                'neutral' => 'INQUIRY',
            ],
            'INQUIRY' => [
                'high_sentiment' => 'PURCHASE',
                'low_sentiment' => 'COMPLAINT',
                'neutral' => 'INQUIRY',
            ],
            'PURCHASE' => [
                'high_sentiment' => 'FEEDBACK',
                'low_sentiment' => 'SUPPORT',
                'neutral' => 'DELIVERY',
            ],
            'SUPPORT' => [
                'high_sentiment' => 'FEEDBACK',
                'low_sentiment' => 'ESCALATION',
                'neutral' => 'SUPPORT',
            ],
        ];

        if (!isset($predictions[$currentIntent])) {
            return null;
        }

        $sentimentCategory = $sentimentScore > 0.3 ? 'high_sentiment' : ($sentimentScore < -0.3 ? 'low_sentiment' : 'neutral');
        $nextIntent = $predictions[$currentIntent][$sentimentCategory] ?? null;

        return [
            'intent' => $nextIntent,
            'confidence' => 0.65,
            'reasoning' => "Based on $currentIntent with $sentimentCategory",
        ];
    }

    /**
     * Suggest related keywords for a message
     *
     * @param MessageSentiment $sentiment
     * @param string|null $lineUserId
     * @return Collection
     */
    public function suggestRelatedKeywords(MessageSentiment $sentiment, ?string $lineUserId = null): Collection
    {
        // Get entities from message
        $entities = $sentiment->entities()->limit(5)->pluck('entity_value')->toArray();

        if (empty($entities)) {
            return collect();
        }

        // Find keywords with matching entities
        $keywords = LineBotKeyword::active()
            ->get()
            ->filter(function ($keyword) use ($entities) {
                $keywordText = strtolower($keyword->keyword_text);
                foreach ($entities as $entity) {
                    if (stripos($keywordText, $entity) !== false) {
                        return true;
                    }
                }
                return false;
            })
            ->take(5);

        // Also find related keywords via relationships
        if ($keywords->isNotEmpty()) {
            $relatedKeywords = KeywordRelationship::active()
                ->whereIn('source_keyword_id', $keywords->pluck('id'))
                ->where('strength_score', '>=', 0.6)
                ->with('relatedKeyword')
                ->pluck('relatedKeyword')
                ->filter();

            $keywords = $keywords->concat($relatedKeywords)->unique('id')->take(10);
        }

        return $keywords;
    }

    /**
     * Record message context to database
     *
     * @param MessageSentiment $sentiment
     * @param array $contextData
     * @param string|null $lineUserId
     * @return MessageContext
     */
    private function recordContext(MessageSentiment $sentiment, array $contextData, ?string $lineUserId = null): MessageContext
    {
        $contextArray = $contextData;
        $contextArray['message_sentiment_id'] = $sentiment->id;
        $contextArray['line_user_id'] = $lineUserId;

        return MessageContext::create($contextArray);
    }

    /**
     * Discover keyword relationships from message patterns
     *
     * @param int $days
     * @return void
     */
    public function discoverKeywordRelationships(int $days = 30): void
    {
        // Get all messages from last N days
        $messages = MessageSentiment::with('entities', 'intents')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        foreach ($messages as $message) {
            $entities = $message->entities()->pluck('entity_value')->toArray();

            // Find keywords matching entities
            $keywords = LineBotKeyword::get();

            foreach ($keywords as $keyword1) {
                $hasMatch = false;
                foreach ($entities as $entity) {
                    if (stripos($keyword1->keyword_text, $entity) !== false) {
                        $hasMatch = true;
                        break;
                    }
                }

                if (!$hasMatch) continue;

                // Find co-occurring keywords
                foreach ($keywords as $keyword2) {
                    if ($keyword1->id >= $keyword2->id) continue;

                    $coOccurs = $messages
                        ->filter(function ($msg) use ($keyword1, $keyword2) {
                            $text = strtolower($msg->message_text ?? '');
                            return stripos($text, $keyword1->keyword_text) !== false &&
                                   stripos($text, $keyword2->keyword_text) !== false;
                        })
                        ->count();

                    if ($coOccurs >= 2) {
                        // Record or update relationship
                        $relationship = KeywordRelationship::firstOrCreate(
                            [
                                'source_keyword_id' => $keyword1->id,
                                'related_keyword_id' => $keyword2->id,
                            ],
                            [
                                'relationship_type' => 'RELATED',
                                'discovery_method' => 'CO_OCCURRENCE',
                                'co_occurrence_frequency' => $coOccurs,
                                'strength_score' => min($coOccurs / 10, 1.0),
                                'confidence' => 0.6,
                            ]
                        );

                        if ($relationship->wasRecentlyCreated) {
                            $relationship->update([
                                'co_occurrence_frequency' => $coOccurs,
                                'strength_score' => min($coOccurs / 10, 1.0),
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Extract words from text
     *
     * @param string $text
     * @return array
     */
    private function extractWords(string $text): array
    {
        // Remove punctuation and convert to lowercase
        $text = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));

        // Split into words
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        // Remove short words
        return array_filter($words, fn($w) => strlen($w) > 2);
    }

    /**
     * Analyze semantic similarity between two messages
     *
     * @param MessageSentiment $message1
     * @param MessageSentiment $message2
     * @return float
     */
    public function calculateSemanticSimilarity(MessageSentiment $message1, MessageSentiment $message2): float
    {
        $words1 = $this->extractWords($message1->message_text ?? '');
        $words2 = $this->extractWords($message2->message_text ?? '');

        if (empty($words1) || empty($words2)) {
            return 0;
        }

        // Calculate Jaccard similarity
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Get conversation analytics for a user
     *
     * @param string $lineUserId
     * @param int $days
     * @return array
     */
    public function getUserConversationAnalytics(string $lineUserId, int $days = 30): array
    {
        $contexts = MessageContext::fromUser($lineUserId)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($contexts->isEmpty()) {
            return [
                'total_messages' => 0,
                'total_conversations' => 0,
                'avg_sentiment' => 0,
                'top_intents' => [],
                'sentiment_direction' => 'STABLE',
            ];
        }

        // Analyze data
        $totalMessages = $contexts->count();
        $totalConversations = $contexts->unique('conversation_id')->count();
        $avgSentiment = $contexts->avg(function ($c) {
            return $c->sentiment->sentiment_score ?? 0;
        });

        // Find top intents
        $topIntents = $contexts
            ->flatMap(function ($c) {
                return $c->intents_in_conversation ?? [];
            })
            ->countBy()
            ->sortDesc()
            ->take(5);

        // Determine sentiment direction
        $sentiments = $contexts->pluck('sentiment.sentiment_score')->toArray();
        if (count($sentiments) > 1) {
            $direction = end($sentiments) > reset($sentiments) ? 'IMPROVING' : 'DECLINING';
        } else {
            $direction = 'STABLE';
        }

        return [
            'total_messages' => $totalMessages,
            'total_conversations' => $totalConversations,
            'avg_sentiment' => round($avgSentiment, 2),
            'top_intents' => $topIntents->toArray(),
            'sentiment_direction' => $direction,
            'last_activity' => $contexts->max('created_at')?->diffForHumans(),
        ];
    }
}
