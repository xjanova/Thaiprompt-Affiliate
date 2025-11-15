<?php

namespace App\Services;

use App\Models\ThemeRgbEffect;
use App\Models\ThemeSetting;

/**
 * RgbEffectService
 *
 * สร้าง CSS และ JavaScript สำหรับ RGB Effects
 */
class RgbEffectService
{
    /**
     * สร้าง CSS สำหรับ RGB Effects ทั้งหมด
     *
     * @param ThemeSetting|null $themeSetting
     * @return string
     */
    public function generateAllEffectsCss(?ThemeSetting $themeSetting = null): string
    {
        $themeSetting = $themeSetting ?? ThemeSetting::active();

        if (!$themeSetting) {
            return '';
        }

        $rgbEffects = $themeSetting->rgbEffects()->where('is_enabled', true)->get();

        if ($rgbEffects->isEmpty()) {
            return '';
        }

        $css = "/* Arrow X RGB Effects - Generated CSS */\n\n";

        foreach ($rgbEffects as $effect) {
            $css .= $this->generateEffectCss($effect);
            $css .= "\n\n";
        }

        return $css;
    }

    /**
     * สร้าง CSS สำหรับ Effect เดียว
     *
     * @param ThemeRgbEffect $effect
     * @return string
     */
    public function generateEffectCss(ThemeRgbEffect $effect): string
    {
        $selector = $this->getCssSelector($effect);
        $animationName = $this->getAnimationName($effect);
        $keyframes = $this->generateKeyframes($effect);

        $css = "/* {$effect->effect_name} */\n";
        $css .= $keyframes . "\n";

        // Generate CSS rule based on trigger state
        $triggerSelector = $this->getTriggerSelector($selector, $effect->trigger_state);

        $css .= "{$triggerSelector} {\n";
        $css .= "    animation: {$animationName} {$effect->animation_duration}ms {$effect->animation_timing} {$effect->delay}ms {$effect->iteration_count};\n";

        // Add z-index if specified
        if ($effect->z_index !== 0) {
            $css .= "    z-index: {$effect->z_index};\n";
        }

        // Add position relative for z-index to work
        if ($effect->z_index !== 0) {
            $css .= "    position: relative;\n";
        }

        $css .= "}";

        return $css;
    }

    /**
     * สร้าง CSS @keyframes
     *
     * @param ThemeRgbEffect $effect
     * @return string
     */
    protected function generateKeyframes(ThemeRgbEffect $effect): string
    {
        $animationName = $this->getAnimationName($effect);
        $colors = $effect->rgb_colors;
        $intensity = $effect->getIntensityValue();

        $keyframes = "@keyframes {$animationName} {\n";

        switch ($effect->animation_type) {
            case 'rainbow':
                $keyframes .= $this->generateRainbowKeyframes($colors);
                break;

            case 'wave':
                $keyframes .= $this->generateWaveKeyframes($colors, $intensity);
                break;

            case 'pulse':
                $keyframes .= $this->generatePulseKeyframes($colors[0] ?? '#FF0000', $effect->blur_radius, $intensity);
                break;

            case 'glow':
                $keyframes .= $this->generateGlowKeyframes($colors[0] ?? '#FF0000', $effect->blur_radius, $intensity);
                break;

            case 'breathing':
                $keyframes .= $this->generateBreathingKeyframes($colors[0] ?? '#FF0000', $intensity);
                break;

            case 'slide':
                $keyframes .= $this->generateSlideKeyframes($colors);
                break;

            case 'rotate':
                $keyframes .= $this->generateRotateKeyframes($colors);
                break;

            case 'flash':
                $keyframes .= $this->generateFlashKeyframes($colors[0] ?? '#FF0000', $intensity);
                break;

            case 'static':
                $keyframes .= $this->generateStaticKeyframes($colors[0] ?? '#FF0000', $effect->blur_radius);
                break;

            default:
                $keyframes .= $this->generateRainbowKeyframes($colors);
        }

        $keyframes .= "}";

        return $keyframes;
    }

    /**
     * Rainbow animation keyframes
     */
    protected function generateRainbowKeyframes(array $colors): string
    {
        if (empty($colors)) {
            return "    0%, 100% { background: transparent; }\n";
        }

        $step = 100 / count($colors);
        $keyframes = '';

        foreach ($colors as $index => $color) {
            $percentage = round($index * $step);
            $keyframes .= "    {$percentage}% { background: {$color}; border-color: {$color}; }\n";
        }

        return $keyframes;
    }

    /**
     * Wave animation keyframes
     */
    protected function generateWaveKeyframes(array $colors, float $intensity): string
    {
        $color = $colors[0] ?? '#FF0000';
        $translateY = 10 * $intensity;

        return "    0%, 100% { transform: translateY(0); box-shadow: 0 0 20px {$color}; }\n" .
               "    50% { transform: translateY(-{$translateY}px); box-shadow: 0 10px 30px {$color}; }\n";
    }

    /**
     * Pulse animation keyframes
     */
    protected function generatePulseKeyframes(string $color, int $blurRadius, float $intensity): string
    {
        $maxBlur = $blurRadius * (1 + $intensity);

        return "    0%, 100% { box-shadow: 0 0 {$blurRadius}px {$color}; }\n" .
               "    50% { box-shadow: 0 0 {$maxBlur}px {$color}; }\n";
    }

