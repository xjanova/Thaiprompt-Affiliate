# 🚀 Arrow X Theme System - แผนการพัฒนาระบบ Theme ใหม่

> **Version:** 1.0.0
> **Date:** 2025-11-15
> **Status:** 📋 Planning Phase
> **Target:** ระบบ Theme ที่ปรับแต่งได้ทุกอย่าง สำหรับทั้ง Frontend + Backend

---

## 📊 สรุปความต้องการ (Requirements Summary)

### 🎯 เป้าหมายหลัก
1. **ยกเลิกระบบ Theme เดิมทั้งหมด** - เริ่มต้นใหม่จากศูนย์
2. **สร้างระบบ Theme ใหม่ชื่อ "Arrow X"** - เป็น Theme เดียวที่ปรับแต่งได้ทุกอย่าง
3. **ใช้ได้ทั้งระบบ** - Frontend + Admin Backend
4. **ระบบปรับแต่งระดับมืออาชีพ** - แยกหมวดหมู่ ตั้งค่าได้ละเอียด
5. **ระบบ RGB Lighting** - เรืองแสงได้หลายจุด ปรับแต่งได้
6. **Multi-language Support** - เริ่มจากไทย แต่รองรับหลายภาษา

---

## 🎨 Arrow X Theme Design Concept

### หลักการออกแบบ
```
Arrow X = Theme 1 (Base) + Theme 3 (Buttons) + Customizable Transparency
```

**องค์ประกอบหลัก:**
- ✅ **Base Style**: Premium Gradient (Theme 1) - สีสรร, 3D effects, Glow
- ✅ **Buttons**: Neumorphic 3D (Theme 3) - Soft shadow, Tactile feel
- ✅ **Transparency**: Optional - ปรับได้ตามต้องการ (0-100%)
- ✅ **RGB Lighting**: เรืองแสงได้หลายจุด - เมนู, header, cards, ฯลฯ

---

## 🗂️ Database Schema Design

**รวม 7 Tables:**
1. `theme_settings` - การตั้งค่าหลัก
2. `theme_colors` - สี Gradient
3. `theme_rgb_effects` - RGB animations
4. `theme_typography` - ฟอนต์
5. `theme_components` - Component settings
6. `translation_cache` - Cache คำแปลจาก Google Translate
7. `google_translate_settings` - ตั้งค่า Google Translate API

---

### 1. Table: `theme_settings`
เก็บการตั้งค่า Theme ทั้งหมด

