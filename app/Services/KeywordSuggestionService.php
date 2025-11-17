<?php

namespace App\Services;

use App\Models\LineBotKeyword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Keyword Suggestion Service
 *
 * วิเคราะห์ no-match messages และเสนอ keywords ใหม่
 */
class KeywordSuggestionService
{
    /**
     * ดึง suggestions จาก no-match messages (30 วันที่ผ่านมา)
     *
     * @param int $days จำนวนวันที่วิเคราะห์
     * @param int $minFrequency จำนวนครั้งต่ำสุดสำหรับ suggestion
     * @return array
     */
    public function getSuggestions(int $days = 30, int $minFrequency = 3): array
    {
        // ดึง no-match messages
        $noMatches = $this->getNoMatchMessages($days);

        if ($noMatches->isEmpty()) {
            return [];
        }

        // วิเคราะห์ patterns
        $patterns = $this->analyzePatterns($noMatches);

        // กรอง by min frequency
        $suggestions = $patterns->filter(function ($item) use ($minFrequency) {
            return $item['frequency'] >= $minFrequency;
        })->values();

        // คำนวณ confidence score
        $suggestions = $suggestions->map(function ($suggestion) use ($noMatches) {
            $suggestion['confidence'] = $this->calculateConfidence(
                $suggestion['frequency'],
                $noMatches->count()
            );
            return $suggestion;
        });

        // เรียงจากสูงสุด
        return $suggestions->sortByDesc('frequency')->toArray();
    }

    /**
     * ดึง no-match messages
     *
     * @param int $days
     * @return Collection
     */
    private function getNoMatchMessages(int $days): Collection
    {
        return collect(
            DB::table('keyword_activity_logs')
                ->where('action_type', 'no_match')
                ->where('created_at', '>=', now()->subDays($days))
                ->select('user_message')
                ->get()
        )->pluck('user_message');
    }

    /**
     * วิเคราะห์ patterns จาก messages
     *
     * @param Collection $messages
     * @return Collection
     */
    private function analyzePatterns(Collection $messages): Collection
    {
        $patterns = collect();

        // Extract keywords from messages (words that appear multiple times)
        $wordFrequency = $this->extractKeywords($messages);

        // สร้าง suggestions จาก top words
        foreach ($wordFrequency as $word => $frequency) {
            if ($frequency >= 3 && strlen($word) > 3) { // Ignore short words
                $patterns->push([
                    'keyword' => $this->generateKeywordName($word),
                    'trigger_words' => [$word],
                    'frequency' => $frequency,
                    'sample_messages' => $this->getSampleMessages($messages, $word),
                ]);
            }
        }

        // Extract phrase patterns (2-3 word combinations)
        $phrasePatterns = $this->extractPhrases($messages);
        foreach ($phrasePatterns as $phrase => $frequency) {
            if ($frequency >= 2) {
                $patterns->push([
                    'keyword' => $this->generateKeywordName($phrase),
                    'trigger_words' => [$phrase],
                    'frequency' => $frequency,
                    'sample_messages' => $this->getSampleMessages($messages, $phrase),
                ]);
            }
        }

        return $patterns;
    }

    /**
     * Extract keywords from messages
     *
     * @param Collection $messages
     * @return array
     */
    private function extractKeywords(Collection $messages): array
    {
        $wordFrequency = [];

        // Common words to ignore (stopwords)
        $stopwords = ['the', 'a', 'an', 'is', 'are', 'or', 'and', 'i', 'me', 'you', 'it',
                      'this', 'that', 'in', 'on', 'at', 'to', 'from', 'of', 'for', 'with',
                      'โปรดช่วยฉัน', 'คำถาม', 'เรื่อง', 'อยากรู้', 'ได้ไหม', 'ไหม'];

        foreach ($messages as $message) {
            // Extract words
            $words = $this->extractWords($message);

            foreach ($words as $word) {
                if (!in_array(strtolower($word), $stopwords) && strlen($word) > 3) {
                    $wordFrequency[strtolower($word)] = ($wordFrequency[strtolower($word)] ?? 0) + 1;
                }
            }
        }

        // Sort by frequency
        arsort($wordFrequency);
        return array_slice($wordFrequency, 0, 20, true); // Top 20
    }

    /**
     * Extract phrases (2-3 words) from messages
     *
     * @param Collection $messages
     * @return array
     */
    private function extractPhrases(Collection $messages): array
    {
        $phraseFrequency = [];

        foreach ($messages as $message) {
            $words = $this->extractWords($message);

            // 2-word phrases
            for ($i = 0; $i < count($words) - 1; $i++) {
                $phrase = strtolower($words[$i] . ' ' . $words[$i + 1]);
                if (strlen($phrase) > 5) {
                    $phraseFrequency[$phrase] = ($phraseFrequency[$phrase] ?? 0) + 1;
                }
            }

            // 3-word phrases
            for ($i = 0; $i < count($words) - 2; $i++) {
                $phrase = strtolower($words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2]);
                if (strlen($phrase) > 8) {
                    $phraseFrequency[$phrase] = ($phraseFrequency[$phrase] ?? 0) + 1;
                }
            }
        }

