<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpamDetectionService
{
    /**
     * Common spam keywords in Thai and English
     *
     * @var array
     */
    private $spamKeywords = [
        // Thai spam keywords
        'คาสิโน', 'พนัน', 'บาคาร่า', 'สล็อต', 'เว็บพนัน', 'แทงบอล',
        'หวย', 'ล็อตเตอรี่', 'ยาลดความอ้วน', 'ยาผู้ชาย', 'ยาเพิ่มขนาด',
        'กู้เงิน', 'สินเชื่อ', 'เงินกู้', 'บัตรเครดิต', 'ฟรี', 'แจกเงิน',

        // English spam keywords
        'casino', 'viagra', 'cialis', 'poker', 'lottery', 'bitcoin',
        'cryptocurrency', 'forex', 'binary options', 'get rich quick',
        'work from home', 'earn money fast', 'click here', 'buy now',
        'limited offer', 'act now', 'congratulations', 'you won',
        'nigerian prince', 'inheritance', 'wire transfer',
    ];

    /**
     * Suspicious URL patterns
     *
     * @var array
     */
    private $suspiciousPatterns = [
        '/bit\.ly/i',
        '/tinyurl/i',
        '/goo\.gl/i',
        '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i', // IP addresses
        '/<script/i',
        '/javascript:/i',
        '/onclick=/i',
        '/onerror=/i',
    ];

    /**
     * Check if content is spam
     *
     * @param string $content
     * @param string $type
     * @return bool
     */
    public function isSpam(string $content, string $type = 'general'): bool
    {
        // Check spam score
        $score = $this->calculateSpamScore($content);

        // Different thresholds for different content types
        $threshold = $this->getThreshold($type);

        if ($score >= $threshold) {
            Log::info('Spam detected', [
                'type' => $type,
                'score' => $score,
                'threshold' => $threshold,
                'content' => substr($content, 0, 100)
            ]);
            return true;
        }

        return false;
    }

    /**
     * Calculate spam score for content
     *
     * @param string $content
     * @return int
     */
    private function calculateSpamScore(string $content): int
    {
        $score = 0;

        // Check for spam keywords
        foreach ($this->spamKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $score += 10;
            }
        }

        // Check for suspicious patterns
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $score += 15;
            }
        }

        // Check for excessive URLs
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/i', $content);
        if ($urlCount > 3) {
            $score += ($urlCount - 3) * 5;
        }

        // Check for excessive capital letters
        $capitalRatio = $this->calculateCapitalRatio($content);
        if ($capitalRatio > 0.5) {
            $score += 10;
        }

        // Check for excessive special characters
        $specialCharRatio = $this->calculateSpecialCharRatio($content);
        if ($specialCharRatio > 0.3) {
            $score += 10;
        }

        // Check for excessive repetition
        if ($this->hasExcessiveRepetition($content)) {
            $score += 15;
        }

        // Check content length (very short or very long can be spam)
        $length = mb_strlen($content);
        if ($length < 10 || $length > 5000) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Get spam threshold for content type
     *
     * @param string $type
     * @return int
     */
    private function getThreshold(string $type): int
    {
        return match($type) {
            'comment', 'review' => 30,
            'order' => 40,
            'contact' => 25,
            'registration' => 35,
            default => 30,
        };
    }

    /**
     * Calculate ratio of capital letters
     *
     * @param string $content
     * @return float
     */
    private function calculateCapitalRatio(string $content): float
    {
        $letters = preg_replace('/[^a-zA-Z]/', '', $content);
        if (empty($letters)) {
            return 0;
        }

        $capitals = preg_replace('/[^A-Z]/', '', $content);
        return strlen($capitals) / strlen($letters);
    }

    /**
     * Calculate ratio of special characters
     *
     * @param string $content
     * @return float
     */
    private function calculateSpecialCharRatio(string $content): float
    {
        if (empty($content)) {
            return 0;
        }

        $specialChars = preg_replace('/[a-zA-Z0-9\s\p{Thai}]/u', '', $content);
        return mb_strlen($specialChars) / mb_strlen($content);
    }

    /**
     * Check for excessive repetition
     *
     * @param string $content
     * @return bool
     */
    private function hasExcessiveRepetition(string $content): bool
    {
        // Check for same word repeated many times
        if (preg_match('/(\b\w+\b)(?:\s+\1){4,}/i', $content)) {
            return true;
        }

        // Check for same character repeated many times
        if (preg_match('/(.)\1{10,}/', $content)) {
            return true;
        }

        return false;
    }

    /**
     * Add custom spam keyword
     *
     * @param string $keyword
     * @return void
     */
    public function addSpamKeyword(string $keyword): void
    {
        if (!in_array($keyword, $this->spamKeywords)) {
            $this->spamKeywords[] = $keyword;
            $this->cacheSpamKeywords();
        }
    }

    /**
     * Remove spam keyword
     *
     * @param string $keyword
     * @return void
     */
    public function removeSpamKeyword(string $keyword): void
    {
        $key = array_search($keyword, $this->spamKeywords);
        if ($key !== false) {
            unset($this->spamKeywords[$key]);
            $this->cacheSpamKeywords();
        }
    }

    /**
     * Get all spam keywords
     *
     * @return array
     */
    public function getSpamKeywords(): array
    {
        return Cache::remember('spam_keywords', 3600, function () {
            return $this->spamKeywords;
        });
    }

    /**
     * Cache spam keywords
     *
     * @return void
     */
    private function cacheSpamKeywords(): void
    {
        Cache::put('spam_keywords', $this->spamKeywords, 3600);
    }
}
