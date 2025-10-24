<?php

namespace App\Services\Setup;

use App\Models\SystemInfo;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Exception;

class SetupService
{
    /**
     * Check system requirements
     */
    public function checkRequirements(): array
    {
        $requirements = [
            'php_version' => [
                'required' => '8.1',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
            ],
            'extensions' => [],
        ];

        // Check required PHP extensions
        $requiredExtensions = [
            'pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype',
            'json', 'bcmath', 'curl', 'fileinfo', 'gd'
        ];

        foreach ($requiredExtensions as $extension) {
            $requirements['extensions'][$extension] = [
                'required' => true,
                'installed' => extension_loaded($extension),
            ];
        }

        // Check writable directories
        $requirements['permissions'] = [
            'storage' => is_writable(storage_path()),
            'bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
            'public' => is_writable(public_path()),
        ];

        return $requirements;
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection(array $config): array
    {
        try {
            config([
                'database.connections.test_connection' => [
                    'driver' => $config['driver'] ?? 'mysql',
                    'host' => $config['host'],
                    'port' => $config['port'] ?? 3306,
                    'database' => $config['database'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                ]
            ]);

            DB::connection('test_connection')->getPdo();

            return [
                'success' => true,
                'message' => 'Database connection successful',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create .env file
     */
    public function createEnvFile(array $config): bool
    {
        try {
            $envContent = File::get(base_path('.env.example'));

            // Replace database configuration
            $envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST=' . $config['db_host'], $envContent);
            $envContent = preg_replace('/DB_PORT=.*/', 'DB_PORT=' . $config['db_port'], $envContent);
            $envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $config['db_database'], $envContent);
            $envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=' . $config['db_username'], $envContent);
            $envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=' . $config['db_password'], $envContent);

            // Replace app configuration
            if (isset($config['app_name'])) {
                $envContent = preg_replace('/APP_NAME=.*/', 'APP_NAME="' . $config['app_name'] . '"', $envContent);
            }
            if (isset($config['app_url'])) {
                $envContent = preg_replace('/APP_URL=.*/', 'APP_URL=' . $config['app_url'], $envContent);
            }

            File::put(base_path('.env'), $envContent);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Run migrations
     */
    public function runMigrations(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return [
                'success' => true,
                'message' => 'Migrations completed successfully',
                'output' => Artisan::output(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Run seeders
     */
    public function runSeeders(): array
    {
        try {
            Artisan::call('db:seed', ['--force' => true]);

            return [
                'success' => true,
                'message' => 'Seeders completed successfully',
                'output' => Artisan::output(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Seeding failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate application key
     */
    public function generateKey(): bool
    {
        try {
            Artisan::call('key:generate', ['--force' => true]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create storage link
     */
    public function createStorageLink(): bool
    {
        try {
            Artisan::call('storage:link');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create admin user
     */
    public function createAdminUser(array $data): array
    {
        try {
            $user = \App\Models\User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'email_verified_at' => now(),
            ]);

            // Assign admin role (if using Spatie Permission)
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                $user->assignRole($adminRole);
            }

            return [
                'success' => true,
                'user' => $user,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete setup
     */
    public function completeSetup(array $data): bool
    {
        try {
            SystemInfo::create([
                'version' => $data['version'] ?? '1.0.0',
                'build_number' => $data['build_number'] ?? null,
                'installed_at' => now(),
                'php_version' => [
                    'version' => PHP_VERSION,
                    'extensions' => get_loaded_extensions(),
                ],
                'database_version' => [
                    'driver' => DB::connection()->getDriverName(),
                    'version' => DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown',
                ],
                'is_setup_completed' => true,
            ]);

            // Set default app settings
            if (isset($data['app_name'])) {
                AppSetting::set('app_name', $data['app_name']);
            }
            if (isset($data['app_logo'])) {
                AppSetting::set('app_logo', $data['app_logo'], 'file');
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get installation progress
     */
    public function getProgress(): array
    {
        return [
            'requirements' => $this->checkRequirements(),
            'env_exists' => File::exists(base_path('.env')),
            'database_connected' => $this->isDatabaseConnected(),
            'migrations_run' => $this->isMigrationsRun(),
            'setup_completed' => SystemInfo::isSetupCompleted(),
        ];
    }

    /**
     * Check if database is connected
     */
    protected function isDatabaseConnected(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if migrations are run
     */
    protected function isMigrationsRun(): bool
    {
        try {
            return DB::table('migrations')->exists();
        } catch (Exception $e) {
            return false;
        }
    }
}
