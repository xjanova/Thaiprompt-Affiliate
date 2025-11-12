<?php

namespace App\Services;

use App\Models\Theme;
use App\Models\ThemePreset;
use App\Models\UserTheme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ThemeService
{
    /**
     * Get theme for user
     */
    public function getThemeForUser($userId)
    {
        // Try to get from cache first
        return Cache::remember("user_theme_{$userId}", 3600, function () use ($userId) {
            $userTheme = UserTheme::getActiveThemeForUser($userId);

            if ($userTheme && $userTheme->theme) {
                return [
                    'theme' => $userTheme->theme,
                    'mode' => $userTheme->mode,
                ];
            }

            // Fallback to default theme
            return [
                'theme' => Theme::getDefault(),
                'mode' => 'auto',
            ];
        });
    }

    /**
     * Set theme for user
     */
    public function setThemeForUser($userId, $themeId, $mode = 'auto')
    {
        $result = UserTheme::setThemeForUser($userId, $themeId, $mode);

        // Clear cache
        Cache::forget("user_theme_{$userId}");

        return $result;
    }

    /**
     * Get CSS for user
     */
    public function getCssForUser($userId, $mode = null)
    {
        $userTheme = $this->getThemeForUser($userId);

        if (!$userTheme['theme']) {
            return '';
        }

        // Determine mode
        if ($mode === null) {
            $mode = $userTheme['mode'] === 'auto' ? 'light' : $userTheme['mode'];
        }

        return $userTheme['theme']->generateCss($mode);
    }

    /**
     * Create theme from preset
     */
    public function createThemeFromPreset($presetId, $displayName = null, $isDefault = false, $userId = null)
    {
        $preset = ThemePreset::findOrFail($presetId);

        DB::beginTransaction();
        try {
            $theme = $preset->toTheme($displayName, $isDefault);

            if ($userId) {
                $theme->created_by = $userId;
                $theme->save();
            }

            DB::commit();
            return $theme;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create custom theme
     */
    public function createTheme(array $data)
    {
        DB::beginTransaction();
        try {
            $theme = Theme::create($data);

            // If set as default, update others
            if ($data['is_default'] ?? false) {
                Theme::where('id', '!=', $theme->id)
                    ->update(['is_default' => false]);
            }

            DB::commit();
            return $theme;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update theme
     */
    public function updateTheme($themeId, array $data)
    {
        DB::beginTransaction();
        try {
            $theme = Theme::findOrFail($themeId);

            // Prevent updating system themes
            if ($theme->is_system && !($data['is_system'] ?? false)) {
                throw new \Exception('Cannot modify system theme');
            }

            $theme->update($data);

            // If set as default, update others
            if ($data['is_default'] ?? false) {
                Theme::where('id', '!=', $theme->id)
                    ->update(['is_default' => false]);
            }

            // Clear all user theme caches
            $this->clearAllUserThemeCaches();

            DB::commit();
            return $theme;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete theme
     */
    public function deleteTheme($themeId)
    {
        DB::beginTransaction();
        try {
            $theme = Theme::findOrFail($themeId);

            // Prevent deleting system or default themes
            if ($theme->is_system) {
                throw new \Exception('Cannot delete system theme');
            }

            if ($theme->is_default) {
                throw new \Exception('Cannot delete default theme. Please set another theme as default first.');
            }

            // Move users to default theme
            $defaultTheme = Theme::getDefault();
            if ($defaultTheme) {
                UserTheme::where('theme_id', $themeId)
                    ->update(['theme_id' => $defaultTheme->id]);
            }

            // Delete preview images
            if ($theme->preview_light) {
                $path = str_replace('/storage/', '', $theme->preview_light);
                Storage::disk('public')->delete($path);
            }

            if ($theme->preview_dark) {
                $path = str_replace('/storage/', '', $theme->preview_dark);
                Storage::disk('public')->delete($path);
            }

            $theme->delete();

            // Clear all user theme caches
            $this->clearAllUserThemeCaches();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Duplicate theme
     */
    public function duplicateTheme($themeId, $newName, $newDisplayName)
    {
        $originalTheme = Theme::findOrFail($themeId);

        $newTheme = $originalTheme->replicate();
        $newTheme->name = $newName;
        $newTheme->display_name = $newDisplayName;
        $newTheme->is_default = false;
        $newTheme->is_system = false;
        $newTheme->save();

        return $newTheme;
    }

    /**
     * Upload preview image
     */
    public function uploadPreviewImage($theme, $file, $mode = 'light')
    {
        $webpService = app(WebPService::class);

        // Convert to WebP
        $result = $webpService->convertAndStore($file, 'theme-previews', 85);

        // Delete old preview if exists
        $oldPreview = $mode === 'light' ? $theme->preview_light : $theme->preview_dark;
        if ($oldPreview) {
            $path = str_replace('/storage/', '', $oldPreview);
            Storage::disk('public')->delete($path);
        }

        // Update theme
        if ($mode === 'light') {
            $theme->preview_light = $result['url'];
        } else {
            $theme->preview_dark = $result['url'];
        }

        $theme->save();

        return $result['url'];
    }

    /**
     * Get all themes
     */
    public function getAllThemes($activeOnly = true)
    {
        $query = Theme::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('is_system', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get theme statistics
     */
    public function getThemeStatistics($themeId)
    {
        $theme = Theme::findOrFail($themeId);

        return [
            'total_users' => $theme->users()->count(),
            'active_users' => $theme->users()->where('is_active', true)->count(),
            'mode_distribution' => [
                'light' => $theme->users()->where('mode', 'light')->count(),
                'dark' => $theme->users()->where('mode', 'dark')->count(),
                'auto' => $theme->users()->where('mode', 'auto')->count(),
            ],
        ];
    }

    /**
     * Initialize default themes
     */
    public function initializeDefaultThemes()
    {
        DB::beginTransaction();
        try {
            // Create default theme presets
            foreach (ThemePreset::defaultPresets() as $presetData) {
                ThemePreset::updateOrCreate(
                    ['name' => $presetData['name']],
                    $presetData
                );
            }

            // Create default system theme if not exists (Classic X)
            if (!Theme::where('is_system', true)->exists()) {
                $defaultPreset = ThemePreset::where('name', 'classic_x')->first();

                if ($defaultPreset) {
                    $theme = $defaultPreset->toTheme('Classic X (Default)', true);
                    $theme->is_system = true;
                    $theme->save();
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Export theme configuration
     */
    public function exportTheme($themeId)
    {
        $theme = Theme::findOrFail($themeId);

        return [
            'name' => $theme->name,
            'display_name' => $theme->display_name,
            'description' => $theme->description,
            'colors' => $theme->colors,
            'typography' => $theme->typography,
            'spacing' => $theme->spacing,
            'borders' => $theme->borders,
            'shadows' => $theme->shadows,
            'animations' => $theme->animations,
            'custom_css' => $theme->custom_css,
            'dark_colors' => $theme->dark_colors,
            'exported_at' => now()->toIso8601String(),
            'version' => config('version.current'),
        ];
    }

    /**
     * Import theme configuration
     */
    public function importTheme(array $config, $userId = null)
    {
        DB::beginTransaction();
        try {
            // Validate config
            if (!isset($config['name']) || !isset($config['display_name'])) {
                throw new \Exception('Invalid theme configuration');
            }

            // Create unique name if exists
            $baseName = $config['name'];
            $counter = 1;
            while (Theme::where('name', $config['name'])->exists()) {
                $config['name'] = $baseName . '_' . $counter;
                $counter++;
            }

            $theme = Theme::create([
                'name' => $config['name'],
                'display_name' => $config['display_name'],
                'description' => $config['description'] ?? null,
                'colors' => $config['colors'] ?? null,
                'typography' => $config['typography'] ?? null,
                'spacing' => $config['spacing'] ?? null,
                'borders' => $config['borders'] ?? null,
                'shadows' => $config['shadows'] ?? null,
                'animations' => $config['animations'] ?? null,
                'custom_css' => $config['custom_css'] ?? null,
                'dark_colors' => $config['dark_colors'] ?? null,
                'is_active' => true,
                'created_by' => $userId,
            ]);

            DB::commit();
            return $theme;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Clear all user theme caches
     */
    protected function clearAllUserThemeCaches()
    {
        // This is a simple implementation
        // In production, you might want to use Cache tags or Redis SCAN
        Cache::flush();
    }

    /**
     * Generate theme CSS file
     */
    public function generateThemeCssFile($themeId, $mode = 'light')
    {
        $theme = Theme::findOrFail($themeId);

        $css = $theme->generateCss($mode);

        // Save to public directory
        $filename = "theme-{$theme->name}-{$mode}.css";
        $path = "css/themes/{$filename}";

        Storage::disk('public')->put($path, $css);

        return "/storage/{$path}";
    }
}
