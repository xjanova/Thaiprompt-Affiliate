<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Affiliate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\Rules\Password;

class SetupController extends Controller
{

    /**
     * Show the setup wizard
     */
    public function index()
    {
        // Check if system is already set up
        if ($this->isSetupCompleted()) {
            return redirect()->route('login')
                ->with('info', 'ระบบติดตั้งเรียบร้อยแล้ว กรุณาเข้าสู่ระบบ');
        }

        return view('setup.wizard');
    }

    /**
     * Check system requirements
     */
    public function checkRequirements()
    {
        $requirements = [
            'php_version' => [
                'required' => '8.1.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.1.0', '>=')
            ],
            'extensions' => [
                'BCMath' => extension_loaded('bcmath'),
                'Ctype' => extension_loaded('ctype'),
                'JSON' => extension_loaded('json'),
                'Mbstring' => extension_loaded('mbstring'),
                'OpenSSL' => extension_loaded('openssl'),
                'PDO' => extension_loaded('pdo'),
                'PDO MySQL' => extension_loaded('pdo_mysql'),
                'Tokenizer' => extension_loaded('tokenizer'),
                'XML' => extension_loaded('xml'),
                'cURL' => extension_loaded('curl'),
                'Fileinfo' => extension_loaded('fileinfo'),
                'GD' => extension_loaded('gd'),
                'Zip' => extension_loaded('zip'),
            ],
            'permissions' => [
                'storage/' => is_writable(storage_path()),
                'bootstrap/cache/' => is_writable(base_path('bootstrap/cache')),
                'storage/app/' => is_writable(storage_path('app')),
                'storage/framework/' => is_writable(storage_path('framework')),
                'storage/logs/' => is_writable(storage_path('logs')),
            ],
            'environment' => [
                'Composer' => $this->checkComposer(),
                '.env file' => File::exists(base_path('.env')),
                'APP_KEY set' => !empty(config('app.key')),
            ]
        ];

        // Check if all requirements passed
        $requirements['all_passed'] =
            $requirements['php_version']['passed'] &&
            !in_array(false, $requirements['extensions']) &&
            !in_array(false, $requirements['permissions']) &&
            !in_array(false, $requirements['environment']);

        return response()->json($requirements);
    }

    /**
     * Test database connection
     */
    public function testDatabase(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            // Test MySQL connection
            $dsn = "mysql:host={$request->host};port={$request->port}";
            $pdo = new \PDO($dsn, $request->username, $request->password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Try to create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$request->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Update .env file
            $this->updateEnvFile([
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $request->host,
                'DB_PORT' => $request->port,
                'DB_DATABASE' => $request->database,
                'DB_USERNAME' => $request->username,
                'DB_PASSWORD' => $request->password,
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'เชื่อมต่อ database สำเร็จและบันทึกการตั้งค่าแล้ว'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ database ได้: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Install Composer dependencies
     */
    public function installDependencies()
    {
        try {
            // Check if vendor directory exists
            if (File::exists(base_path('vendor')) && File::exists(base_path('vendor/autoload.php'))) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dependencies ติดตั้งแล้ว'
                ]);
            }

            // Run composer install
            $result = Process::timeout(600)->run('composer install --no-dev --optimize-autoloader --no-interaction');

            if ($result->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ติดตั้ง dependencies สำเร็จ'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในการติดตั้ง: ' . $result->errorOutput()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run database migrations
     */
    public function runMigrations()
    {
        try {
            // Clear config cache first
            Artisan::call('config:clear');

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            return response()->json([
                'success' => true,
                'message' => 'สร้าง database tables สำเร็จ',
                'output' => Artisan::output()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Super Admin user
     */
    public function createAdmin(Request $request)
    {
        // Check if system is already set up
        if ($this->isSetupCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'ระบบติดตั้งเรียบร้อยแล้ว'
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::beginTransaction();

        try {
            // Create super admin user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'super_admin',
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ]);

            // Create affiliate account for super admin
            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'referral_code' => Affiliate::generateReferralCode(),
                'level' => 1,
                'status' => 'active',
            ]);

            $user->update(['affiliate_id' => $affiliate->id]);

            // Create default settings
            $this->createDefaultSettings();

            // Mark setup as completed
            File::put(storage_path('app/.setup_completed'), now()->toString());

            // Update .env to production mode if needed
            if (app()->environment('local')) {
                $this->updateEnvFile([
                    'APP_ENV' => 'production',
                    'APP_DEBUG' => 'false',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สร้างบัญชี Super Admin สำเร็จ! กำลังนำคุณไปยังหน้าเข้าสู่ระบบ...'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system info
     */
    public function info()
    {
        return response()->json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_version' => config('version.current'),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'setup_completed' => $this->isSetupCompleted(),
        ]);
    }

    /**
     * Check if setup is completed
     */
    protected function isSetupCompleted(): bool
    {
        // Check if setup completed flag exists
        if (File::exists(storage_path('app/.setup_completed'))) {
            return true;
        }

        // Check if super admin exists
        if (User::where('role', 'super_admin')->exists()) {
            // Auto-create flag if admin exists but flag doesn't
            File::put(storage_path('app/.setup_completed'), now()->toString());
            return true;
        }

        return false;
    }

    /**
     * Check if Composer is available
     */
    protected function checkComposer(): bool
    {
        $result = Process::run('composer --version');
        return $result->successful();
    }

    /**
     * Update .env file
     */
    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            // Escape special characters in value
            $value = str_replace(['\\', '$', '"'], ['\\\\', '\\$', '\\"'], $value);

            // Check if key exists
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Add new key
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * Create default settings
     */
    protected function createDefaultSettings(): void
    {
        $defaults = [
            'app_name' => ['value' => 'TP-Affiliate', 'type' => 'string', 'group' => 'general'],
            'app_installed' => ['value' => true, 'type' => 'boolean', 'group' => 'general'],
            'commission_rate' => ['value' => 10, 'type' => 'integer', 'group' => 'affiliate'],
            'multi_level_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'affiliate'],
            'timezone' => ['value' => 'Asia/Bangkok', 'type' => 'string', 'group' => 'general'],
            'locale' => ['value' => 'th', 'type' => 'string', 'group' => 'general'],
            'currency' => ['value' => 'THB', 'type' => 'string', 'group' => 'general'],
        ];

        foreach ($defaults as $key => $config) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'group' => $config['group'],
                ]
            );
        }
    }
}
