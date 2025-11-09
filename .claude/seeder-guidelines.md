# Laravel Seeder Guidelines for Claude

## 🎯 Smart Seeding Strategy

When creating or updating database seeders, **ALWAYS** follow these principles to prevent data loss and preserve user customizations:

---

## 1. ✅ Check Before Seeding

**NEVER seed blindly**. Always check if data already exists:

```php
public function run(): void
{
    // ❌ BAD: Direct seeding without checking
    Setting::create(['key' => 'app_name', 'value' => 'MyApp']);

    // ✅ GOOD: Check first
    if (!Setting::where('key', 'app_name')->exists()) {
        Setting::create(['key' => 'app_name', 'value' => 'MyApp']);
    }
}
```

---

## 2. 🔄 Update Only Missing Keys

When database schema changes (new columns/keys added), **ONLY add missing keys** without overwriting existing values:

```php
public function run(): void
{
    $defaultSettings = [
        'app_name' => 'MyApp',
        'app_logo' => '/logo.png',
        'new_feature_enabled' => true,  // New key added
    ];

    // ❌ BAD: Overwrite everything
    foreach ($defaultSettings as $key => $value) {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    // ✅ GOOD: Add only missing keys
    foreach ($defaultSettings as $key => $value) {
        if (!Setting::where('key', $key)->exists()) {
            Setting::create(['key' => $key, 'value' => $value]);
        }
    }
}
```

---

## 3. 🛡️ Protect User Data with Early Exit

If core settings exist, assume user has customized them and **skip the entire seeder**:

```php
public function run(): void
{
    // Check for a core setting that indicates seeding was done
    if (Setting::where('key', 'app_name')->exists()) {
        $this->command->warn('⚠️  Settings already exist!');
        $this->command->info('   Skipping to preserve customizations.');
        return;  // Early exit
    }

    // Seed all default settings
    $this->seedDefaultSettings();
}
```

---

## 4. 🔧 Smart Merge Strategy

For complex data (JSON settings, arrays), **merge** instead of replace:

```php
public function run(): void
{
    $existingSetting = WindowsUiSetting::where('key', 'menu_items')->first();

    if ($existingSetting) {
        $existingValue = json_decode($existingSetting->value, true);
        $newDefaults = ['item1' => 'value1', 'item2' => 'value2'];

        // ❌ BAD: Replace everything
        $existingSetting->value = json_encode($newDefaults);

        // ✅ GOOD: Merge, keeping existing values
        $merged = array_merge($newDefaults, $existingValue);
        $existingSetting->value = json_encode($merged);
        $existingSetting->save();
    } else {
        // Create new if doesn't exist
        WindowsUiSetting::create([
            'key' => 'menu_items',
            'value' => json_encode($newDefaults),
        ]);
    }
}
```

---

## 5. 📊 Incremental Seeding Pattern

For settings with versions, use **incremental seeding**:

```php
public function run(): void
{
    $currentVersion = Setting::where('key', 'seeder_version')->value('value') ?? 0;

    // Version 1: Initial settings
    if ($currentVersion < 1) {
        $this->seedVersion1();
        Setting::updateOrCreate(['key' => 'seeder_version'], ['value' => 1]);
    }

    // Version 2: Add new settings only
    if ($currentVersion < 2) {
        $this->seedVersion2NewKeysOnly();
        Setting::updateOrCreate(['key' => 'seeder_version'], ['value' => 2]);
    }
}

private function seedVersion1(): void
{
    // Initial complete seed
    Setting::create(['key' => 'app_name', 'value' => 'MyApp']);
    Setting::create(['key' => 'app_logo', 'value' => '/logo.png']);
}

private function seedVersion2NewKeysOnly(): void
{
    // Add ONLY new keys introduced in version 2
    if (!Setting::where('key', 'new_feature')->exists()) {
        Setting::create(['key' => 'new_feature', 'value' => true]);
    }
}
```

---

## 6. 🎯 WindowsUiSetting Specific Rules

For `WindowsUiSetting` model with `set()` method:

