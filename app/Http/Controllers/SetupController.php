<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Models\User;
use Exception;

class SetupController extends Controller
{
    /**
     * Show the setup welcome page
     */
    public function index()
    {
        return view('setup.welcome');
    }

    /**
     * Show requirements check page
     */
    public function requirements()
    {
        $requirements = [
            'php' => [
                'name' => 'PHP >= 8.1',
                'passed' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'value' => PHP_VERSION
            ],
            'extensions' => [
                'pdo' => [
                    'name' => 'PDO',
                    'passed' => extension_loaded('pdo'),
                ],
                'mbstring' => [
                    'name' => 'Mbstring',
                    'passed' => extension_loaded('mbstring'),
                ],
                'openssl' => [
                    'name' => 'OpenSSL',
                    'passed' => extension_loaded('openssl'),
                ],
                'tokenizer' => [
                    'name' => 'Tokenizer',
                    'passed' => extension_loaded('tokenizer'),
                ],
                'xml' => [
                    'name' => 'XML',
                    'passed' => extension_loaded('xml'),
                ],
                'curl' => [
                    'name' => 'cURL',
                    'passed' => extension_loaded('curl'),
                ],
                'fileinfo' => [
                    'name' => 'Fileinfo',
                    'passed' => extension_loaded('fileinfo'),
                ],
                'gd' => [
                    'name' => 'GD',
                    'passed' => extension_loaded('gd'),
                ],
                'zip' => [
                    'name' => 'Zip',
                    'passed' => extension_loaded('zip'),
                ],
            ],
            'permissions' => [
                'storage' => [
                    'name' => 'storage/',
                    'passed' => is_writable(storage_path()),
                ],
                'bootstrap' => [
                    'name' => 'bootstrap/cache/',
                    'passed' => is_writable(base_path('bootstrap/cache')),
                ],
            ]
        ];

        $allPassed = $requirements['php']['passed'];
        foreach ($requirements['extensions'] as $ext) {
            if (!$ext['passed']) {
                $allPassed = false;
                break;
            }
        }
        foreach ($requirements['permissions'] as $perm) {
            if (!$perm['passed']) {
                $allPassed = false;
                break;
            }
        }

        return view('setup.requirements', compact('requirements', 'allPassed'));
    }

    /**
     * Show database configuration page
     */
    public function database()
    {
        return view('setup.database');
    }

    /**
     * Test database connection
     */
    public function testDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required|numeric',
            'db_name' => 'required',
            'db_username' => 'required',
        ]);

        try {
            $connection = mysqli_connect(
                $request->db_host,
                $request->db_username,
                $request->db_password,
                null,
                $request->db_port
            );

            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect: ' . mysqli_connect_error()
                ]);
            }

            // Check if database exists
            $dbExists = mysqli_select_db($connection, $request->db_name);

            mysqli_close($connection);

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful!',
                'database_exists' => $dbExists
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save database configuration
     */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required|numeric',
            'db_name' => 'required',
            'db_username' => 'required',
        ]);

        try {
            // Update .env file
            $this->updateEnv([
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_name,
                'DB_USERNAME' => $request->db_username,
                'DB_PASSWORD' => $request->db_password,
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'Database configuration saved!'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show admin account creation page
     */
    public function admin()
    {
        return view('setup.admin');
    }

    /**
     * Create admin account and run migrations
     */
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Create admin user
            $admin = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'role' => 'admin',
                'status' => 'active',
            ]);

            // Create installation lock file
            File::put(storage_path('installed'), now()->toString());

            return response()->json([
                'success' => true,
                'message' => 'Installation completed successfully!',
                'redirect' => route('login')
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show final completion page
     */
    public function complete()
    {
        return view('setup.complete');
    }

    /**
     * Update .env file with new values
     */
    private function updateEnv(array $data)
    {
        $envFile = base_path('.env');

        if (!File::exists($envFile)) {
            File::copy(base_path('.env.example'), $envFile);
        }

        $content = File::get($envFile);

        foreach ($data as $key => $value) {
            $value = $this->sanitizeEnvValue($value);

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $content
                );
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        File::put($envFile, $content);
    }

    /**
     * Sanitize environment value
     */
    private function sanitizeEnvValue($value)
    {
        if (empty($value)) {
            return '';
        }

        // If value contains spaces or special characters, wrap in quotes
        if (preg_match('/\s/', $value) || preg_match('/[#$&*(){}[\]\\|;\'",<>?]/', $value)) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }

        return $value;
    }
}
