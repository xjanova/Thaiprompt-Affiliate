<?php

namespace App\Services;

use App\Models\ThemeSetting;

/**
 * ThemeService
 *
 * จัดการ Arrow X Theme compilation และ CSS generation
 */
class ThemeService
{
    /**
     * สร้าง CSS Variables จาก Theme Settings
     *
     * @param ThemeSetting|null $themeSetting
     * @return string
     */
    public function generateCssVariables(?ThemeSetting $themeSetting = null): string
    {
        $themeSetting = $themeSetting ?? ThemeSetting::active();

        if (!$themeSetting) {
            return '';
        }

        $css = "/* Arrow X Theme - CSS Variables */\n";
        $css .= ":root {\n";

        // Layout Variables
        $css .= "    /* Layout */\n";
        $css .= "    --arrow-x-sidebar-width: {$themeSetting->sidebar_width}px;\n";
        $css .= "    --arrow-x-navbar-height: {$themeSetting->navbar_height}px;\n";
        $css .= "    --arrow-x-footer-height: {$themeSetting->footer_height}px;\n\n";

        // Opacity Variables
        $css .= "    /* Opacity */\n";
        $css .= "    --arrow-x-global-opacity: " . ($themeSetting->global_opacity / 100) . ";\n";
        $css .= "    --arrow-x-sidebar-opacity: " . ($themeSetting->sidebar_opacity / 100) . ";\n";
        $css .= "    --arrow-x-navbar-opacity: " . ($themeSetting->navbar_opacity / 100) . ";\n";
        $css .= "    --arrow-x-card-opacity: " . ($themeSetting->card_opacity / 100) . ";\n";
        $css .= "    --arrow-x-modal-opacity: " . ($themeSetting->modal_opacity / 100) . ";\n\n";

        // Card Variables
        $css .= "    /* Cards */\n";
        $css .= "    --arrow-x-card-blur: {$themeSetting->card_blur_intensity}px;\n";
        $css .= "    --arrow-x-card-border-width: {$themeSetting->card_border_width}px;\n";
        $css .= "    --arrow-x-card-border-radius: {$themeSetting->card_border_radius}px;\n";
        $css .= "    --arrow-x-card-shadow: var(--shadow-{$themeSetting->card_shadow_intensity});\n\n";

        // Glass Effect Variables (ใช้กับ .glass-fusion, .glass-neu, .glass-dropdown)
        $css .= "    /* Glass Effect */\n";
        $css .= "    --glass-opacity: " . ($themeSetting->card_opacity / 100) . ";\n";
        $css .= "    --glass-blur: {$themeSetting->card_blur_intensity}px;\n";
        $css .= "    --card-roundness: {$themeSetting->card_border_radius}px;\n";
        $css .= "    --border-opacity: 0.2;\n";
        $css .= "    --backdrop-saturate: 1.8;\n\n";

        // Colors (if available)
        if ($themeSetting->color) {
            $css .= $this->generateColorVariables($themeSetting->color);
        }

        // Typography (if available)
        if ($themeSetting->typography) {
            $css .= $this->generateTypographyVariables($themeSetting->typography);
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * สร้าง Color Variables
     */
    protected function generateColorVariables($color): string
    {
        $css = "    /* Colors - Primary Gradient */\n";
        $css .= "    --arrow-x-primary-start: {$color->primary_start};\n";
        $css .= "    --arrow-x-primary-middle: {$color->primary_middle};\n";
        $css .= "    --arrow-x-primary-end: {$color->primary_end};\n";
        $css .= "    --arrow-x-primary-gradient: linear-gradient(" . str_replace('-', ' ', $color->gradient_direction) . ", {$color->primary_start}, {$color->primary_middle}, {$color->primary_end});\n\n";

        $css .= "    /* Colors - Secondary Gradient */\n";
        $css .= "    --arrow-x-secondary-start: {$color->secondary_start};\n";
        $css .= "    --arrow-x-secondary-middle: {$color->secondary_middle};\n";
        $css .= "    --arrow-x-secondary-end: {$color->secondary_end};\n";
        $css .= "    --arrow-x-secondary-gradient: linear-gradient(" . str_replace('-', ' ', $color->gradient_direction) . ", {$color->secondary_start}, {$color->secondary_middle}, {$color->secondary_end});\n\n";

        $css .= "    /* Colors - Status */\n";
        $css .= "    --arrow-x-accent: {$color->accent_color};\n";
        $css .= "    --arrow-x-success: {$color->success_color};\n";
        $css .= "    --arrow-x-warning: {$color->warning_color};\n";
        $css .= "    --arrow-x-error: {$color->error_color};\n";
        $css .= "    --arrow-x-info: {$color->info_color};\n\n";

        $css .= "    /* Colors - Background (Light) */\n";
        $css .= "    --arrow-x-bg-primary: {$color->background_primary};\n";
        $css .= "    --arrow-x-bg-secondary: {$color->background_secondary};\n";
        $css .= "    --arrow-x-bg-tertiary: {$color->background_tertiary};\n\n";

        $css .= "    /* Colors - Text (Light) */\n";
        $css .= "    --arrow-x-text-primary: {$color->text_primary};\n";
        $css .= "    --arrow-x-text-secondary: {$color->text_secondary};\n";
        $css .= "    --arrow-x-text-tertiary: {$color->text_tertiary};\n\n";

        $css .= "    /* Colors - Borders */\n";
        $css .= "    --arrow-x-border: {$color->border_color};\n";
        $css .= "    --arrow-x-divider: {$color->divider_color};\n\n";

        return $css;
    }

    /**
     * สร้าง Typography Variables
     */
    protected function generateTypographyVariables($typography): string
    {
        $css = "    /* Typography - Fonts */\n";
        $css .= "    --arrow-x-font-primary: '{$typography->primary_font}', sans-serif;\n";
        $css .= "    --arrow-x-font-secondary: '{$typography->secondary_font}', sans-serif;\n";
        $css .= "    --arrow-x-font-code: '{$typography->code_font}', monospace;\n\n";

        $css .= "    /* Typography - Sizes */\n";
        $css .= "    --arrow-x-text-base: {$typography->base_font_size}rem;\n";
        $css .= "    --arrow-x-text-h1: {$typography->heading_h1_size}rem;\n";
        $css .= "    --arrow-x-text-h2: {$typography->heading_h2_size}rem;\n";
        $css .= "    --arrow-x-text-h3: {$typography->heading_h3_size}rem;\n";
        $css .= "    --arrow-x-text-h4: {$typography->heading_h4_size}rem;\n";
        $css .= "    --arrow-x-text-h5: {$typography->heading_h5_size}rem;\n";
        $css .= "    --arrow-x-text-h6: {$typography->heading_h6_size}rem;\n\n";

        $css .= "    /* Typography - Line Heights */\n";
        $css .= "    --arrow-x-leading-heading: {$typography->heading_line_height};\n";
        $css .= "    --arrow-x-leading-body: {$typography->body_line_height};\n\n";

        return $css;
    }

    /**
     * สร้าง Dark Mode CSS Variables
     */
    public function generateDarkModeCssVariables(?ThemeSetting $themeSetting = null): string
    {
        $themeSetting = $themeSetting ?? ThemeSetting::active();

        if (!$themeSetting || !$themeSetting->color) {
            return '';
        }

        $color = $themeSetting->color;

        $css = "/* Arrow X Theme - Dark Mode */\n";
        $css .= ".dark, [data-theme='dark'] {\n";

        $css .= "    /* Colors - Background (Dark) */\n";
        $css .= "    --arrow-x-bg-primary: {$color->dark_background_primary};\n";
        $css .= "    --arrow-x-bg-secondary: {$color->dark_background_secondary};\n";
        $css .= "    --arrow-x-bg-tertiary: {$color->dark_background_tertiary};\n\n";

        $css .= "    /* Colors - Text (Dark) */\n";
        $css .= "    --arrow-x-text-primary: {$color->dark_text_primary};\n";
        $css .= "    --arrow-x-text-secondary: {$color->dark_text_secondary};\n";
        $css .= "    --arrow-x-text-tertiary: {$color->dark_text_tertiary};\n\n";

        $css .= "    /* Colors - Borders (Dark) */\n";
        $css .= "    --arrow-x-border: {$color->dark_border_color};\n";
        $css .= "    --arrow-x-divider: {$color->dark_divider_color};\n";

        $css .= "}\n";

        return $css;
    }

    /**
     * Compile ทุกอย่างเป็น CSS file เดียว
     *
     * @return string
     */
    public function compileThemeCss(): string
    {
        $rgbService = new RgbEffectService();

        $css = "/**\n";
        $css .= " * Arrow X Theme System - Compiled CSS\n";
        $css .= " * Generated: " . now()->toDateTimeString() . "\n";
        $css .= " */\n\n";

        // CSS Variables
        $css .= $this->generateCssVariables() . "\n\n";

        // Dark Mode
        $css .= $this->generateDarkModeCssVariables() . "\n\n";

        // RGB Effects
        $css .= $rgbService->generateAllEffectsCss() . "\n";

        return $css;
    }

    /**
     * Compile JavaScript
     *
     * @return string
     */
    public function compileThemeJs(): string
    {
        $rgbService = new RgbEffectService();

        $js = "/**\n";
        $js .= " * Arrow X Theme System - Compiled JavaScript\n";
        $js .= " * Generated: " . now()->toDateTimeString() . "\n";
        $js .= " */\n\n";

        $js .= $rgbService->generateAllEffectsJs();

        return $js;
    }
}
