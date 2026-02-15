<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * ตัวควบคุมสำหรับการติดตั้งระบบครั้งแรก
 *
 * ใช้สำหรับสร้างบัญชี Super Admin ครั้งแรกเมื่อติดตั้งระบบ
 * รองรับการติดตั้งแบบ Step-by-Step Wizard
 */
class SetupController extends Controller
{
    /**
     * ขั้นตอนการติดตั้งทั้งหมด
     *
     * @var array
     */
    protected $steps = [
        1 => 'welcome',      // ยินดีต้อนรับ
        2 => 'requirements', // ตรวจสอบ requirements
        3 => 'database',     // ตั้งค่าฐานข้อมูล
        4 => 'admin',        // สร้าง Super Admin
        5 => 'complete',     // เสร็จสิ้น
    ];

    /**
     * แสดงหน้าติดตั้งระบบ (Wizard)
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        // ตรวจสอบว่ามี super admin อยู่แล้วหรือไม่
        if ($this->isSetupCompleted()) {
            return redirect()->route('home')->with('info', 'ระบบได้ติดตั้งเรียบร้อยแล้ว');
        }

        // ดึงข้อมูลสถานะระบบ
        $systemStatus = $this->getSystemStatus();

        return view('auth.setup', [
            'steps' => $this->steps,
            'systemStatus' => $systemStatus,
            'requirements' => $this->checkRequirements(),
            'databaseConnected' => $this->checkDatabaseConnection(),
            'hasMigrations' => $this->checkMigrationsExist(),
        ]);
    }

    /**
     * ตรวจสอบสถานะระบบ
     */
    protected function getSystemStatus(): array
    {
        return [
            'has_env' => File::exists(base_path('.env')),
            'has_vendor' => File::exists(base_path('vendor/autoload.php')),
            'has_app_key' => ! empty(config('app.key')),
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_writable' => is_writable(base_path('bootstrap/cache')),
        ];
    }

    /**
     * ตรวจสอบ Requirements ของระบบ
     */
    protected function checkRequirements(): array
    {
        $requirements = [];

        // PHP Version
        $phpVersion = PHP_VERSION;
        $requirements['php'] = [
            'name' => 'PHP Version',
            'required' => '8.1.0',
            'current' => $phpVersion,
            'passed' => version_compare($phpVersion, '8.1.0', '>='),
        ];

        // PHP Extensions
        $extensions = [
            'bcmath' => 'BCMath',
            'ctype' => 'Ctype',
            'curl' => 'cURL',
            'dom' => 'DOM',
            'fileinfo' => 'Fileinfo',
            'json' => 'JSON',
            'mbstring' => 'Mbstring',
            'openssl' => 'OpenSSL',
            'pdo' => 'PDO',
            'pdo_mysql' => 'PDO MySQL',
            'tokenizer' => 'Tokenizer',
            'xml' => 'XML',
        ];

        foreach ($extensions as $ext => $name) {
            $requirements["ext_{$ext}"] = [
                'name' => "{$name} Extension",
                'required' => 'Enabled',
                'current' => extension_loaded($ext) ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded($ext),
            ];
        }

        // Directory Permissions
        $directories = [
            'storage' => storage_path(),
            'bootstrap_cache' => base_path('bootstrap/cache'),
        ];

        foreach ($directories as $key => $path) {
            $requirements["dir_{$key}"] = [
                'name' => basename($path).' directory',
                'required' => 'Writable',
                'current' => is_writable($path) ? 'Writable' : 'Not Writable',
                'passed' => is_writable($path),
            ];
        }

        return $requirements;
    }