    /**
     * Glow animation keyframes
     */
    protected function generateGlowKeyframes(string $color, int $blurRadius, float $intensity): string
    {
        $maxBlur = $blurRadius * 3 * $intensity;

        return "    0%, 100% { box-shadow: 0 0 {$blurRadius}px {$color}; filter: brightness(1); }\n" .
               "    50% { box-shadow: 0 0 {$maxBlur}px {$color}; filter: brightness(1.2); }\n";
    }

    /**
     * Breathing animation keyframes
     */
    protected function generateBreathingKeyframes(string $color, float $intensity): string
    {
        $minOpacity = 1 - (0.5 * $intensity);

        return "    0%, 100% { opacity: 1; }\n" .
               "    50% { opacity: {$minOpacity}; }\n";
    }

    /**
     * Slide animation keyframes
     */
    protected function generateSlideKeyframes(array $colors): string
    {
        $gradient = implode(', ', $colors);

        return "    0% { background-position: 0% 50%; }\n" .
               "    50% { background-position: 100% 50%; }\n" .
               "    100% { background-position: 0% 50%; }\n";
    }

    /**
     * Rotate animation keyframes
     */
    protected function generateRotateKeyframes(array $colors): string
    {
        return "    0% { transform: rotate(0deg); }\n" .
               "    100% { transform: rotate(360deg); }\n";
    }

    /**
     * Flash animation keyframes
     */
    protected function generateFlashKeyframes(string $color, float $intensity): string
    {
        $brightness = 1 + (0.5 * $intensity);

        return "    0%, 100% { filter: brightness(1); }\n" .
               "    50% { filter: brightness({$brightness}); background: {$color}22; }\n";
    }

    /**
     * Static glow keyframes
     */
    protected function generateStaticKeyframes(string $color, int $blurRadius): string
    {
        return "    0%, 100% { box-shadow: 0 0 {$blurRadius}px {$color}; }\n";
    }

    /**
     * ดึง CSS Selector จาก effect
     */
    protected function getCssSelector(ThemeRgbEffect $effect): string
    {
        if ($effect->target_element === 'custom' && $effect->custom_selector) {
            return $effect->custom_selector;
        }

        $selectorMap = [
            'sidebar' => '.sidebar, aside, [data-sidebar]',
            'navbar' => '.navbar, nav, [data-navbar]',
            'menu-items' => '.menu-item, .nav-link',
            'menu-items-active' => '.menu-item.active, .nav-link.active, .menu-item[aria-current], .nav-link[aria-current]',
            'cards' => '.card, [data-card]',
            'buttons' => '.btn, button, [role="button"]',
            'buttons-active' => '.btn.active, .btn:active, button.active, button:active',
            'links-active' => 'a.active, a[aria-current], .router-link-active',
            'headers' => 'header, [data-header]',
            'titles' => 'h1, h2, h3, h4, h5, h6',
            'badges' => '.badge, [data-badge]',
            'borders' => '.border',
            'backgrounds' => '.background, [data-background]',
        ];

        return $selectorMap[$effect->target_element] ?? '';
    }

    /**
     * สร้าง selector ตาม trigger state
     */
    protected function getTriggerSelector(string $baseSelector, string $triggerState): string
    {
        switch ($triggerState) {
            case 'hover':
                return "{$baseSelector}:hover";
            case 'active':
                return $baseSelector; // Already active in base selector
            case 'focus':
                return "{$baseSelector}:focus, {$baseSelector}:focus-within";
            case 'click':
                return "{$baseSelector}:active";
            case 'always':
            default:
                return $baseSelector;
        }
    }

    /**
     * สร้างชื่อ animation
     */
    protected function getAnimationName(ThemeRgbEffect $effect): string
    {
        return 'arrow-x-rgb-' . $effect->id . '-' . $effect->animation_type;
    }

    /**
     * สร้าง JavaScript สำหรับ RGB Effects (ถ้าต้องการ dynamic control)
     *
     * @param ThemeSetting|null $themeSetting
     * @return string
     */
    public function generateAllEffectsJs(?ThemeSetting $themeSetting = null): string
    {
        $themeSetting = $themeSetting ?? ThemeSetting::active();

        if (!$themeSetting) {
            return '';
        }

        $js = "// Arrow X RGB Effects - Generated JavaScript\n\n";
        $js .= "window.ArrowXRgbEffects = {\n";
        $js .= "    initialized: false,\n\n";
        $js .= "    init() {\n";
        $js .= "        if (this.initialized) return;\n";
        $js .= "        console.log('Arrow X RGB Effects initialized');\n";
        $js .= "        this.initialized = true;\n";
        $js .= "    },\n\n";
        $js .= "    // Add custom methods here\n";
        $js .= "};\n\n";
        $js .= "// Auto-initialize\n";
        $js .= "if (document.readyState === 'loading') {\n";
        $js .= "    document.addEventListener('DOMContentLoaded', () => ArrowXRgbEffects.init());\n";
        $js .= "} else {\n";
        $js .= "    ArrowXRgbEffects.init();\n";
        $js .= "}\n";

        return $js;
    }
}
