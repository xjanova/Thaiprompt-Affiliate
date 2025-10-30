<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LanguageSetting;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LanguageSettingController extends Controller
{
    /**
     * Display language settings page
     */
    public function index()
    {
        $languages = LanguageSetting::getAll();

        $switcherSettings = [
            'style' => Setting::get('language_switcher_style') ?? 'dropdown',
            'show_flags' => filter_var(
                Setting::get('language_switcher_show_flags') ?? 'true',
                FILTER_VALIDATE_BOOLEAN
            ),
            'flag_size' => (int) (Setting::get('language_switcher_flag_size') ?? 24),
            'show_name' => filter_var(
                Setting::get('language_switcher_show_name') ?? 'true',
                FILTER_VALIDATE_BOOLEAN
            ),
            'position' => Setting::get('language_switcher_position') ?? 'top-right',
        ];

        return view('admin.settings.languages', compact('languages', 'switcherSettings'));
    }

    /**
     * Update language settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'languages' => 'required|array',
            'languages.*.code' => 'required|string|max:10',
            'languages.*.is_enabled' => 'boolean',
            'languages.*.sort_order' => 'integer',
            'switcher_style' => 'required|in:dropdown,flags,compact',
            'show_flags' => 'boolean',
            'flag_size' => 'required|integer|min:16|max:64',
            'show_name' => 'boolean',
            'position' => 'required|in:top-left,top-right,bottom-left,bottom-right',
        ]);

        try {
            // Update languages
            foreach ($validated['languages'] as $langData) {
                $language = LanguageSetting::where('code', $langData['code'])->first();
                if ($language) {
                    $language->update([
                        'is_enabled' => $langData['is_enabled'] ?? false,
                        'sort_order' => $langData['sort_order'] ?? 0,
                    ]);
                }
            }

            // Update switcher settings
            Setting::set('language_switcher_style', $validated['switcher_style'], 'string', 'language');
            Setting::set('language_switcher_show_flags', $validated['show_flags'] ? 'true' : 'false', 'boolean', 'language');
            Setting::set('language_switcher_flag_size', $validated['flag_size'], 'integer', 'language');
            Setting::set('language_switcher_show_name', $validated['show_name'] ? 'true' : 'false', 'boolean', 'language');
            Setting::set('language_switcher_position', $validated['position'], 'string', 'language');

            // Clear caches
            Cache::forget('language_settings_enabled');
            Cache::forget('language_settings_all');

            Log::info('Language settings updated', [
                'user' => auth()->id(),
                'enabled_count' => collect($validated['languages'])->where('is_enabled', true)->count(),
            ]);

            return redirect()
                ->route('admin.settings.languages')
                ->with('success', 'Language settings updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update language settings', [
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to update language settings: ' . $e->getMessage());
        }
    }

    /**
     * Update single language
     */
    public function updateLanguage(Request $request, $code)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'native_name' => 'sometimes|string|max:100',
            'flag_emoji' => 'nullable|string|max:10',
            'flag_image_url' => 'nullable|url|max:255',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ]);

        $language = LanguageSetting::where('code', $code)->firstOrFail();

        try {
            if (isset($validated['is_default']) && $validated['is_default']) {
                $language->setAsDefault();
            } else {
                $language->update($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Language updated successfully',
                'language' => $language->toApiArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update language', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update language: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle language enabled status
     */
    public function toggle(Request $request, $code)
    {
        $language = LanguageSetting::where('code', $code)->firstOrFail();

        try {
            $language->is_enabled = !$language->is_enabled;
            $language->save();

            return response()->json([
                'success' => true,
                'is_enabled' => $language->is_enabled,
                'message' => $language->is_enabled ? 'Language enabled' : 'Language disabled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle language: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder languages
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'string|exists:language_settings,code',
        ]);

        try {
            foreach ($validated['order'] as $index => $code) {
                LanguageSetting::where('code', $code)->update([
                    'sort_order' => $index + 1,
                ]);
            }

            Cache::forget('language_settings_enabled');
            Cache::forget('language_settings_all');

            return response()->json([
                'success' => true,
                'message' => 'Language order updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder languages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get switcher settings
     */
    public function getSwitcherSettings()
    {
        return response()->json([
            'success' => true,
            'settings' => [
                'style' => Setting::get('language_switcher_style') ?? 'dropdown',
                'show_flags' => filter_var(
                    Setting::get('language_switcher_show_flags') ?? 'true',
                    FILTER_VALIDATE_BOOLEAN
                ),
                'flag_size' => (int) (Setting::get('language_switcher_flag_size') ?? 24),
                'show_name' => filter_var(
                    Setting::get('language_switcher_show_name') ?? 'true',
                    FILTER_VALIDATE_BOOLEAN
                ),
                'position' => Setting::get('language_switcher_position') ?? 'top-right',
            ],
        ]);
    }

    /**
     * Update switcher settings only
     */
    public function updateSwitcherSettings(Request $request)
    {
        $validated = $request->validate([
            'style' => 'required|in:dropdown,flags,compact',
            'show_flags' => 'boolean',
            'flag_size' => 'required|integer|min:16|max:64',
            'show_name' => 'boolean',
            'position' => 'required|in:top-left,top-right,bottom-left,bottom-right',
        ]);

        try {
            Setting::set('language_switcher_style', $validated['style'], 'string', 'language');
            Setting::set('language_switcher_show_flags', $validated['show_flags'] ? 'true' : 'false', 'boolean', 'language');
            Setting::set('language_switcher_flag_size', $validated['flag_size'], 'integer', 'language');
            Setting::set('language_switcher_show_name', $validated['show_name'] ? 'true' : 'false', 'boolean', 'language');
            Setting::set('language_switcher_position', $validated['position'], 'string', 'language');

            return response()->json([
                'success' => true,
                'message' => 'Switcher settings updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update switcher settings: ' . $e->getMessage(),
            ], 500);
        }
    }
}
