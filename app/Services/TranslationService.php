<?php

namespace App\Services;

use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected ?TranslateClient $client = null;
    protected bool $enabled;
    protected array $supportedLanguages;

    public function __construct()
    {
        $this->enabled = config('translate.google.enabled', false);
        $this->supportedLanguages = array_keys(config('translate.supported_languages', []));

        if ($this->enabled) {
            try {
                $this->initializeClient();
            } catch (\Exception $e) {
                Log::error('Google Translate initialization failed: ' . $e->getMessage());
                $this->enabled = false;
            }
        }
    }

    /**
     * Initialize Google Translate Client
     */
    protected function initializeClient(): void
    {
        $config = [];

        // Use API Key if provided
        if ($apiKey = config('translate.google.api_key')) {
            $config['key'] = $apiKey;
        }
        // Otherwise use service account credentials
        elseif ($credentialsPath = config('translate.google.credentials_path')) {
            if (file_exists($credentialsPath)) {
                $config['keyFilePath'] = $credentialsPath;
            }
        }

        if (!empty($config)) {
            $this->client = new TranslateClient($config);
        }
    }

    /**
     * Translate text to target language
     *
     * @param string $text Text to translate
     * @param string $targetLang Target language code
     * @param string|null $sourceLang Source language code (auto-detect if null)
     * @return string|null Translated text or null on failure
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        // If translation is disabled or not configured, return original text
        if (!$this->enabled || !$this->client) {
            return $text;
        }

        // Validate target language
        if (!in_array($targetLang, $this->supportedLanguages)) {
            return $text;
        }

        // Use source language from config if not provided
        if (!$sourceLang) {
            $sourceLang = config('translate.source_language', 'th');
        }

        // Don't translate if source and target are the same
        if ($sourceLang === $targetLang) {
            return $text;
        }

        // Check cache first
        if (config('translate.cache.enabled', true)) {
            $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $result = $this->client->translate($text, [
                'target' => $targetLang,
                'source' => $sourceLang,
            ]);

            $translatedText = $result['text'] ?? $text;

            // Cache the result
            if (config('translate.cache.enabled', true)) {
                $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
                $cacheTtl = config('translate.cache.ttl', 86400);
                Cache::put($cacheKey, $translatedText, $cacheTtl);
            }

            return $translatedText;
        } catch (\Exception $e) {
            Log::error('Translation failed: ' . $e->getMessage());
            return $text; // Return original text on error
        }
    }

    /**
     * Translate multiple texts
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLang Target language code
     * @param string|null $sourceLang Source language code
     * @return array Array of translated texts
     */
    public function translateBatch(array $texts, string $targetLang, ?string $sourceLang = null): array
    {
        $results = [];

        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLang, $sourceLang);
        }

        return $results;
    }

    /**
     * Get available languages
     *
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        return config('translate.supported_languages', []);
    }

    /**
     * Check if translation service is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->client !== null;
    }

    /**
     * Detect language of text
     *
     * @param string $text
     * @return string|null Language code or null
     */
    public function detectLanguage(string $text): ?string
    {
        if (!$this->enabled || !$this->client) {
            return null;
        }

        try {
            $result = $this->client->detectLanguage($text);
            return $result['languageCode'] ?? null;
        } catch (\Exception $e) {
            Log::error('Language detection failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate cache key for translation
     *
     * @param string $text
     * @param string $targetLang
     * @param string $sourceLang
     * @return string
     */
    protected function getCacheKey(string $text, string $targetLang, string $sourceLang): string
    {
        $prefix = config('translate.cache.prefix', 'translate:');
        $hash = md5($text);
        return "{$prefix}{$sourceLang}:{$targetLang}:{$hash}";
    }

    /**
     * Clear translation cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $prefix = config('translate.cache.prefix', 'translate:');
        Cache::flush(); // Or use more specific cache clearing if needed
    }
}
