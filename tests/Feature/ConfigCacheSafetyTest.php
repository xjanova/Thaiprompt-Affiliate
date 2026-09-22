<?php

namespace Tests\Feature;

use App\Support\EnvFileConfigCache;
use Tests\TestCase;

/**
 * ของที่ต้องจริงเสมอเมื่อ prod รันด้วย config cache
 *
 * 🐢 (2026-09-22) prod ไม่เคยมี config cache เลย — deploy.sh STEP 15 cache แล้ว
 *    STEP 18 `composer dump-autoload` (hook ComposerScripts::clearCompiled) ลบทิ้งทุกรอบ
 *    ทุกคำขอเลยเสีย ~100ms อ่าน .env (42ms) + ไฟล์ config (57ms) ใหม่หมด
 *
 * พอเปิด cache ได้ มีของ 3 อย่างที่ต้องไม่หลุด:
 *   1. ห้ามเรียก env() นอกไฟล์ config — ตอน config ถูก cache Laravel ไม่โหลด .env
 *      env() จึงคืน null เงียบ ๆ (เคยมี 60 จุด เช่นที่เก็บไฟล์เสียงแม่หมอ, HF token)
 *   2. หน้าแอดมินที่เขียน .env ต้องลบ config cache ไม่งั้นค่าใหม่ไม่มีผลจนกว่า deploy รอบหน้า
 *   3. deploy.sh ต้อง cache config อีกรอบหลัง dump-autoload และก่อน restart queue worker
 *
 * ไม่ใช้ DB — ตรวจซอร์สโค้ดกับค่า config ล้วน ๆ
 */