```php
public function run(): void
{
    // Define all default settings
    $defaults = [
        'taskbar_position' => ['value' => 'top', 'type' => 'string'],
        'taskbar_height' => ['value' => 60, 'type' => 'integer'],
        'new_clock_format' => ['value' => '24h', 'type' => 'string'], // New!
    ];

    // Check if seeding was done before
    $coreKeysExist = WindowsUiSetting::where('key', 'taskbar_position')->exists();

    if ($coreKeysExist) {
        // Add ONLY missing keys
        foreach ($defaults as $key => $config) {
            if (!WindowsUiSetting::where('key', $key)->exists()) {
                WindowsUiSetting::set($key, $config['value'], $config['type']);
                $this->command->info("   ➕ Added new setting: {$key}");
            }
        }
        $this->command->info('✅ Updated with new settings only.');
    } else {
        // First time seeding - create all
        foreach ($defaults as $key => $config) {
            WindowsUiSetting::set($key, $config['value'], $config['type']);
        }
        $this->command->info('✅ All settings seeded successfully!');
    }
}
```

---

## 7. 🚨 When to Skip vs Update

| Scenario | Action | Reason |
|----------|--------|--------|
| Core settings exist | **SKIP entire seeder** | Preserve all user customizations |
| New column added | **Add missing keys only** | Extend functionality without data loss |
| Bug fix in default value | **SKIP** | User may have corrected it already |
| Critical security update | **Warn + Manual** | Require admin intervention |
| Schema migration | **Migration file** | Not a seeder responsibility |

---

## 8. 📝 Seeder Best Practices Checklist

Before committing any seeder:

- [ ] Does it check if data exists before inserting?
- [ ] Does it preserve user customizations?
- [ ] Does it handle new keys gracefully?
- [ ] Does it show clear console messages (warn/info)?
- [ ] Does it have an early exit strategy?
- [ ] Is it idempotent (safe to run multiple times)?
- [ ] Does it log what was added vs skipped?

---

## 9. 💡 Example: Complete Smart Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\WindowsUiSetting;
use Illuminate\Database\Seeder;

class WindowsUiSeeder extends Seeder
{
    private array $defaultSettings = [
        // Core settings (check these for existence)
        'windows_taskbar_position' => ['value' => 'top', 'type' => 'string', 'core' => true],
        'windows_taskbar_height' => ['value' => 60, 'type' => 'integer', 'core' => true],

        // New settings (can be added incrementally)
        'millennium_clock_format' => ['value' => '24h', 'type' => 'string', 'core' => false],
        'millennium_clock_style' => ['value' => 'digital', 'type' => 'string', 'core' => false],
    ];

    public function run(): void
    {
        // Check if core settings exist
        $coreSettings = collect($this->defaultSettings)
            ->filter(fn($config) => $config['core'] ?? false)
            ->keys();

        $existingCoreSettings = WindowsUiSetting::whereIn('key', $coreSettings)->count();

        if ($existingCoreSettings > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install - seed everything
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all Windows UI settings...');

        foreach ($this->defaultSettings as $key => $config) {
            WindowsUiSetting::set($key, $config['value'], $config['type']);
        }

        $count = count($this->defaultSettings);
        $this->command->info("✅ Seeded {$count} settings successfully!");
    }

    /**
     * Update mode - add only missing keys
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing settings detected!');
        $this->command->info('   Running in update mode (add missing keys only)...');

        $added = 0;
        $skipped = 0;

        foreach ($this->defaultSettings as $key => $config) {
            if (!WindowsUiSetting::where('key', $key)->exists()) {
                WindowsUiSetting::set($key, $config['value'], $config['type']);
                $this->command->info("   ➕ Added: {$key}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new settings.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing settings (preserved).");
        }
    }
}
```

---

## 🔐 Security Reminder

**NEVER** automatically update:
- User credentials
- API keys/tokens
- Security settings
- Custom business logic values

These require **manual review** by the administrator.

---

## 📚 Related Laravel Concepts

- `firstOrCreate()` - Find or create (no update)
- `updateOrCreate()` - Find and update or create (overwrites!)
- `firstOr()` - With callback for custom logic
- Database transactions for atomic operations
- Schema migrations for structural changes

---

**Last Updated**: 2025-01-09
**Author**: Claude (AI Assistant)
**Version**: 1.0
