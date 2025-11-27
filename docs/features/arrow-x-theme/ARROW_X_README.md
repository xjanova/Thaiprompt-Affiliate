# 🚀 Arrow X Theme System

**Modern, Customizable, Performance-First Theme System สำหรับ Laravel 11**

Version: 1.0.0 | Built with: Tailwind CSS + Alpine.js + Laravel 11

---

## 📖 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Components](#components)
5. [Services](#services)
6. [Configuration](#configuration)
7. [Usage Examples](#usage-examples)
8. [Performance](#performance)
9. [Artisan Commands](#artisan-commands)
10. [Testing](#testing)

---

## 🎯 Overview

**Arrow X Theme System** คือระบบ theme ที่ปรับแต่งได้ครบวงจรสำหรับ Laravel applications พร้อมด้วย:

- **17 Pre-built Components** - Card, Button, Badge, Alert, Form, Modal, Table, Navigation
- **RGB Effects System** - 9 animation types สำหรับ active states
- **Multi-Language Support** - 14 ภาษา พร้อม auto-translate
- **Dark/Light Mode** - Full support ทุก component
- **Cache Management** - Smart caching สำหรับ performance
- **Admin Dashboard** - จัดการ theme แบบ UI

---

## ✨ Features

### 🎨 UI Components (17 Components)

**Cards:**
- `arrow-x.card.stat` - Stat card พร้อม icon และ trend
- `arrow-x.card.info` - Info card ทั่วไป
- `arrow-x.card.gradient` - Gradient background card

**Buttons:**
- `arrow-x.button` - Modern button (5 variants, 6 colors, 5 sizes)

**Badges & Alerts:**
- `arrow-x.badge` - Badge/Tag component
- `arrow-x.alert` - Alert/Notification box

**Forms:**
- `arrow-x.form.input` - Input field
- `arrow-x.form.select` - Select dropdown
- `arrow-x.form.checkbox` - Checkbox

**Navigation:**
- `arrow-x.sidebar` - Collapsible sidebar
- `arrow-x.sidebar.item` - Sidebar menu item
- `arrow-x.navbar` - Top navigation bar
- `arrow-x.navbar.notification` - Notification dropdown
- `arrow-x.navbar.user-menu` - User menu dropdown

**Data Display:**
- `arrow-x.table` - Data table
- `arrow-x.modal` - Modal dialog

**Utilities:**
- `arrow-x.theme-styles` - Theme CSS/JS injection
- `arrow-x.language-switcher` - Language switcher (3 variants)

### ⚡ Advanced Features

- **RGB Effects** - 9 animation types (rainbow, wave, pulse, glow, breathing, slide, rotate, flash, static)
- **Cache System** - TTL-based caching (1 hour default)
- **Minification** - Auto minify CSS/JS in production
- **Static Export** - Export compiled CSS/JS to files
- **Multi-Language** - 14 languages support
- **Dark Mode** - Automatic dark mode utilities
- **Responsive** - Mobile-first design

---

## 📦 Installation

### Requirements

- PHP 8.1+
- Laravel 11.x
- Node.js 18+
- Tailwind CSS 3.4+
- Alpine.js 3.13+

### Steps

1. **Database Migration:**

```bash
php artisan migrate
```

2. **Seed Default Theme:**

```bash
php artisan db:seed --class=ArrowXThemeSeeder
```

3. **Compile Theme:**

```bash
php artisan arrowx:compile
```

4. **Done!** Theme พร้อมใช้งาน

---

## 🧩 Components

### Button Component

```blade
<x-arrow-x.button variant="gradient" color="purple" size="md" icon="fa-rocket">
    Click Me
</x-arrow-x.button>
```

**Props:**
- `variant`: primary, secondary, gradient, outline, ghost
- `color`: purple, blue, green, orange, pink, red
- `size`: xs, sm, md, lg, xl
- `icon`: Font Awesome icon class
- `loading`: true/false
- `disabled`: true/false

### Card Components

```blade
{{-- Stat Card --}}
<x-arrow-x.card.stat
    title="ผู้ใช้ทั้งหมด"
    value="12,543"
    icon="fa-users"
    color="purple"
    trend="up"
    trendValue="+12%"
/>

{{-- Info Card --}}
<x-arrow-x.card.info title="Card Title">
    <p>Card content here...</p>
</x-arrow-x.card.info>

{{-- Gradient Card --}}
<x-arrow-x.card.gradient title="Featured" gradient="purple" :pattern="true">
    <p>Gradient background card</p>
</x-arrow-x.card.gradient>
```

### Form Components

```blade
{{-- Input --}}
<x-arrow-x.form.input
    label="ชื่อผู้ใช้"
    name="username"
    icon="fa-user"
    :required="true"
/>

{{-- Select --}}
<x-arrow-x.form.select
    label="ประเภท"
    name="type"
    :options="['1' => 'Option 1', '2' => 'Option 2']"
/>

{{-- Checkbox --}}
<x-arrow-x.form.checkbox
    label="ยอมรับเงื่อนไข"
    name="terms"
    description="กรุณาอ่านเงื่อนไขก่อนดำเนินการ"
/>
```

### Navigation Components

```blade
{{-- Sidebar --}}
<x-arrow-x.sidebar title="My App">
    <x-slot:menu>
        <x-arrow-x.sidebar.item icon="fa-home" href="/" :active="true">
            หน้าแรก
        </x-arrow-x.sidebar.item>

        <x-arrow-x.sidebar.item icon="fa-users" href="/users" badge="12" badgeColor="purple">
            ผู้ใช้
        </x-arrow-x.sidebar.item>
    </x-slot:menu>
</x-arrow-x.sidebar>

{{-- Navbar --}}
<x-arrow-x.navbar>
    <x-slot:right>
        <x-arrow-x.navbar.notification :count="5" />
        <x-arrow-x.navbar.user-menu name="John Doe" role="Admin" />
    </x-slot:right>
</x-arrow-x.navbar>
```

### Language Switcher

```blade
{{-- Dropdown Style --}}
<x-arrow-x.language-switcher variant="dropdown" />

{{-- Flag Grid Style --}}
<x-arrow-x.language-switcher variant="flags" />

{{-- Text List Style --}}
<x-arrow-x.language-switcher variant="text" />
```

---

## 🛠️ Services

### ThemeCompilerService

```php
use App\Services\ThemeCompilerService;

$compiler = app(ThemeCompilerService::class);

// Compile theme (auto-cache)
$compiled = $compiler->compile($themeSetting);

// Force refresh cache
$compiled = $compiler->compile($themeSetting, true);

// Export to static files
$files = $compiler->compileToFile($themeSetting);

// Clear cache
$compiler->clearCache($themeSetting);

// Warm up cache
$compiler->warmUpCache();
```

### ComponentService

```php
use App\Services\ComponentService;

$service = app(ComponentService::class);

// Render button dynamically
$html = $service->button('Click Me', [
    'variant' => 'gradient',
    'color' => 'purple',
]);

// Check component existence
if ($service->exists('arrow-x.button')) {
    // Component exists
}

// Get available components
$components = $service->getAvailableComponents();
```

### TranslationService

```php
use App\Services\TranslationService;

$translator = app(TranslationService::class);

// Translate text
$translated = $translator->translate('Hello', 'th'); // "สวัสดี"

// Batch translate
$texts = ['Hello', 'World'];
$translated = $translator->translateBatch($texts, 'ja');

// Detect language
$lang = $translator->detectLanguage('สวัสดี'); // "th"
```

---

## ⚙️ Configuration

### Theme Settings (Database)

ตั้งค่าผ่าน Admin UI: `/admin/arrow-x-theme`

**General Settings:**
- Brand Name
- Logo/Favicon
- Layout (sidebar width, navbar height)
- Opacity settings

**Color Settings:**
- Primary Gradient (3 colors)
- Secondary Gradient
- Status Colors (success, warning, error, info)

**RGB Effects:**
- Animation Type
- Target Element
- Trigger State
- Duration
- Intensity

**Typography:**
- Font Families
- Font Sizes
- Line Heights

### Environment Variables

```env
# Google Translate API (Optional)
GOOGLE_TRANSLATE_API_KEY=your-api-key-here
```

---

## 📝 Usage Examples

### Complete Dashboard Example

```blade
<!DOCTYPE html>
<html lang="th">
<head>
    <title>Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Arrow X Theme --}}
    <x-arrow-x.theme-styles />
</head>
<body class="bg-gray-50 dark:bg-gray-900">

    {{-- Sidebar --}}
    <x-arrow-x.sidebar title="My App">
        <x-slot:menu>
            <x-arrow-x.sidebar.item icon="fa-home" href="/" :active="true">
                Dashboard
            </x-arrow-x.sidebar.item>
        </x-slot:menu>
    </x-arrow-x.sidebar>

    {{-- Main Content --}}
    <main class="ml-72 p-8">
        {{-- Navbar --}}
        <x-arrow-x.navbar>
            <x-slot:right>
                <x-arrow-x.language-switcher />
                <x-arrow-x.navbar.notification :count="5" />
                <x-arrow-x.navbar.user-menu />
            </x-slot:right>
        </x-arrow-x.navbar>

        {{-- Stats --}}
        <div class="grid grid-cols-4 gap-6 mt-8">
            <x-arrow-x.card.stat
                title="Users"
                value="1,234"
                icon="fa-users"
                trend="up"
                trendValue="+12%"
            />
        </div>
    </main>

</body>
</html>
```

---

## ⚡ Performance

### Benchmarking

```bash
php artisan arrowx:benchmark --iterations=10
```

**Expected Results:**
- First Compile (Cache): < 500ms
- Cached Compile: < 100ms
- Force Refresh (Avg): < 500ms
- Compile to Files: < 1000ms

**Cache Improvement:** ~90%+

### Optimization Tips

1. **Enable Cache in Production:**
   ```php
   // Automatically enabled
   // TTL: 1 hour
   ```

2. **Use Static Files for Better Performance:**
   ```bash
   php artisan arrowx:compile --file
   ```

3. **Warm Up Cache After Deployment:**
   ```bash
   php artisan arrowx:warmup
   ```

4. **Clear Old Cache:**
   ```bash
   php artisan arrowx:clear
   ```

---

## 🎯 Artisan Commands

### Theme Management

```bash
# Compile theme
php artisan arrowx:compile
php artisan arrowx:compile --theme=1
php artisan arrowx:compile --all
php artisan arrowx:compile --file

# Clear cache
php artisan arrowx:clear
php artisan arrowx:clear --files

# Warm up cache
php artisan arrowx:warmup

# Benchmark performance
php artisan arrowx:benchmark
php artisan arrowx:benchmark --iterations=20
```

### Translation Management

```bash
# Clear translation cache
php artisan arrowx:clear-translations
php artisan arrowx:clear-translations --lang=en
php artisan arrowx:clear-translations --all
```

---

## 🧪 Testing

### Run Tests

```bash
# All tests
php artisan test

# Specific test
php artisan test --filter=ThemeCompilerServiceTest
```

### Test Coverage

```bash
php artisan test --coverage
```

**Test Files:**
- `tests/Unit/Services/ThemeCompilerServiceTest.php`
- `tests/Unit/Services/ComponentServiceTest.php`

---

## 📚 Resources

- **Demo:** `/demo/components`
- **Admin:** `/admin/arrow-x-theme`
- **Documentation:** `ARROW_X_THEME_PLAN.md`

---

## 🤝 Support

**ติดปัญหา?**
1. ตรวจสอบ logs: `storage/logs/laravel.log`
2. Clear cache: `php artisan arrowx:clear`
3. Re-compile: `php artisan arrowx:compile`

---

## 📄 License

MIT License

---

**Built with ❤️ by Arrow X Team**

Version 1.0.0 | 2025-11-15