class ConfigCacheSafetyTest extends TestCase
{
    /**
     * ห้ามมี env('...') ในโค้ดแอป / route / bootstrap / วิว
     *
     * database/ ไม่ตรวจ — seeder กับ migration รันใน deploy หลัง STEP 8 ล้าง config แล้ว
     */
    public function test_no_env_calls_outside_config_files(): void
    {
        $offenders = [];

        foreach ($this->phpFiles([app_path(), base_path('routes'), base_path('bootstrap')]) as $file) {
            if (str_contains($file, DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            foreach ($this->envCallLines((string) file_get_contents($file)) as $line) {
                $offenders[] = $this->relative($file).':'.$line;
            }
        }

        // วิว Blade — CSS `env(safe-area-inset-bottom)` ไม่มีเครื่องหมายคำพูด จึงไม่โดน pattern นี้
        foreach ($this->phpFiles([resource_path('views')]) as $file) {
            foreach (preg_split('/\R/', (string) file_get_contents($file)) as $i => $text) {
                if (preg_match('/(?<![\w>:$])env\(\s*[\'"]/', $text)) {
                    $offenders[] = $this->relative($file).':'.($i + 1);
                }
            }
        }

        $this->assertSame([], $offenders,
            "เรียก env() นอกไฟล์ config — prod รัน config:cache แล้วค่านี้จะเป็น null\n"
            ."ย้ายไปไว้ใน config/*.php แล้วอ่านด้วย config() แทน:\n".implode("\n", $offenders)
        );
    }

    /**
     * ค่าที่ย้ายจาก env() ในโค้ดมาไว้ใน config/services.php ต้องมีคีย์ครบ
     */
    public function test_moved_env_values_are_reachable_through_config(): void
    {
        $services = config('services');

        foreach (['huggingface.token', 'github.token', 'github.webhook_secret', 'tavily.api_key',
            'brave_search.api_key', 'gemini.api_key', 'openai.api_key', 'anthropic.model',
            'nongying_chat.provider', 'google.tts_api_key', 'google.cloud_project_id'] as $key) {
            $this->assertTrue(\Illuminate\Support\Arr::has($services, $key), "config('services.{$key}') หายไป");
        }

        $this->assertNotEmpty(config('services.gemini.tts_model'));
        $this->assertNotEmpty(config('services.nongying_chat.provider'));

        // ที่เก็บไฟล์เสียงคำทำนาย — FortuneVoiceStorageService::envDefaults() อ่านทั้งก้อน
        $expected = [
            'r2' => ['account_id', 'access_key_id', 'secret_access_key', 'bucket', 'public_url'],
            's3' => ['access_key_id', 'secret_access_key', 'region', 'bucket', 'endpoint', 'public_url'],
            'gcs' => ['credentials_path', 'bucket', 'public_url'],
            'firebase' => ['credentials_path', 'bucket', 'public_url'],
        ];
        foreach ($expected as $driver => $keys) {
            $this->assertSame($keys, array_keys((array) config("services.fortune_voice_storage.{$driver}")),
                "config('services.fortune_voice_storage.{$driver}') คีย์ไม่ตรงกับที่ FortuneVoiceStorageService ใช้");
        }
    }

    /**
     * EnvFileConfigCache::forget() ต้องลบไฟล์ config cache จริง และไม่พังถ้าไม่มีไฟล์
     */
    public function test_forget_removes_the_cached_config_file(): void
    {
        // ชี้ไฟล์ cache ไปที่อื่น — ห้ามเขียน bootstrap/cache/config.php ของจริง
        // ถ้าเทสต์ล้มกลางทาง ไฟล์นั้นจะทำให้เทสต์ตัวอื่นรันด้วย config ค้าง
        // ใช้พาธสัมพัทธ์ — Laravel นับแค่ / กับ \ เป็นพาธเต็ม (D:\ บน Windows จะถูกต่อท้าย base path)
        $relative = 'storage/framework/cache/config-cache-test-'.uniqid().'.php';
        $path = base_path($relative);
        $_SERVER['APP_CONFIG_CACHE'] = $relative;

        try {
            $this->assertSame($path, app()->getCachedConfigPath());

            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, '<?php return [];');
            EnvFileConfigCache::forget();
            $this->assertFileDoesNotExist($path);

            EnvFileConfigCache::forget(); // ไม่มีไฟล์แล้วต้องไม่ error
            $this->assertFileDoesNotExist($path);
        } finally {
            unset($_SERVER['APP_CONFIG_CACHE']);
            @unlink($path);
        }
    }

    /**
     * ทุกจุดที่เขียน .env ในหน้าแอดมินต้องตามด้วยการลบ config cache
     */
    public function test_admin_env_writers_forget_the_config_cache(): void
    {
        foreach (['SecurityController', 'SettingsController'] as $controller) {
            $source = (string) file_get_contents(app_path("Http/Controllers/Admin/{$controller}.php"));

            $writes = substr_count($source, 'file_put_contents($envPath');
            $forgets = substr_count($source, 'EnvFileConfigCache::forget()');

            $this->assertGreaterThan(0, $writes, "{$controller} ไม่เขียน .env แล้ว — เอาออกจากเทสต์นี้ได้");
            $this->assertSame($writes, $forgets,
                "{$controller} เขียน .env {$writes} จุด แต่ลบ config cache {$forgets} จุด — ค่าที่แอดมินบันทึกจะไม่มีผล");
        }
    }

    /**
     * deploy.sh: config:cache รอบสองต้องอยู่หลัง composer dump-autoload และก่อน restart queue worker
     */
    public function test_deploy_script_recaches_config_after_dump_autoload(): void
    {
        $script = (string) file_get_contents(base_path('deploy.sh'));

        $dump = strpos($script, "\ncomposer dump-autoload --optimize");
        $this->assertNotFalse($dump, 'หา composer dump-autoload ใน deploy.sh ไม่เจอ');

        $recache = strpos($script, "\nif php artisan config:cache", $dump);
        $this->assertNotFalse($recache, 'deploy.sh ไม่ cache config ซ้ำหลัง dump-autoload — prod จะไม่มี config cache อีก');

        $workerRestart = strpos($script, 'sudo systemctl restart fortune-queue-worker.service', $dump);
        $this->assertNotFalse($workerRestart);
        $this->assertLessThan($workerRestart, $recache, 'ต้อง cache config ก่อน restart worker — worker อ่าน config ตอนเริ่ม');
    }

    /**
     * @param  array<int, string>  $dirs
     * @return array<int, string>
     */
    private function phpFiles(array $dirs): array
    {
        $files = [];
        foreach ($dirs as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
                    $files[] = $f->getPathname();
                }
            }
        }
        sort($files);

        return $files;
    }

    /**
     * บรรทัดที่เรียกฟังก์ชัน env() จริง — ข้ามคอมเมนต์ และข้าม ->env( / ::env( / function env(
     *
     * @return array<int, int>
     */
    private function envCallLines(string $code): array
    {
        $tokens = token_get_all($code);
        $lines = [];
        $prev = null;

        foreach ($tokens as $i => $token) {
            if (! is_array($token)) {
                $prev = $token;

                continue;
            }
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $isEnvName = ($token[0] === T_STRING && strtolower($token[1]) === 'env')
                || ($token[0] === T_NAME_FULLY_QUALIFIED && strtolower($token[1]) === '\\env');

            if ($isEnvName && ! (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true))) {
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        continue;
                    }
                    if ($tokens[$j] === '(') {
                        $lines[] = $token[2];
                    }
                    break;
                }
            }

            $prev = $token;
        }

        return $lines;
    }

    private function relative(string $file): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
    }
}