```sql
CREATE TABLE theme_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Basic Info
    theme_name VARCHAR(100) DEFAULT 'Arrow X',
    theme_version VARCHAR(20) DEFAULT '1.0.0',
    is_active BOOLEAN DEFAULT true,

    -- Logo & Branding
    logo_path VARCHAR(255) NULL,
    favicon_path VARCHAR(255) NULL,
    brand_name VARCHAR(100) DEFAULT 'TP-Affiliate',
    brand_tagline VARCHAR(255) NULL,

    -- Layout Settings
    layout_type ENUM('fixed', 'fluid', 'boxed') DEFAULT 'fluid',
    sidebar_width INT DEFAULT 260 COMMENT 'px',
    navbar_height INT DEFAULT 64 COMMENT 'px',
    footer_height INT DEFAULT 80 COMMENT 'px',

    -- Transparency Settings (0-100)
    global_opacity INT DEFAULT 100 COMMENT '0-100%',
    sidebar_opacity INT DEFAULT 100,
    navbar_opacity INT DEFAULT 100,
    card_opacity INT DEFAULT 100,
    modal_opacity INT DEFAULT 95,

    -- Card Settings
    card_blur_intensity INT DEFAULT 0 COMMENT '0-20px',
    card_border_width INT DEFAULT 1 COMMENT 'px',
    card_border_radius INT DEFAULT 16 COMMENT 'px',
    card_shadow_intensity ENUM('none', 'sm', 'md', 'lg', 'xl', '2xl') DEFAULT 'lg',

    -- Language Settings
    default_language VARCHAR(5) DEFAULT 'th' COMMENT 'th, en, etc.',
    available_languages JSON DEFAULT '["th", "en"]',
    rtl_enabled BOOLEAN DEFAULT false,

    -- Created/Updated
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_theme_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Table: `theme_colors`
เก็บการตั้งค่าสี Gradient และ RGB

```sql
CREATE TABLE theme_colors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_setting_id BIGINT UNSIGNED,

    -- Color Categories
    category ENUM(
        'primary', 'secondary', 'accent',
        'success', 'warning', 'danger', 'info',
        'background', 'text', 'border'
    ) NOT NULL,

    -- Gradient Colors (for gradients)
    color_start VARCHAR(7) NOT NULL COMMENT 'Hex color #RRGGBB',
    color_middle VARCHAR(7) NULL COMMENT 'Hex color #RRGGBB (optional)',
    color_end VARCHAR(7) NOT NULL COMMENT 'Hex color #RRGGBB',

    -- Gradient Direction
    gradient_direction ENUM(
        'to-right', 'to-left', 'to-top', 'to-bottom',
        'to-top-right', 'to-top-left', 'to-bottom-right', 'to-bottom-left'
    ) DEFAULT 'to-right',

    -- Opacity for this color scheme
    opacity INT DEFAULT 100 COMMENT '0-100%',

    -- Usage Context
    apply_to ENUM('sidebar', 'navbar', 'cards', 'buttons', 'badges', 'all') DEFAULT 'all',

    FOREIGN KEY (theme_setting_id) REFERENCES theme_settings(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_apply_to (apply_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Table: `theme_rgb_effects`
เก็บการตั้งค่าเอฟเฟกต์ RGB เรืองแสง

```sql
CREATE TABLE theme_rgb_effects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_setting_id BIGINT UNSIGNED,

    -- Effect Info
    effect_name VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN DEFAULT true,

    -- Target Element
    target_element ENUM(
        'sidebar', 'navbar', 'menu-items', 'menu-items-active', 'cards', 'buttons', 'buttons-active',
        'links-active', 'headers', 'titles', 'badges', 'borders', 'backgrounds',
        'custom'
    ) NOT NULL,
    custom_selector VARCHAR(255) NULL COMMENT 'CSS selector if target=custom',

    -- Trigger State (เมื่อไหร่จะแสดงเอฟเฟกต์)
    trigger_state ENUM(
        'always', 'hover', 'active', 'focus', 'click'
    ) DEFAULT 'always' COMMENT 'always=แสดงตลอด, hover=เมื่อ hover, active=เมื่อ element เป็น active',

    -- RGB Animation Settings
    animation_type ENUM(
        'rainbow', 'wave', 'pulse', 'glow', 'breathing',
        'slide', 'rotate', 'flash', 'static'
    ) DEFAULT 'rainbow',

    -- Colors for RGB cycle
    rgb_colors JSON NOT NULL COMMENT '["#FF0000", "#00FF00", "#0000FF", ...]',

    -- Animation Speed
    animation_duration INT DEFAULT 3000 COMMENT 'milliseconds',
    animation_timing ENUM('linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out') DEFAULT 'linear',

    -- Effect Intensity
    intensity ENUM('subtle', 'medium', 'strong', 'extreme') DEFAULT 'medium',
    blur_radius INT DEFAULT 10 COMMENT 'px for glow effect',

    -- Timing
    delay INT DEFAULT 0 COMMENT 'milliseconds delay before start',
    iteration_count VARCHAR(20) DEFAULT 'infinite' COMMENT 'infinite or number',

    -- Position (z-index for layering)
    z_index INT DEFAULT 0,

    FOREIGN KEY (theme_setting_id) REFERENCES theme_settings(id) ON DELETE CASCADE,
    INDEX idx_target (target_element),
    INDEX idx_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. Table: `theme_typography`
เก็บการตั้งค่าฟอนต์และตัวอักษร

```sql
CREATE TABLE theme_typography (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_setting_id BIGINT UNSIGNED,

    -- Font Families
    primary_font VARCHAR(100) DEFAULT 'Inter',
    secondary_font VARCHAR(100) DEFAULT 'Noto Sans Thai',
    code_font VARCHAR(100) DEFAULT 'Fira Code',

    -- Font Sizes (rem)
    base_font_size DECIMAL(3,2) DEFAULT 1.00 COMMENT 'rem',
    heading_h1_size DECIMAL(3,2) DEFAULT 2.50,
    heading_h2_size DECIMAL(3,2) DEFAULT 2.00,
    heading_h3_size DECIMAL(3,2) DEFAULT 1.75,
    heading_h4_size DECIMAL(3,2) DEFAULT 1.50,
    heading_h5_size DECIMAL(3,2) DEFAULT 1.25,
    heading_h6_size DECIMAL(3,2) DEFAULT 1.00,

    -- Font Weights
    thin_weight INT DEFAULT 300,
    normal_weight INT DEFAULT 400,
    medium_weight INT DEFAULT 500,
    semibold_weight INT DEFAULT 600,
    bold_weight INT DEFAULT 700,
    extrabold_weight INT DEFAULT 800,

    -- Line Heights
    heading_line_height DECIMAL(3,2) DEFAULT 1.20,
    body_line_height DECIMAL(3,2) DEFAULT 1.60,

    FOREIGN KEY (theme_setting_id) REFERENCES theme_settings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Table: `theme_components`
เก็บการตั้งค่าแต่ละ Component

```sql
CREATE TABLE theme_components (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_setting_id BIGINT UNSIGNED,

    -- Component Info
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM(
        'card', 'button', 'input', 'select', 'modal', 'dropdown',
        'table', 'badge', 'alert', 'navbar', 'sidebar', 'footer'
    ) NOT NULL,

    -- Visibility
    is_enabled BOOLEAN DEFAULT true,

    -- Dimensions
    height INT NULL COMMENT 'px or null for auto',
    width VARCHAR(20) NULL COMMENT 'px, %, auto',
    padding_x INT DEFAULT 16 COMMENT 'px horizontal',
    padding_y INT DEFAULT 12 COMMENT 'px vertical',
    margin_x INT DEFAULT 0,
    margin_y INT DEFAULT 0,

    -- Styling
    border_radius INT DEFAULT 12 COMMENT 'px',
    border_width INT DEFAULT 1,
    opacity INT DEFAULT 100 COMMENT '0-100%',

    -- Shadow Settings
    shadow_type ENUM('none', 'sm', 'md', 'lg', 'xl', '2xl', 'inner') DEFAULT 'md',
    shadow_color VARCHAR(20) DEFAULT 'rgba(0,0,0,0.1)',

    -- Neumorphic Settings (for buttons)
    neumorphic_enabled BOOLEAN DEFAULT false,
    neumorphic_light_shadow VARCHAR(50) DEFAULT '-8px -8px 16px rgba(255,255,255,0.1)',
    neumorphic_dark_shadow VARCHAR(50) DEFAULT '8px 8px 16px rgba(0,0,0,0.3)',

    -- Hover Effects
    hover_scale DECIMAL(3,2) DEFAULT 1.05,
    hover_shadow_intensity VARCHAR(20) DEFAULT 'lg',
    hover_opacity INT DEFAULT 100,

    -- Custom CSS
    custom_css TEXT NULL,

    FOREIGN KEY (theme_setting_id) REFERENCES theme_settings(id) ON DELETE CASCADE,
    INDEX idx_component_type (component_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6. Table: `translation_cache`
เก็บ cache คำแปลจาก Google Translate API

```sql
CREATE TABLE translation_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Source & Target
    source_language VARCHAR(5) NOT NULL COMMENT 'th, en, ja, etc.',
    target_language VARCHAR(5) NOT NULL COMMENT 'th, en, ja, etc.',

    -- Text
    source_text TEXT NOT NULL,
    translated_text TEXT NOT NULL,

    -- Hash for fast lookup
    text_hash VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of source_text + languages',

    -- API Info
    api_provider VARCHAR(50) DEFAULT 'google_translate',
    api_call_timestamp TIMESTAMP NULL,

    -- Usage Stats
    hit_count INT DEFAULT 1 COMMENT 'จำนวนครั้งที่ใช้ cache นี้',
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Created/Updated
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_translation (text_hash, source_language, target_language),
    INDEX idx_languages (source_language, target_language),
    INDEX idx_hash (text_hash),
    INDEX idx_last_used (last_used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7. Table: `google_translate_settings`
เก็บการตั้งค่า Google Translate API

```sql
CREATE TABLE google_translate_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- API Credentials
    api_key VARCHAR(255) NOT NULL COMMENT 'Google Cloud API Key (encrypted)',
    project_id VARCHAR(100) NULL,

    -- Default Settings
    default_source_language VARCHAR(5) DEFAULT 'th' COMMENT 'ภาษาต้นทาง',
    enabled_target_languages JSON DEFAULT '["en", "ja", "zh", "ko"]' COMMENT 'ภาษาที่เปิดใช้งาน',

    -- Features
    auto_detect_enabled BOOLEAN DEFAULT true COMMENT 'ตรวจจับภาษาอัตโนมัติ',
    cache_enabled BOOLEAN DEFAULT true COMMENT 'ใช้ cache',
    cache_ttl INT DEFAULT 2592000 COMMENT 'Cache TTL (seconds) = 30 days',

    -- Limits & Quotas
    daily_quota INT DEFAULT 10000 COMMENT 'จำนวนคำแปลสูงสุดต่อวัน',
    monthly_quota INT DEFAULT 300000,
    current_daily_usage INT DEFAULT 0,
    current_monthly_usage INT DEFAULT 0,
    quota_reset_at TIMESTAMP NULL,

    -- Fallback
    fallback_language VARCHAR(5) DEFAULT 'th' COMMENT 'ภาษาสำรอง',
    fallback_enabled BOOLEAN DEFAULT true,

    -- Status
    is_active BOOLEAN DEFAULT true,
    last_api_call_at TIMESTAMP NULL,
    last_error_message TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🏗️ Backend Architecture

### Directory Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── ThemeController.php          # จัดการ Theme settings
│   └── Requests/
│       └── ThemeSettingRequest.php          # Validation
├── Models/
│   ├── ThemeSetting.php                     # Main theme model
│   ├── ThemeColor.php                       # Color schemes
│   ├── ThemeRgbEffect.php                   # RGB effects
│   ├── ThemeTypography.php                  # Typography settings
│   ├── ThemeComponent.php                   # Component settings
│   ├── TranslationCache.php                 # Translation cache model
│   └── GoogleTranslateSetting.php           # Google Translate API settings
├── Services/
│   ├── ThemeService.php                     # Business logic
│   ├── ThemeCompilerService.php             # Compile CSS/JS
│   ├── RgbEffectService.php                 # RGB animations
│   └── TranslationService.php               # Google Translate API integration
└── View/
    └── Components/
        ├── ArrowXLayout.php                 # Main layout component
        ├── ArrowXSidebar.php                # Sidebar component
        ├── ArrowXNavbar.php                 # Navbar component
        ├── ArrowXCard.php                   # Card component
        ├── ArrowXButton.php                 # Button component
        └── RgbEffect.php                    # RGB effect component
```

### Key Services

#### 1. **ThemeService.php**
```php
<?php

namespace App\Services;

class ThemeService
{
    /**
     * ดึงการตั้งค่า theme ที่ใช้งานอยู่
     */
    public function getActiveTheme(): ThemeSetting;

    /**
     * อัพเดทการตั้งค่า theme
     */
    public function updateSettings(array $settings): ThemeSetting;

    /**
     * รีเซ็ตเป็นค่าเริ่มต้น
     */
    public function resetToDefault(): ThemeSetting;

    /**
     * Export การตั้งค่าเป็น JSON
     */
    public function exportSettings(): array;

    /**
     * Import การตั้งค่าจาก JSON
     */
    public function importSettings(array $settings): ThemeSetting;
}
```

#### 2. **ThemeCompilerService.php**
```php
<?php

namespace App\Services;

class ThemeCompilerService
{
    /**
     * Compile Tailwind config จากการตั้งค่า
     */
    public function compileTailwindConfig(): string;

    /**
     * Generate CSS variables
     */
    public function generateCssVariables(): string;

    /**
     * Build final CSS file
     */
    public function buildCss(): void;

    /**
     * Cache compiled assets
     */
    public function cacheAssets(): void;
}
```

#### 3. **RgbEffectService.php**
```php
<?php

namespace App\Services;

class RgbEffectService
{
    /**
     * Generate RGB animation CSS
     */
    public function generateAnimationCss(ThemeRgbEffect $effect): string;

    /**
     * Generate RGB animation JavaScript
     */
    public function generateAnimationJs(ThemeRgbEffect $effect): string;

    /**
     * Get all active effects
     */
    public function getActiveEffects(): Collection;
}
```

#### 4. **TranslationService.php**
```php
<?php

namespace App\Services;

use Google\Cloud\Translate\V2\TranslateClient;
use App\Models\TranslationCache;
use App\Models\GoogleTranslateSetting;

class TranslationService
{
    protected TranslateClient $client;
    protected GoogleTranslateSetting $settings;

    /**
     * แปลข้อความผ่าน Google Translate API
     *
     * @param string $text ข้อความต้นทาง
     * @param string $targetLang ภาษาปลายทาง (en, ja, zh, etc.)
     * @param string|null $sourceLang ภาษาต้นทาง (null = auto-detect)
     * @return string ข้อความที่แปลแล้ว
     */
    public function translate(
        string $text,
        string $targetLang,
        ?string $sourceLang = null
    ): string;

    /**
     * ตรวจสอบ cache ก่อนเรียก API
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return string|null
     */
    protected function getFromCache(
        string $text,
        string $sourceLang,
        string $targetLang
    ): ?string;

    /**
     * บันทึกลง cache
     *
     * @param string $sourceText
     * @param string $translatedText
     * @param string $sourceLang
     * @param string $targetLang
     * @return void
     */
    protected function saveToCache(
        string $sourceText,
        string $translatedText,
        string $sourceLang,
        string $targetLang
    ): void;

    /**
     * ตรวจจับภาษาอัตโนมัติ
     *
     * @param string $text
     * @return string Language code (th, en, ja, etc.)
     */
    public function detectLanguage(string $text): string;

    /**
     * แปลหลายข้อความพร้อมกัน (batch)
     *
     * @param array $texts
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return array
     */
    public function translateBatch(
        array $texts,
        string $targetLang,
        ?string $sourceLang = null
    ): array;

    /**
     * ตรวจสอบ quota ว่าเหลือพอหรือไม่
     *
     * @return bool
     */
    public function checkQuota(): bool;

    /**
     * อัพเดท quota usage
     *
     * @param int $count
     * @return void
     */
    protected function updateQuotaUsage(int $count): void;

    /**
     * ดึงภาษาที่รองรับทั้งหมด
     *
     * @return array
     */
    public function getSupportedLanguages(): array;

    /**
     * Fallback เป็นภาษาไทยถ้าแปลไม่ได้
     *
     * @param string $text
     * @return string
     */
    protected function fallback(string $text): string;

    /**
     * ล้าง cache ที่ไม่ได้ใช้งานนานๆ
     *
     * @param int $days
     * @return int จำนวน records ที่ลบ
     */
    public function clearOldCache(int $days = 30): int;
}
```

---

## 🎨 Frontend Implementation

### 1. Main Layout: `arrow-x-layout.blade.php`
```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ $themeSetting->brand_name }}</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ $themeSetting->favicon_path ?? '/favicon.ico' }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family={{ $typography->primary_font }}:wght@300;400;500;600;700;800;900&family={{ $typography->secondary_font }}:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Compiled Theme CSS --}}
    <link rel="stylesheet" href="{{ asset('build/arrow-x-theme.css') }}">

    {{-- Dynamic CSS Variables --}}
    <style id="arrow-x-variables">
        :root {
            {!! $themeCompiler->generateCssVariables() !!}
        }
    </style>

    {{-- RGB Effects CSS --}}
    <style id="arrow-x-rgb-effects">
        {!! $rgbService->generateAllEffectsCss() !!}
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800"
      x-data="arrowXApp()"
      x-init="init()"
      style="opacity: {{ $themeSetting->global_opacity / 100 }}">

    <div class="flex h-full">
        {{-- Sidebar --}}
        <x-arrow-x-sidebar />

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Navbar --}}
            <x-arrow-x-navbar />

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>

            {{-- Footer --}}
            <x-arrow-x-footer />
        </div>
    </div>

    {{-- RGB Effects JavaScript --}}
    <script>
        {!! $rgbService->generateAllEffectsJs() !!}
    </script>

    @stack('scripts')
</body>
</html>
```

### 2. Alpine.js App Component
```javascript
// resources/js/arrow-x-app.js

function arrowXApp() {
    return {
        // State
        sidebarOpen: true,
        darkMode: localStorage.getItem('darkMode') === 'true',
        language: localStorage.getItem('language') || 'th',

        // Theme Settings (loaded from backend)
        themeSettings: {},

        // Init
        init() {
            this.loadThemeSettings();
            this.applyDarkMode();
            this.initRgbEffects();
        },

        // Load theme settings via AJAX
        async loadThemeSettings() {
            const response = await fetch('/api/theme/settings');
            this.themeSettings = await response.json();
        },

        // Dark mode
        applyDarkMode() {
            document.documentElement.classList.toggle('dark', this.darkMode);
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            this.applyDarkMode();
        },

        // Language
        changeLanguage(lang) {
            this.language = lang;
            localStorage.setItem('language', lang);
            window.location.reload();
        },

        // RGB Effects
        initRgbEffects() {
            // Initialize RGB animation loops
            if (window.ArrowXRgbEffects) {
                window.ArrowXRgbEffects.init();
            }
        }
    }
}
```

---

## ⚙️ Theme Settings UI - แบ่งเป็นหมวดหมู่

### หมวดหมู่การตั้งค่า (Settings Categories)

#### 1. 🎨 **สีและการไล่สี (Colors & Gradients)**
- Primary Colors (Gradient Start → End)
- Secondary Colors
- Accent Colors
- Success/Warning/Danger/Info Colors
- Text Colors (Light/Dark mode)
- Background Colors
- Border Colors

#### 2. 🖼️ **โลโก้และแบรนด์ (Logo & Branding)**
- อัพโหลดโลโก้ (SVG, PNG)
- อัพโหลด Favicon
- ชื่อแบรนด์
- Tagline
- Logo Size (Width/Height)
- Logo Position

#### 3. 📐 **เลย์เอาท์และขนาด (Layout & Dimensions)**
- Layout Type (Fixed/Fluid/Boxed)
- Sidebar Width (px)
- Navbar Height (px)
- Footer Height (px)
- Container Max Width
- Content Spacing

#### 4. 💎 **ความโปร่งใสและเบลอ (Transparency & Blur)**
- Global Opacity (0-100%)
- Sidebar Opacity
- Navbar Opacity
- Card Opacity
- Modal Opacity
- Backdrop Blur Intensity (0-20px)

#### 5. 🎴 **การ์ดและคอมโพเนนท์ (Cards & Components)**
- Card Border Radius (px)
- Card Border Width (px)
- Card Shadow Intensity (none → 2xl)
- Card Background Opacity
- Card Hover Effects
- Component Spacing

#### 6. 🔘 **ปุ่มและการโต้ตอบ (Buttons & Interactions)**
- Button Style (Gradient/Neumorphic/Flat)
- Neumorphic Light Shadow
- Neumorphic Dark Shadow
- Hover Scale (1.0 - 1.2)
- Hover Shadow Intensity
- Animation Duration

#### 7. ✨ **เอฟเฟกต์ RGB (RGB Effects)**
**แยกตามตำแหน่งและ State:**

**Trigger State Options (เมื่อไหร่จะแสดงเอฟเฟกต์):**
- Always - แสดงตลอดเวลา
- Hover - แสดงเมื่อ hover
- Active - แสดงเมื่อ element เป็น active
- Focus - แสดงเมื่อ focus
- Click - แสดงเมื่อคลิก

**RGB Effect Positions:**

- **Sidebar RGB Effect**
  - Enable/Disable
  - Trigger State (Always/Hover/Active/Focus)
  - Animation Type (Rainbow/Wave/Pulse/Glow/Breathing/Slide/Rotate/Flash)
  - Colors Cycle (JSON array of hex colors)
  - Speed (ms)
  - Intensity (Subtle/Medium/Strong/Extreme)
  - Blur Radius (px)

- **Navbar RGB Effect**
  - (same options as Sidebar)

- **Menu Items RGB Effect**
  - (same options)
  - **Trigger State: Always** (แสดงตลอด)

- **Menu Items Active RGB Effect** ⭐ **NEW**
  - (same options)
  - **Trigger State: Active** (แสดงเฉพาะเมนูที่กำลัง active)
  - **CSS Target: .menu-item.active, .nav-link.active**

- **Cards RGB Effect**
  - (same options)

- **Buttons RGB Effect**
  - (same options)
  - **Trigger State: Always/Hover** (แสดงตลอดหรือเมื่อ hover)

- **Buttons Active RGB Effect** ⭐ **NEW**
  - (same options)
  - **Trigger State: Active/Click** (แสดงเมื่อปุ่มถูกคลิกหรือ active)
  - **CSS Target: .btn.active, .btn:active, button.active**

- **Links Active RGB Effect** ⭐ **NEW**
  - (same options)
  - **Trigger State: Active** (แสดงเฉพาะลิงก์ที่ active)
  - **CSS Target: a.active, a.router-link-active**

- **Headers RGB Effect**
  - (same options)

- **Custom Elements RGB Effect**
  - CSS Selector (กำหนดเอง)
  - Trigger State (กำหนดเอง)
  - (same options)

#### 8. 🔤 **ตัวอักษรและฟอนต์ (Typography)**
- Primary Font Family
- Secondary Font Family
- Code Font Family
- Base Font Size (rem)
- Heading Sizes (H1-H6)
- Font Weights
- Line Heights

#### 9. 🌍 **ภาษาและการแปล (Language & Localization)**
**ใช้ Google Translate API อัตโนมัติ**
- Default Language (th = ไทย เป็นค่าเริ่มต้น)
- Target Languages (เลือกภาษาที่ต้องการแปล)
- Google Cloud Translate API Key
- Auto-detect Language (ตรวจจับภาษาอัตโนมัติ)
- Translation Cache (เก็บ cache คำแปลเพื่อประหยัด API calls)
- RTL Support (Enable/Disable สำหรับภาษาอาหรับ/ฮีบรู)
- Date Format (แยกตามภาษา)
- Number Format (แยกตามภาษา)
- Currency Symbol (แยกตามภาษา)

**คุณสมบัติ:**
- ✅ แปลอัตโนมัติผ่าน Google Translate API
- ✅ Cache คำแปลใน Database (ไม่เรียก API ซ้ำ)
- ✅ Fallback เป็นภาษาไทยถ้าแปลไม่ได้
- ✅ รองรับ 100+ ภาษา (ตาม Google Translate)
- ✅ แปล Real-time เมื่อผู้ใช้เปลี่ยนภาษา

#### 10. 🎭 **โหมดมืด/สว่าง (Dark/Light Mode)**
- Default Mode
- Auto Switch (based on time)
- Custom Dark Colors
- Custom Light Colors
- Transition Speed

#### 11. 🎬 **แอนิเมชั่นและการเคลื่อนไหว (Animations & Transitions)**
- Global Animation Duration
- Transition Timing Function
- Page Load Animation
- Scroll Reveal Effects
- Hover Animation Speed

#### 12. 📱 **Responsive และ Mobile (Responsive & Mobile)**
- Mobile Sidebar Behavior
- Mobile Navbar Behavior
- Touch Gestures
- Breakpoints (Custom)
- Mobile Font Sizes

---

## 🎯 Implementation Phases (ระยะการพัฒนา)

### **Phase 1: Foundation (Week 1)** 🏗️
- [ ] สร้าง Database Schema (5 tables)
- [ ] สร้าง Models และ Relationships
- [ ] สร้าง Migrations และ Seeders
- [ ] สร้าง Base Services (ThemeService, ThemeCompilerService)

### **Phase 2: Admin UI (Week 2)** 🎨
- [ ] สร้างหน้า Theme Settings (Admin)
- [ ] แบ่งการตั้งค่าเป็น 12 หมวด (Tabs)
- [ ] Color Picker สำหรับเลือกสี Gradient
- [ ] Upload Logo/Favicon
- [ ] Slider controls สำหรับ Opacity, Size, etc.

### **Phase 3: RGB Effects System (Week 3)** ✨
- [ ] สร้าง RgbEffectService
- [ ] Generate CSS Animations (Rainbow, Wave, Pulse, etc.)
- [ ] Generate JavaScript Animations
- [ ] UI สำหรับตั้งค่า RGB Effects แต่ละตำแหน่ง
- [ ] Preview RGB Effects แบบ Real-time

### **Phase 4: Frontend Components (Week 4)** 🧩
- [ ] สร้าง Blade Components (Sidebar, Navbar, Card, Button, etc.)
- [ ] Arrow X Layout Template
- [ ] Alpine.js Integration
- [ ] Dynamic CSS Variables System
- [ ] Real-time Theme Preview

### **Phase 5: Theme Compiler (Week 5)** ⚙️
- [ ] Compile Tailwind Config จากการตั้งค่า
- [ ] Generate CSS Variables
- [ ] Build และ Cache CSS/JS Files
- [ ] Hot Reload สำหรับ Development

### **Phase 6: Google Translate API Integration (Week 6)** 🌍
- [ ] Setup Google Cloud Translate API
- [ ] สร้าง TranslationService (ใช้ Google Translate API)
- [ ] Translation Cache System (เก็บ cache ใน Database)
- [ ] Language Switcher UI (เลือกภาษาได้ 100+ ภาษา)
- [ ] Auto-detect Language Feature
- [ ] Fallback System (กลับไปใช้ไทยถ้าแปลไม่ได้)
- [ ] Default Language: Thai (th)
- [ ] Admin UI สำหรับจัดการ API Key และ Target Languages

### **Phase 7: Migration & Testing (Week 7)** 🧪
- [ ] ยกเลิกระบบ Theme เดิม (Deprecate old themes)
- [ ] Migration Script (ย้ายข้อมูลถ้ามี)
- [ ] Unit Tests
- [ ] Integration Tests
- [ ] UI/UX Testing

### **Phase 8: Documentation & Launch (Week 8)** 📚
- [ ] User Guide (Thai)
- [ ] Developer Documentation
- [ ] Video Tutorials
- [ ] Launch Arrow X Theme
- [ ] Announcement

---

## 📋 ตัวอย่าง UI Settings Screen

### หน้า Theme Settings (Wireframe)

```
┌─────────────────────────────────────────────────────────────────┐
│ Arrow X Theme Settings                                    [Save] │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│ ┌─ Tabs ────────────────────────────────────────────────────┐  │
│ │ [สีและการไล่สี] [โลโก้] [เลย์เอาท์] [ความโปร่งใส] ...    │  │
│ └───────────────────────────────────────────────────────────┘  │
│                                                                   │
│ ┌─ สีและการไล่สี (Colors & Gradients) ────────────────────┐  │
│ │                                                            │  │
│ │  Primary Gradient:                                         │  │
│ │  ┌────────┐  Start: [🎨 #3B82F6] ────────────────────┐   │  │
│ │  │████████│  Middle: [🎨 #8B5CF6] (Optional)          │   │  │
│ │  │████████│  End: [🎨 #EC4899] ──────────────────────┘   │  │
│ │  └────────┘                                                │  │
│ │                                                            │  │
│ │  Direction: [→ To Right ▼]                                │  │
│ │  Opacity: [████████──] 80%                                │  │
│ │  Apply to: [☑ Sidebar] [☑ Cards] [☐ Buttons]             │  │
│ │                                                            │  │
│ │  [+ Add Color Scheme]                                     │  │
│ │                                                            │  │
│ └────────────────────────────────────────────────────────────┘  │
│                                                                   │
│ ┌─ Live Preview ────────────────────────────────────────────┐  │
│ │ [Real-time preview of theme changes]                       │  │
│ │ ┌─────────────────────────────────────────────────────┐   │  │
│ │ │ [Preview Window]                                     │   │  │
│ │ └─────────────────────────────────────────────────────┘   │  │
│ └────────────────────────────────────────────────────────────┘  │
│                                                                   │
│                              [Reset to Default] [Export] [Import] │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎨 RGB Effect Examples

### 1. Rainbow Animation (แถบเมนู)
```css
@keyframes rainbow {
    0% { border-left-color: #FF0000; box-shadow: 0 0 20px #FF0000; }
    14% { border-left-color: #FF7F00; box-shadow: 0 0 20px #FF7F00; }
    28% { border-left-color: #FFFF00; box-shadow: 0 0 20px #FFFF00; }
    42% { border-left-color: #00FF00; box-shadow: 0 0 20px #00FF00; }
    57% { border-left-color: #0000FF; box-shadow: 0 0 20px #0000FF; }
    71% { border-left-color: #4B0082; box-shadow: 0 0 20px #4B0082; }
    85% { border-left-color: #9400D3; box-shadow: 0 0 20px #9400D3; }
    100% { border-left-color: #FF0000; box-shadow: 0 0 20px #FF0000; }
}

.menu-item.rgb-rainbow {
    animation: rainbow 3s linear infinite;
    border-left: 4px solid;
}
```

### 2. Wave Effect (หัวเรื่อง)
```css
@keyframes wave-glow {
    0%, 100% { text-shadow: 0 0 10px #FF0066, 0 0 20px #FF0066; }
    50% { text-shadow: 0 0 20px #00FFFF, 0 0 40px #00FFFF; }
}

.title.rgb-wave {
    animation: wave-glow 2s ease-in-out infinite;
}
```

### 3. Pulse Effect (ปุ่ม)
```css
@keyframes pulse-rgb {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
    50% { box-shadow: 0 0 0 20px rgba(59, 130, 246, 0); }
    100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}

.button.rgb-pulse {
    animation: pulse-rgb 2s infinite;
}
```

---

## 🔧 API Endpoints

### Theme Management API

```php
// Get active theme settings
GET /api/theme/settings

// Update theme settings
PUT /api/theme/settings
{
    "global_opacity": 90,
    "sidebar_width": 280,
    "card_blur_intensity": 10,
    // ... other settings
}

// Get theme colors
GET /api/theme/colors

// Update theme colors
PUT /api/theme/colors
{
    "category": "primary",
    "color_start": "#3B82F6",
    "color_end": "#8B5CF6",
    "gradient_direction": "to-right"
}

// Get RGB effects
GET /api/theme/rgb-effects

// Create RGB effect
POST /api/theme/rgb-effects
{
    "target_element": "sidebar",
    "animation_type": "rainbow",
    "rgb_colors": ["#FF0000", "#00FF00", "#0000FF"],
    "animation_duration": 3000
}

// Update RGB effect
PUT /api/theme/rgb-effects/{id}

// Delete RGB effect
DELETE /api/theme/rgb-effects/{id}

// Reset to default
POST /api/theme/reset

// Export settings
GET /api/theme/export

// Import settings
POST /api/theme/import
{
    "settings": { ... }
}

// Preview theme (without saving)
POST /api/theme/preview
{
    "settings": { ... }
}
```

---

## 📊 Performance Considerations

### 1. Caching Strategy
- ✅ Cache compiled CSS/JS files
- ✅ Cache theme settings in Redis
- ✅ Cache color schemes
- ✅ Use Laravel Cache for theme queries
- ✅ CDN for static assets

### 2. Optimization
- ✅ Lazy load RGB effects (only active ones)
- ✅ Minify generated CSS/JS
- ✅ Use CSS variables for dynamic values
- ✅ Debounce real-time preview updates
- ✅ Optimize database queries (eager loading)

### 3. Bundle Size
- ✅ Code splitting (admin vs frontend)
- ✅ Tree shaking unused code
- ✅ Compress images (Logo/Favicon)
- ✅ Use WebP for images
- ✅ Lazy load components

---

## ✅ Success Criteria (เกณฑ์ความสำเร็จ)

### Must Have (บังคับต้องมี)
- [x] ✅ ปรับแต่งสีได้ทุกส่วน (Gradient support)
- [x] ✅ อัพโหลด Logo/Favicon ได้
- [x] ✅ ปรับความโปร่งใสได้ทุก element
- [x] ✅ ปุ่ม Neumorphic 3D
- [x] ✅ ระบบ RGB Effects (อย่างน้อย 5 แบบ)
- [x] ✅ ใช้กับทั้ง Frontend + Admin
- [x] ✅ Multi-language (เริ่มจากไทย)
- [x] ✅ Real-time Preview
- [x] ✅ Export/Import Settings
- [x] ✅ Responsive ทุกหน้าจอ

### Nice to Have (ควรมี)
- [ ] 🎯 Theme Marketplace (แชร์ theme ระหว่าง users)
- [ ] 🎯 Theme Versioning (ย้อนกลับเป็น version เก่าได้)
- [ ] 🎯 A/B Testing (ทดสอบ theme หลายๆ แบบ)
- [ ] 🎯 Analytics (วัดผลการใช้งาน theme)
- [ ] 🎯 Dark/Light Auto Switch (ตามเวลา)

---

## 🚨 Risks & Mitigation (ความเสี่ยง)

### Risk 1: Performance Impact from RGB Effects
**Mitigation:**
- ใช้ CSS animations แทน JavaScript
- จำกัดจำนวน RGB effects ที่เปิดพร้อมกัน
- ให้ผู้ใช้เลือก intensity (subtle → extreme)

### Risk 2: Browser Compatibility
**Mitigation:**
- Test ใน Chrome, Firefox, Safari, Edge
- Fallback สำหรับ browser เก่า
- Progressive enhancement

### Risk 3: Theme Settings Too Complex
**Mitigation:**
- UI/UX ที่เข้าใจง่าย (Wizard style)
- Presets สำเร็จรูป (Light, Dark, Colorful, etc.)
- Tooltips และ Help text

### Risk 4: Migration from Old Themes
**Mitigation:**
- ทำ migration script ดี ๆ
- Backup ข้อมูลก่อน migrate
- ให้ผู้ใช้ preview ก่อน apply

---

## 📝 Next Steps (ขั้นตอนถัดไป)

### ต้องการคำยืนยันจากคุณ:

1. **✅ อนุมัติแผนนี้หรือไม่?**
   - มีอะไรต้องเพิ่ม/ลดออกบ้างไหม?

2. **🎨 Design mockup ต้องการไหม?**
   - ผมจะสร้าง mockup UI ให้ดูก่อนเริ่มเขียนโค้ด

3. **⏱️ Timeline ตกลงหรือเปล่า?**
   - 8 สัปดาห์ เหมาะสมไหม? หรือต้องการเร็วกว่านี้?

4. **🚀 เริ่มจาก Phase ไหนก่อน?**
   - แนะนำ: Phase 1 (Database + Models)
   - หรือต้องการเริ่มจาก UI mockup ก่อน?

5. **🎯 Priority Features**
   - Feature ไหนสำคัญที่สุด? ทำก่อน
   - RGB Effects หรือ Customization System?

---

**รอคำตอบจากคุณแล้วจะเริ่มทำทันที! 🚀**
