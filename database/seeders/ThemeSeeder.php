<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Models\ThemePreset;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed theme presets first
        $this->seedThemePresets();

        // Create default system theme
        $this->seedDefaultTheme();
    }

    /**
     * Seed theme presets
     */
    protected function seedThemePresets()
    {
        // Check if theme presets already exist
        $existingPresetsCount = ThemePreset::count();

        if ($existingPresetsCount > 0) {
            $this->command->warn('⚠️  Existing theme presets detected!');
            $this->command->info('   Running in UPDATE mode (adding missing presets only)...');

            $presets = ThemePreset::defaultPresets();
            $added = 0;
            $skipped = 0;

            foreach ($presets as $presetData) {
                if (!ThemePreset::where('name', $presetData['name'])->exists()) {
                    ThemePreset::create($presetData);
                    $this->command->info("   ➕ Added preset: {$presetData['display_name']}");
                    $added++;
                } else {
                    $skipped++;
                }
            }

            if ($added > 0) {
                $this->command->info("✅ Added {$added} new theme presets.");
            }

            if ($skipped > 0) {
                $this->command->info("   ⏭️  Skipped {$skipped} existing presets (preserved).");
            }
        } else {
            // Fresh install - create all presets
            $this->command->info('🌱 Fresh install: Seeding all theme presets...');

            $presets = ThemePreset::defaultPresets();

            foreach ($presets as $presetData) {
                ThemePreset::create($presetData);
                $this->command->info("   Created preset: {$presetData['display_name']}");
            }

            $this->command->info('✅ Theme presets seeded successfully!');
        }
    }

    /**
     * Seed default theme
     */
    protected function seedDefaultTheme()
    {
        // Check if default theme exists
        if (Theme::where('is_system', true)->exists()) {
            $this->command->info('Default system theme already exists. Skipping...');
            return;
        }

        // Create default theme from preset
        $defaultPreset = ThemePreset::where('name', 'line_oa_default')->first();

        if (!$defaultPreset) {
            $this->command->error('Default preset not found. Please check ThemePreset::defaultPresets()');
            return;
        }

        $defaultConfig = $defaultPreset->config;

        $theme = Theme::create([
            'name' => 'line_oa_default',
            'display_name' => 'Line OA (Default)',
            'description' => 'ธีมเริ่มต้นของระบบ แบบ Line Official Account สีเขียว Line สดใส',
            'is_default' => true,
            'is_system' => true,
            'is_active' => true,
            'colors' => $defaultConfig['colors'],
            'dark_colors' => $defaultConfig['dark_colors'],
            'typography' => $defaultConfig['typography'],
            'spacing' => $defaultConfig['spacing'],
            'borders' => $defaultConfig['borders'],
            'shadows' => $defaultConfig['shadows'],
            'animations' => $defaultConfig['animations'],
        ]);

        $defaultPreset->incrementUsage();

        $this->command->info("Created default system theme: {$theme->display_name}");
    }
}
