<?php

namespace App\Services;

use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * ThemeCompilerService
 *
 * Advanced service สำหรับ compile และ cache Arrow X Theme
 * พร้อม optimization และ minification
 */
class ThemeCompilerService
{
    protected ThemeService $themeService;
    protected RgbEffectService $rgbService;

    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'arrow_x_theme';

    /**
     * Cache TTL (seconds) - 1 ชั่วโมง
     */
    protected const CACHE_TTL = 3600;

    public function __construct()
    {
        $this->themeService = new ThemeService();
        $this->rgbService = new RgbEffectService();
    }

    /**
     * Compile theme พร้อม caching
     *
     * @param ThemeSetting|null $themeSetting
     * @param bool $forceRefresh บังคับ refresh cache
     * @return array ['css' => string, 'js' => string]
     */
    public function compile(?ThemeSetting $themeSetting = null, bool $forceRefresh = false): array
    {
        $themeSetting = $themeSetting ?? ThemeSetting::active();

        if (!$themeSetting) {
            return ['css' => '', 'js' => ''];
        }

        // Cache key ตาม theme ID และ updated_at
        $cacheKey = $this->getCacheKey($themeSetting);

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Compile CSS และ JS
        $compiled = [
            'css' => $this->compileCss($themeSetting),
            'js' => $this->compileJs($themeSetting),
        ];

        // บันทึกลง cache
        Cache::put($cacheKey, $compiled, self::CACHE_TTL);

        // Log
        Log::info("Arrow X Theme compiled and cached", [
            'theme_id' => $themeSetting->id,
            'cache_key' => $cacheKey,
        ]);

        return $compiled;
    }

    /**
     * Compile CSS
     *
     * @param ThemeSetting $themeSetting
     * @return string
     */
    protected function compileCss(ThemeSetting $themeSetting): string
    {
        $css = "/**\n";
        $css .= " * Arrow X Theme System - Compiled CSS\n";
        $css .= " * Theme: {$themeSetting->theme_name}\n";
        $css .= " * Generated: " . now()->toDateTimeString() . "\n";
        $css .= " * Cache Key: " . $this->getCacheKey($themeSetting) . "\n";
        $css .= " */\n\n";

        // CSS Variables
        $css .= $this->themeService->generateCssVariables($themeSetting) . "\n\n";

        // Dark Mode Variables
        $css .= $this->themeService->generateDarkModeCssVariables($themeSetting) . "\n\n";

        // RGB Effects
        $css .= $this->rgbService->generateAllEffectsCss($themeSetting) . "\n";

        // Minify CSS (optional)
        if (config('app.env') === 'production') {
            $css = $this->minifyCss($css);
        }

        return $css;
    }

    /**
     * Compile JavaScript
     *
     * @param ThemeSetting $themeSetting
     * @return string
     */
    protected function compileJs(ThemeSetting $themeSetting): string
    {
        $js = "/**\n";
        $js .= " * Arrow X Theme System - Compiled JavaScript\n";
        $js .= " * Theme: {$themeSetting->theme_name}\n";
        $js .= " * Generated: " . now()->toDateTimeString() . "\n";
        $js .= " */\n\n";

        // RGB Effects JavaScript
        $js .= $this->rgbService->generateAllEffectsJs($themeSetting) . "\n";

        // Theme utilities
        $js .= $this->generateThemeUtilities($themeSetting);

        // Minify JS (optional)
        if (config('app.env') === 'production') {
            $js = $this->minifyJs($js);
        }

        return $js;
    }

