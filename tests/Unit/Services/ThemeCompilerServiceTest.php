<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ThemeCompilerService;
use App\Services\ThemeService;
use App\Services\RgbEffectService;
use App\Models\ThemeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * ThemeCompilerService Unit Tests
 *
 * ทดสอบ ThemeCompilerService functionality
 */
class ThemeCompilerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ThemeCompilerService $compiler;
    protected ThemeSetting $themeSetting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new ThemeCompilerService();

        // สร้าง test theme
        $this->themeSetting = ThemeSetting::factory()->create([
            'theme_name' => 'Test Theme',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_compile_theme_css_and_js(): void
    {
        $compiled = $this->compiler->compile($this->themeSetting, true);

        $this->assertIsArray($compiled);
        $this->assertArrayHasKey('css', $compiled);
        $this->assertArrayHasKey('js', $compiled);
        $this->assertNotEmpty($compiled['css']);
        $this->assertNotEmpty($compiled['js']);
    }

    /** @test */
    public function it_caches_compiled_theme(): void
    {
        // Clear cache ก่อน
        Cache::flush();

        // Compile ครั้งแรก (should cache)
        $compiled1 = $this->compiler->compile($this->themeSetting, true);

        // Compile ครั้งที่สอง (should use cache)
        $compiled2 = $this->compiler->compile($this->themeSetting, false);

        // ผลลัพธ์ควรเหมือนกัน
        $this->assertEquals($compiled1['css'], $compiled2['css']);
        $this->assertEquals($compiled1['js'], $compiled2['js']);
    }

    /** @test */
    public function it_can_force_refresh_cache(): void
    {
        // Compile และ cache
        $compiled1 = $this->compiler->compile($this->themeSetting, true);

        // แก้ไข theme
        $this->themeSetting->update(['theme_name' => 'Updated Theme']);

        // Force refresh cache
        $compiled2 = $this->compiler->compile($this->themeSetting, true);

        // Cache key ควรต่างกัน (เพราะ updated_at เปลี่ยน)
        $this->assertNotNull($compiled2);
    }

    /** @test */
    public function it_can_clear_theme_cache(): void
    {
        // Compile และ cache
        $this->compiler->compile($this->themeSetting, true);

        // Clear cache
        $result = $this->compiler->clearCache($this->themeSetting);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_compile_to_files(): void
    {
        $files = $this->compiler->compileToFile($this->themeSetting);

        $this->assertIsArray($files);
        $this->assertArrayHasKey('css_path', $files);
        $this->assertArrayHasKey('js_path', $files);
        $this->assertFileExists($files['css_path']);
        $this->assertFileExists($files['js_path']);

        // Cleanup
        @unlink($files['css_path']);
        @unlink($files['js_path']);
    }

    /** @test */
    public function it_minifies_css_in_production(): void
    {
        // Set to production
        config(['app.env' => 'production']);

        $compiled = $this->compiler->compile($this->themeSetting, true);

        // CSS ควรไม่มี line breaks เยอะ (minified)
        $lineCount = substr_count($compiled['css'], "\n");
        $this->assertLessThan(50, $lineCount); // Minified CSS มี line breaks น้อย
    }

    /** @test */
    public function it_includes_css_variables_in_compilation(): void
    {
        $compiled = $this->compiler->compile($this->themeSetting, true);

        $this->assertStringContainsString(':root', $compiled['css']);
        $this->assertStringContainsString('--arrow-x-', $compiled['css']);
    }

    /** @test */
    public function it_includes_dark_mode_css(): void
    {
        $compiled = $this->compiler->compile($this->themeSetting, true);

        $this->assertStringContainsString('.dark', $compiled['css']);
    }

    /** @test */
    public function it_includes_theme_utilities_in_js(): void
    {
        $compiled = $this->compiler->compile($this->themeSetting, true);

        $this->assertStringContainsString('window.ArrowXTheme', $compiled['js']);
        $this->assertStringContainsString('toggleDarkMode', $compiled['js']);
        $this->assertStringContainsString('isDarkMode', $compiled['js']);
    }

    /** @test */
    public function it_can_warm_up_cache_for_all_themes(): void
    {
        // สร้างหลาย themes
        ThemeSetting::factory()->count(3)->create();

        $count = $this->compiler->warmUpCache();

        $this->assertGreaterThanOrEqual(3, $count);
    }

    /** @test */
    public function it_generates_unique_cache_keys(): void
    {
        $method = new \ReflectionMethod($this->compiler, 'getCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($this->compiler, $this->themeSetting);

        // Update theme
        $this->themeSetting->touch();
        $this->themeSetting->refresh();

        $key2 = $method->invoke($this->compiler, $this->themeSetting);

        // Cache keys ควรต่างกัน
        $this->assertNotEquals($key1, $key2);
    }

    protected function tearDown(): void
    {
        // Cleanup compiled files
        $publicPath = public_path('themes/arrow-x');
        if (is_dir($publicPath)) {
            $files = glob("{$publicPath}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        parent::tearDown();
    }
}
