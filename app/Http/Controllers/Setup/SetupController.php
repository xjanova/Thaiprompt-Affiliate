<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Services\Setup\SetupService;
use App\Services\Backup\BackupService;
use App\Models\SystemInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetupController extends Controller
{
    protected SetupService $setupService;
    protected BackupService $backupService;

    public function __construct(SetupService $setupService, BackupService $backupService)
    {
        $this->setupService = $setupService;
        $this->backupService = $backupService;
    }

    /**
     * Show setup wizard welcome page
     */
    public function index()
    {
        // Redirect if setup is already completed
        if (SystemInfo::isSetupCompleted()) {
            return redirect()->route('home');
        }

        return view('setup.index');
    }

    /**
     * Check system requirements
     */
    public function checkRequirements()
    {
        $requirements = $this->setupService->checkRequirements();

        return response()->json([
            'success' => true,
            'requirements' => $requirements,
        ]);
    }

    /**
     * Show database configuration step
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
            'host' => 'required',
            'port' => 'required|integer',
            'database' => 'required',
            'username' => 'required',
            'password' => 'nullable',
        ]);

        $result = $this->setupService->testDatabaseConnection($request->all());

        return response()->json($result);
    }

    /**
     * Save database configuration
     */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required|integer',
            'db_database' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
            'app_name' => 'required|string',
            'app_url' => 'required|url',
        ]);

        $success = $this->setupService->createEnvFile($request->all());

        if ($success) {
            $this->setupService->generateKey();

            return response()->json([
                'success' => true,
                'message' => 'Database configuration saved successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to save database configuration',
        ], 500);
    }

    /**
     * Run migrations
     */
    public function migrate()
    {
        $result = $this->setupService->runMigrations();

        if ($result['success']) {
            $this->setupService->createStorageLink();
        }

        return response()->json($result);
    }

    /**
     * Run database seeders
     */
    public function seed()
    {
        $result = $this->setupService->runSeeders();

        return response()->json($result);
    }

    /**
     * Show admin account creation step
     */
    public function admin()
    {
        return view('setup.admin');
    }

    /**
     * Create admin account
     */
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $result = $this->setupService->createAdminUser($request->all());

        return response()->json($result);
    }

    /**
     * Complete setup
     */
    public function complete(Request $request)
    {
        // Create initial backup
        $this->backupService->createDatabaseBackup();

        // Complete setup
        $success = $this->setupService->completeSetup([
            'version' => '1.0.0',
            'app_name' => $request->input('app_name', config('app.name')),
        ]);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Setup completed successfully',
                'redirect' => route('admin.dashboard'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to complete setup',
        ], 500);
    }

    /**
     * Get installation progress
     */
    public function progress()
    {
        $progress = $this->setupService->getProgress();

        return response()->json($progress);
    }
}