        arsort($phraseFrequency);
        return array_slice($phraseFrequency, 0, 10, true); // Top 10
    }

    /**
     * Extract words from message
     *
     * @param string $message
     * @return array
     */
    private function extractWords(string $message): array
    {
        // Remove special characters, keep letters, numbers, spaces
        $cleaned = preg_replace('/[^a-zA-Z0-9\s์-๙]/u', '', $message);
        return array_filter(explode(' ', trim($cleaned)));
    }

    /**
     * Generate keyword name from word/phrase
     *
     * @param string $word
     * @return string
     */
    private function generateKeywordName(string $word): string
    {
        // Convert to snake_case
        $name = strtolower(trim($word));
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/[^a-z0-9_]/', '', $name);

        // Limit to 30 chars
        return substr($name, 0, 30);
    }

    /**
     * Get sample messages for a word
     *
     * @param Collection $messages
     * @param string $word
     * @param int $limit
     * @return array
     */
    private function getSampleMessages(Collection $messages, string $word, int $limit = 3): array
    {
        return $messages->filter(function ($message) use ($word) {
            return stripos($message, $word) !== false;
        })->take($limit)->toArray();
    }

    /**
     * Calculate confidence score (0-100)
     *
     * @param int $frequency
     * @param int $total
     * @return float
     */
    private function calculateConfidence(int $frequency, int $total): float
    {
        // Confidence based on percentage of total
        $percentage = ($frequency / $total) * 100;

        // Cap at 100
        return min($percentage, 100);
    }

    /**
     * Get unique suggestions (avoid duplicates with existing keywords)
     *
     * @return array
     */
    public function getUniqueNewSuggestions(int $days = 30, int $minFrequency = 3): array
    {
        $suggestions = $this->getSuggestions($days, $minFrequency);

        // Get existing keywords
        $existingKeywords = LineBotKeyword::pluck('keyword')->toArray();

        // Filter out existing
        return array_filter($suggestions, function ($suggestion) use ($existingKeywords) {
            return !in_array($suggestion['keyword'], $existingKeywords);
        });
    }

    /**
     * Create keyword draft from suggestion
     *
     * @param array $suggestion
     * @return LineBotKeyword
     */
    public function createKeywordDraft(array $suggestion): LineBotKeyword
    {
        $keyword = new LineBotKeyword();
        $keyword->keyword = $suggestion['keyword'];
        $keyword->trigger_words = json_encode($suggestion['trigger_words']);
        $keyword->response_type = 'text';
        $keyword->response_text = '💬 ' . ucfirst(str_replace('_', ' ', $suggestion['keyword'])) .
                                 "\n\n" .
                                 "ขออภัยค่ะ ยังไม่มีข้อมูลเกี่ยวกับหัวข้อนี้\n" .
                                 "กรุณาติดต่อเจ้าหน้าที่ค่ะ";
        $keyword->category = 'custom';
        $keyword->priority = 50;
        $keyword->is_active = false; // Inactive by default - for review
        $keyword->notes = 'Auto-suggested from AI analysis. Frequency: ' .
                         $suggestion['frequency'] .
                         ', Confidence: ' .
                         round($suggestion['confidence'], 1) . '%';

        return $keyword;
    }

    /**
     * Approve and save suggestion as keyword
     *
     * @param array $suggestion
     * @param array $updates Optional updates (e.g., response_text)
     * @return LineBotKeyword
     */
    public function approveSuggestion(array $suggestion, array $updates = []): LineBotKeyword
    {
        $keyword = $this->createKeywordDraft($suggestion);

        // Apply updates
        foreach ($updates as $key => $value) {
            if (property_exists($keyword, $key)) {
                $keyword->$key = $value;
            }
        }

        // Activate
        $keyword->is_active = true;

        // Save
        $keyword->save();

        return $keyword;
    }

    /**
     * Get suggestion statistics
     *
     * @return array
     */
    public function getStatistics(int $days = 30): array
    {
        $noMatches = $this->getNoMatchMessages($days);
        $suggestions = $this->getUniqueNewSuggestions($days);
        $existingKeywords = LineBotKeyword::count();

        return [
            'total_no_matches' => $noMatches->count(),
            'unique_no_matches' => $noMatches->unique()->count(),
            'suggestions_count' => count($suggestions),
            'existing_keywords' => $existingKeywords,
            'potential_coverage_increase' => round((count($suggestions) / ($existingKeywords + count($suggestions))) * 100, 2),
            'analysis_period_days' => $days,
        ];
    }

    /**
     * Get suggestion recommendations
     *
     * @return array
     */
    public function getRecommendations(): array
    {
        $stats = $this->getStatistics();
        $suggestions = $this->getUniqueNewSuggestions();

        $recommendations = [];

        // If many no-matches
        if ($stats['total_no_matches'] > 50) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => '⚠️ มี no-match จำนวนมาก (' . $stats['total_no_matches'] . ' ครั้ง)',
                'action' => 'ควรสร้าง keywords ใหม่เพื่อครอบคลุมคำถามเหล่านี้',
            ];
        }

        // If high potential coverage increase
        if ($stats['potential_coverage_increase'] > 20) {
            $recommendations[] = [
                'type' => 'opportunity',
                'message' => '📈 มีโอกาสเพิ่มการครอบคลุม ' . $stats['potential_coverage_increase'] . '%',
                'action' => 'อนุมัติ suggestions เพื่อปรับปรุงประสิทธิภาพ',
            ];
        }

        // If suggestions exist
        if (count($suggestions) > 0) {
            $recommendations[] = [
                'type' => 'suggestion',
                'message' => '💡 มี ' . count($suggestions) . ' keyword suggestions พร้อมสำหรับการอนุมัติ',
                'action' => 'ตรวจสอบและอนุมัติ suggestions',
            ];
        }

        return $recommendations;
    }
}
