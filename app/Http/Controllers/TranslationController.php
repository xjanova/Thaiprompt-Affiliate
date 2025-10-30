<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TranslationController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Translate text via AJAX
     */
    public function translate(Request $request): JsonResponse
    {
        $availableLanguages = \App\Models\LanguageSetting::getEnabledCodes();

        $request->validate([
            'text' => 'required|string|max:5000',
            'target_lang' => 'required|string|in:' . implode(',', $availableLanguages),
            'source_lang' => 'nullable|string|in:' . implode(',', $availableLanguages),
        ]);

        $text = trim($request->input('text'));
        $targetLang = $request->input('target_lang');
        $sourceLang = $request->input('source_lang');

        // Don't translate empty strings
        if (empty($text)) {
            return response()->json([
                'success' => true,
                'original' => '',
                'translated' => '',
                'target_lang' => $targetLang,
            ]);
        }

        if (!$this->translationService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Translation service is not enabled',
                'original' => $text,
            ], 503);
        }

        $translated = $this->translationService->translate($text, $targetLang, $sourceLang);

        return response()->json([
            'success' => true,
            'original' => $text,
            'translated' => $translated,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
        ]);
    }

    /**
     * Batch translate multiple texts
     */
    public function translateBatch(Request $request): JsonResponse
    {
        $availableLanguages = \App\Models\LanguageSetting::getEnabledCodes();

        $request->validate([
            'texts' => 'required|array|max:50',
            'texts.*' => 'string|max:5000',
            'target_lang' => 'required|string|in:' . implode(',', $availableLanguages),
            'source_lang' => 'nullable|string|in:' . implode(',', $availableLanguages),
        ]);

        $texts = $request->input('texts');
        $targetLang = $request->input('target_lang');
        $sourceLang = $request->input('source_lang');

        if (!$this->translationService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Translation service is not enabled',
            ], 503);
        }

        $translated = $this->translationService->translateBatch($texts, $targetLang, $sourceLang);

        return response()->json([
            'success' => true,
            'translations' => $translated,
            'target_lang' => $targetLang,
            'count' => count($translated),
        ]);
    }

    /**
     * Get available languages
     */
    public function languages(): JsonResponse
    {
        $languages = $this->translationService->getAvailableLanguages();

        return response()->json([
            'success' => true,
            'languages' => $languages,
        ]);
    }

    /**
     * Detect language of text
     */
    public function detect(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        $text = $request->input('text');

        if (!$this->translationService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Translation service is not enabled',
            ], 503);
        }

        $detectedLang = $this->translationService->detectLanguage($text);

        return response()->json([
            'success' => true,
            'detected_language' => $detectedLang,
            'text' => $text,
        ]);
    }

    /**
     * Check if translation service is enabled
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'enabled' => $this->translationService->isEnabled(),
            'languages' => $this->translationService->getAvailableLanguages(),
        ]);
    }
}
