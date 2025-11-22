<?php

namespace App\Services;

use App\Models\LanguageSetting;
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
        try {
            // Read from database settings first, fallback to config, then env
            $this->enabled = \App\Models\Setting::get('google_translate_enabled')
                ?? config('translate.google.enabled', false);

            // Get supported languages from database
            $this->supportedLanguages = LanguageSetting::getEnabledCodes();
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ใช้ค่าจาก config
            Log::debug('TranslationService: Cannot load from database, using config - ' . $e->getMessage());
            $this->enabled = config('translate.google.enabled', false);
            $this->supportedLanguages = [];
        }

        // Fallback to config if no languages in database
        if (empty($this->supportedLanguages)) {
            $this->supportedLanguages = array_keys(config('translate.supported_languages', []));
        }

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

        // Priority order: Database > Environment > Config
        try {
            $apiKey = \App\Models\Setting::get('google_translate_api_key')
                ?? env('GOOGLE_TRANSLATE_API_KEY')
                ?? config('translate.google.api_key');
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ใช้ค่าจาก env/config
            $apiKey = env('GOOGLE_TRANSLATE_API_KEY')
                ?? config('translate.google.api_key');
        }

        if ($apiKey) {
            $config['key'] = $apiKey;
            Log::info('Google Translate initialized with API key');
        }
        // Otherwise use service account credentials
        else {
            $credentialsPath = env('GOOGLE_TRANSLATE_CREDENTIALS')
                ?? config('translate.google.credentials_path');

            if ($credentialsPath && file_exists($credentialsPath)) {
                $config['keyFilePath'] = $credentialsPath;
                Log::info('Google Translate initialized with service account', [
                    'path' => $credentialsPath
                ]);
            } else {
                Log::warning('Google Translate credentials not found', [
                    'checked_path' => $credentialsPath
                ]);
            }
        }

        if (!empty($config)) {
            try {
                $this->client = new TranslateClient($config);
            } catch (\Exception $e) {
                Log::error('Failed to create TranslateClient', [
                    'error' => $e->getMessage(),
                    'config_keys' => array_keys($config)
                ]);
                throw $e;
            }
        } else {
            throw new \Exception('No valid Google Translate credentials configured');
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
        // Trim whitespace
        $text = trim($text);

        // Don't translate empty strings
        if (empty($text)) {
            return $text;
        }

        // Use source language from database setting, fallback to config
        if (!$sourceLang) {
            $sourceLang = \App\Models\Setting::get('translate_source_language')
                ?? config('translate.source_language', 'th');
        }

        // Don't translate if source and target are the same
        if ($sourceLang === $targetLang) {
            return $text;
        }

        // If translation is disabled or not configured, return original text
        if (!$this->enabled || !$this->client) {
            Log::debug('Translation disabled, returning original text');
            return $text;
        }

        // Validate target language
        if (!in_array($targetLang, $this->supportedLanguages)) {
            Log::warning('Unsupported target language', [
                'target' => $targetLang,
                'supported' => $this->supportedLanguages
            ]);
            return $text;
        }

        // PRIORITY 2: Check cache
        $cacheEnabled = \App\Models\Setting::get('translate_cache_enabled')
            ?? config('translate.cache.enabled', true);

        if ($cacheEnabled) {
            $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                Log::debug('Translation cache hit', ['key' => $cacheKey]);
                return $cached;
            }
        }

        // PRIORITY 3: Use Google Translate API
        try {
            $result = $this->client->translate($text, [
                'target' => $targetLang,
                'source' => $sourceLang,
            ]);

            $translatedText = $result['text'] ?? $text;

            // Cache the result
            if ($cacheEnabled) {
                $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
                $cacheTtl = config('translate.cache.ttl', 86400);
                Cache::put($cacheKey, $translatedText, $cacheTtl);
                Log::debug('Translation cached', ['key' => $cacheKey]);
            }

            Log::info('Translation successful', [
                'source' => $sourceLang,
                'target' => $targetLang,
                'length' => strlen($text)
            ]);

            return $translatedText;
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'error' => $e->getMessage(),
                'source' => $sourceLang,
                'target' => $targetLang,
                'text_length' => strlen($text)
            ]);
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
        $languages = LanguageSetting::getEnabled();

        if ($languages->isEmpty()) {
            // Fallback to config
            return config('translate.supported_languages', []);
        }

        // Get UI preferences
        $flagSize = (int) (\App\Models\Setting::get('language_switcher_flag_size') ?? 24);
        $showFlags = filter_var(
            \App\Models\Setting::get('language_switcher_show_flags') ?? 'true',
            FILTER_VALIDATE_BOOLEAN
        );
        $showName = filter_var(
            \App\Models\Setting::get('language_switcher_show_name') ?? 'true',
            FILTER_VALIDATE_BOOLEAN
        );

        return $languages->mapWithKeys(function ($lang) use ($flagSize, $showFlags, $showName) {
            return [
                $lang->code => $lang->toApiArray($flagSize, $showFlags, $showName)
            ];
        })->toArray();
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
