<?php

namespace Database\Factories;

use App\Models\ThemeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ ThemeSetting
 */
class ThemeSettingFactory extends Factory
{
    protected $model = ThemeSetting::class;

    public function definition(): array
    {
        return [
            'theme_name' => 'Arrow X',
            'theme_version' => '1.0.0',
            'is_active' => true,
            'brand_name' => 'TP-Affiliate',
            'brand_tagline' => 'Test Theme',
            'layout_type' => 'fluid',
            'sidebar_width' => 260,
            'navbar_height' => 64,
            'footer_height' => 80,
            'global_opacity' => 100,
            'sidebar_opacity' => 100,
            'navbar_opacity' => 100,
            'card_opacity' => 100,
            'modal_opacity' => 95,
            'card_blur_intensity' => 10,
            'card_border_width' => 1,
            'card_border_radius' => 16,
            'default_language' => 'th',
            'rtl_enabled' => false,
            'bg_effects_enabled' => true,
        ];
    }
}