    /**
     * สร้าง JavaScript utilities สำหรับ theme
     *
     * @param ThemeSetting $themeSetting
     * @return string
     */
    protected function generateThemeUtilities(ThemeSetting $themeSetting): string
    {
        $brandName = $themeSetting->brand_name ?? 'Arrow X';

        return <<<JS

// Arrow X Theme Utilities
window.ArrowXTheme = {
    name: '{$themeSetting->theme_name}',
    brand: '{$brandName}',
    version: '{$themeSetting->version ?? '1.0.0'}',

    // Toggle dark mode
    toggleDarkMode() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
    },

    // Get current theme mode
    isDarkMode() {
        return document.documentElement.classList.contains('dark');
    },

    // Apply saved dark mode preference
    applySavedDarkMode() {
        const darkMode = localStorage.getItem('darkMode');
        if (darkMode === 'true') {
            document.documentElement.classList.add('dark');
        }
    }
};

// Auto-apply saved dark mode on load
document.addEventListener('DOMContentLoaded', () => {
    window.ArrowXTheme.applySavedDarkMode();
});

JS;
    }

    /**
     * Minify CSS (basic)
     *
     * @param string $css
     * @return string
     */
    protected function minifyCss(string $css): string
    {
        // ลบ comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // ลบ whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

        return trim($css);
    }

    /**
     * Minify JavaScript (basic)
     *
     * @param string $js
     * @return string
     */
    protected function minifyJs(string $js): string
    {
        // ลบ single-line comments (เว้น URLs)
        $js = preg_replace('~//[^\n]*~', '', $js);

        // ลบ multi-line comments
        $js = preg_replace('~/\*.*?\*/~s', '', $js);

        // ลบ whitespace
        $js = preg_replace('/\s+/', ' ', $js);

        return trim($js);
    }

    /**
     * สร้าง cache key
     *
     * @param ThemeSetting $themeSetting
     * @return string
     */
    protected function getCacheKey(ThemeSetting $themeSetting): string
    {
        $timestamp = $themeSetting->updated_at?->timestamp ?? time();
        return self::CACHE_PREFIX . "_{$themeSetting->id}_{$timestamp}";
    }

    /**
     * Clear theme cache
     *
     * @param ThemeSetting|null $themeSetting
     * @return bool
     */
    public function clearCache(?ThemeSetting $themeSetting = null): bool
    {
        if ($themeSetting) {
            $cacheKey = $this->getCacheKey($themeSetting);
            return Cache::forget($cacheKey);
        }

        // Clear ทุก theme cache
        $cleared = Cache::flush();

        Log::info("Arrow X Theme cache cleared");

        return $cleared;
    }

    /**
     * บันทึก compiled theme ลง file
     *
     * @param ThemeSetting|null $themeSetting
     * @return array ['css_path' => string, 'js_path' => string]
     */
    public function compileToFile(?ThemeSetting $themeSetting = null): array
    {
        $themeSetting = $themeSetting ?? ThemeSetting::active();

        if (!$themeSetting) {
            throw new \Exception('No active theme found');
        }

        // Compile
        $compiled = $this->compile($themeSetting, true);

        // สร้าง directory ถ้ายังไม่มี
        $publicPath = public_path('themes/arrow-x');
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0755, true);
        }

        // File paths
        $cssPath = "{$publicPath}/theme-{$themeSetting->id}.css";
        $jsPath = "{$publicPath}/theme-{$themeSetting->id}.js";

        // บันทึก files
        File::put($cssPath, $compiled['css']);
        File::put($jsPath, $compiled['js']);

        Log::info("Arrow X Theme compiled to files", [
            'theme_id' => $themeSetting->id,
            'css_path' => $cssPath,
            'js_path' => $jsPath,
        ]);

        return [
            'css_path' => $cssPath,
            'js_path' => $jsPath,
        ];
    }

    /**
     * ดึง compiled theme จาก cache หรือ compile ใหม่
     *
     * @param ThemeSetting|null $themeSetting
     * @return array
     */
    public function getCompiled(?ThemeSetting $themeSetting = null): array
    {
        return $this->compile($themeSetting, false);
    }

    /**
     * Warm up cache - compile ทุก theme ล่วงหน้า
     *
     * @return int จำนวน theme ที่ compile
     */
    public function warmUpCache(): int
    {
        $themes = ThemeSetting::all();
        $count = 0;

        foreach ($themes as $theme) {
            $this->compile($theme, true);
            $count++;
        }

        Log::info("Arrow X Theme cache warmed up", ['count' => $count]);

        return $count;
    }
}