    /**
     * ตรวจสอบการเชื่อมต่อฐานข้อมูล
     */
    protected function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ตรวจสอบว่า migrations ถูกรันแล้วหรือยัง
     */
    protected function checkMigrationsExist(): bool
    {
        try {
            // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
            return \Schema::hasTable('users');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ตรวจสอบการเชื่อมต่อฐานข้อมูล (API endpoint)
     *
     * @return \Illuminate\Http\JsonResponse
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
            // ทดสอบการเชื่อมต่อ
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                $request->host,
                $request->port,
                $request->database
            );

            $pdo = new \PDO(
                $dsn,
                $request->username,
                $request->password ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            return response()->json([
                'success' => true,
                'message' => 'เชื่อมต่อฐานข้อมูลสำเร็จ!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * บันทึกการตั้งค่าฐานข้อมูลและรัน migrations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            // อัพเดท .env file
            $this->updateEnvFile([
                'DB_HOST' => $request->host,
                'DB_PORT' => $request->port,
                'DB_DATABASE' => $request->database,
                'DB_USERNAME' => $request->username,
                'DB_PASSWORD' => $request->password ?? '',
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            // ทดสอบการเชื่อมต่อใหม่
            config([
                'database.connections.mysql.host' => $request->host,
                'database.connections.mysql.port' => $request->port,
                'database.connections.mysql.database' => $request->database,
                'database.connections.mysql.username' => $request->username,
                'database.connections.mysql.password' => $request->password ?? '',
            ]);

            DB::purge('mysql');
            DB::connection('mysql')->getPdo();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าฐานข้อมูลสำเร็จ!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * รัน Migrations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function runMigrations()
    {
        try {
            // รัน migrations
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'รัน migrations สำเร็จ!',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรัน migrations: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * รัน Seeders
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function runSeeders()
    {
        try {
            // รัน seeders
            Artisan::call('db:seed', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'รัน seeders สำเร็จ!',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรัน seeders: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * อัพเดทไฟล์ .env
     */
    protected function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            // Copy จาก .env.example ถ้ายังไม่มี .env
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            // Escape special characters
            $escapedValue = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);

            // ถ้ามี key อยู่แล้ว ให้อัพเดท
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}=\"{$escapedValue}\"",
                    $envContent
                );
            } else {
                // ถ้ายังไม่มี ให้เพิ่มเข้าไป
                $envContent .= "\n{$key}=\"{$escapedValue}\"";
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * บันทึกข้อมูล Super Admin และเสร็จสิ้นการติดตั้ง
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ตรวจสอบว่ามี super admin อยู่แล้วหรือไม่
        if ($this->isSetupCompleted()) {
            return redirect()->route('home')->with('error', 'ระบบได้ติดตั้งเรียบร้อยแล้ว');
        }

        // ตรวจสอบข้อมูลที่ส่งมา
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // สร้างบัญชี Super Admin
            // ⚠️ IMPORTANT: ใช้ new User() แล้ว assign แบบ direct
            // เพราะ 'role' และ 'is_super_admin' อยู่ใน $guarded array
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->email_verified_at = now();

            // ✅ กำหนด sensitive fields แบบ direct assignment
            $user->role = 'super_admin';
            $user->is_super_admin = true;

            // บันทึกข้อมูล
            $user->save();

            // สร้างไฟล์ flag เพื่อบอกว่าติดตั้งเสร็จแล้ว
            $this->createSetupCompletedFlag();

            // เข้าสู่ระบบอัตโนมัติ
            auth()->login($user);

            // Regenerate session เพื่อป้องกัน session fixation + สร้าง CSRF token ใหม่
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard')
                ->with('success', 'ติดตั้งระบบเรียบร้อยแล้ว! ยินดีต้อนรับสู่ TP-Affiliate');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการสร้างบัญชี: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * ตรวจสอบว่าติดตั้งระบบเรียบร้อยแล้วหรือไม่
     */
    protected function isSetupCompleted(): bool
    {
        // ตรวจสอบว่ามีไฟล์ flag หรือไม่
        if (File::exists(storage_path('app/.setup_completed'))) {
            return true;
        }

        // ตรวจสอบว่ามี super admin หรือไม่
        return User::where('is_super_admin', true)->exists() ||
               User::where('role', 'super_admin')->exists();
    }

    /**
     * สร้างไฟล์ flag เพื่อบอกว่าติดตั้งเสร็จแล้ว
     */
    protected function createSetupCompletedFlag(): void
    {
        $storagePath = storage_path('app');

        // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
        if (! File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // สร้างไฟล์ flag
        File::put(storage_path('app/.setup_completed'), now()->toString());
    }
}
