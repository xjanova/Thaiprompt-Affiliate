<?php

namespace App\Console\Commands\ArrowX;

use App\Services\ThemeCompilerService;
use App\Models\ThemeSetting;
use Illuminate\Console\Command;

/**
 * Artisan Command: Compile Arrow X Theme
 *
 * ใช้สำหรับ compile theme เป็น static files หรือ refresh cache
 */
class CompileThemeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arrowx:compile
                            {--theme= : Theme ID to compile (leave empty for active theme)}
                            {--all : Compile all themes}
                            {--file : Save to file}
                            {--clear-cache : Clear cache before compiling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile Arrow X Theme และบันทึกลง cache หรือ file';

    protected ThemeCompilerService $compiler;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->compiler = app(ThemeCompilerService::class);

        $this->info('🎨 Arrow X Theme Compiler');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Clear cache ถ้าต้องการ
        if ($this->option('clear-cache')) {
            $this->info('🗑️  Clearing theme cache...');
            $this->compiler->clearCache();
            $this->info('✅ Cache cleared');
        }

        // Compile all themes
        if ($this->option('all')) {
            return $this->compileAllThemes();
        }

        // Compile specific theme
        $themeId = $this->option('theme');
        $theme = $themeId
            ? ThemeSetting::find($themeId)
            : ThemeSetting::active();

        if (!$theme) {
            $this->error('❌ Theme not found');
            return self::FAILURE;
        }

        return $this->compileTheme($theme);
    }

    /**
     * Compile single theme
     *
     * @param ThemeSetting $theme
     * @return int
     */
    protected function compileTheme(ThemeSetting $theme): int
    {
        $this->info("📦 Compiling: {$theme->theme_name} (ID: {$theme->id})");

        try {
            if ($this->option('file')) {
                // Compile to file
                $files = $this->compiler->compileToFile($theme);
                $this->info("✅ Compiled to files:");
                $this->line("   CSS: {$files['css_path']}");
                $this->line("   JS:  {$files['js_path']}");
            } else {
                // Compile to cache
                $compiled = $this->compiler->compile($theme, true);
                $cssSize = strlen($compiled['css']);
                $jsSize = strlen($compiled['js']);

                $this->info("✅ Compiled to cache:");
                $this->line("   CSS: " . number_format($cssSize) . " bytes");
                $this->line("   JS:  " . number_format($jsSize) . " bytes");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Compilation failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Compile all themes
     *
     * @return int
     */
    protected function compileAllThemes(): int
    {
        $themes = ThemeSetting::all();

        if ($themes->isEmpty()) {
            $this->warn('⚠️  No themes found');
            return self::SUCCESS;
        }

        $this->info("📦 Compiling {$themes->count()} theme(s)...");
        $this->newLine();

        $success = 0;
        $failed = 0;

        foreach ($themes as $theme) {
            $result = $this->compileTheme($theme);

            if ($result === self::SUCCESS) {
                $success++;
            } else {
                $failed++;
            }

            $this->newLine();
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("✅ Success: {$success}");

        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
