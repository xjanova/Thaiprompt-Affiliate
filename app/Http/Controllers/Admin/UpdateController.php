<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SystemUpdate;
use App\Models\UpdateLog;
use App\Models\UpdateNotification;
use App\Services\UpdateService;
use App\Services\VersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class UpdateController extends Controller
{
    protected UpdateService $updateService;
    protected VersionService $versionService;

    public function __construct(UpdateService $updateService, VersionService $versionService)
    {
        $this->updateService = $updateService;
        $this->versionService = $versionService;
    }

    /**
     * Display updates index page
     */
    public function index()
    {
        $currentVersion = config('version.current');
        $availableUpdates = SystemUpdate::whereRaw('version > ?', [$currentVersion])
            ->orderBy('released_at', 'desc')
            ->paginate(10);

        $updateHistory = UpdateLog::with(['systemUpdate', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $stats = [
            'current_version' => $currentVersion,
            'total_updates' => $availableUpdates->total(),
            'pending_updates' => SystemUpdate::whereRaw('version > ?', [$currentVersion])
                ->where('is_stable', true)
                ->count(),
            'last_update' => UpdateLog::where('status', 'completed')
                ->latest()
                ->first(),
        ];

        return view('admin.updates.index', compact('availableUpdates', 'updateHistory', 'stats'));
    }

    /**
     * Check for new updates
     */
    public function check()
    {
        try {
            // Clear version caches first to get fresh data
            $this->versionService->clearCache();

            // Force check for updates (bypasses UpdateService cache)
            $updates = $this->updateService->checkForUpdates(true);

            // Get fresh version comparison info
            $versionInfo = $this->versionService->checkForUpdates();

            $message = 'ระบบอัพเดทเป็นเวอร์ชันล่าสุดแล้ว';
            $help = null;

            if (count($updates) > 0) {
                $message = 'พบอัพเดทใหม่ ' . count($updates) . ' รายการ';
            } elseif (!$versionInfo['update_available'] && $versionInfo['latest_version'] === null) {
                // No releases found on GitHub
                $message = 'ไม่พบ releases บน GitHub repository';
                $help = [
                    'reason' => 'Repository ยังไม่มี releases หรือ repository เป็น private',
                    'solutions' => [
                        '1. สร้าง Release บน GitHub:',
                        '   - ไปที่ https://github.com/xjanova/Thaiprompt-Affiliate/releases',
                        '   - คลิก "Create a new release"',
                        '   - เลือก tag และใส่รายละเอียด',
                        '   - กด "Publish release"',
                        '',
                        '2. หรือถ้า repository เป็น private:',
                        '   - สร้าง Personal Access Token บน GitHub',
                        '   - เพิ่ม GITHUB_TOKEN=your_token ใน .env',
                    ],
                    'auto_release' => 'หรือใช้ GitHub Actions ที่มีอยู่แล้ว - แค่ merge PR ไปยัง main branch',
                ];
            }

            return response()->json([
                'success' => true,
                'updates' => $updates,
                'count' => count($updates),
                'version_info' => $versionInfo,
                'message' => $message,
                'help' => $help,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Update check failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Classify error and provide helpful solutions
            $errorMessage = $e->getMessage();
            $errorType = 'unknown';
            $solutions = [];

            if (preg_match('/(dns|resolution failed|could not resolve host|cURL error 6)/i', $errorMessage)) {
                $errorType = 'dns';
                $solutions = [
                    '1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต: ping 8.8.8.8',
                    '2. ตรวจสอบ DNS configuration ของเซิร์ฟเวอร์',
                    '3. เพิ่ม DNS servers: nameserver 8.8.8.8 ใน /etc/resolv.conf',
                    '4. Restart DNS service: sudo systemctl restart systemd-resolved',
                    '5. หากใช้ shared hosting: ติดต่อ hosting provider',
                    '6. ตรวจสอบว่า Firewall ไม่ได้บล็อก DNS queries (port 53)',
                ];
            } elseif (preg_match('/(timeout|timed out)/i', $errorMessage)) {
                $errorType = 'timeout';
                $solutions = [
                    '1. ตรวจสอบความเร็วอินเทอร์เน็ต',
                    '2. ลองอีกครั้งในอีกสักครู่',
                    '3. ตรวจสอบว่า api.github.com ไม่ถูกบล็อกโดย Firewall',
                ];
            } elseif (preg_match('/(could not read|failed to connect|network error)/i', $errorMessage)) {
                $errorType = 'network';
                $solutions = [
                    '1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต',
                    '2. ตรวจสอบ Firewall settings',
                    '3. ตรวจสอบว่า api.github.com สามารถเข้าถึงได้',
                ];
            }

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: การตรวจสอบอัพเดทล้มเหลว',
                'error_type' => $errorType,
                'error_details' => [
                    'message' => $errorMessage,
                    'type' => get_class($e),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : 'เปิด APP_DEBUG=true ใน .env เพื่อดู stack trace',
                ],
                'solutions' => $solutions,
                'help' => empty($solutions) ? 'ติดต่อผู้ดูแลระบบสำหรับความช่วยเหลือ' : 'ลองวิธีแก้ไขด้านล่าง',
            ], 500);
        }
    }

    /**
     * Show update details
     */
    public function show($id)
    {
        $update = SystemUpdate::findOrFail($id);
        $currentVersion = config('version.current');

        $requirements = [
            'php_version' => [
                'required' => $update->min_php_version ?? '8.1.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, $update->min_php_version ?? '8.1.0', '>=')
            ],
            'extensions' => $update->required_extensions ?? [],
            'can_update' => $update->meetsRequirements(),
        ];

        return view('admin.updates.show', compact('update', 'currentVersion', 'requirements'));
    }

    /**
     * Install/perform update
     */
    public function install($id)
    {
        try {
            $result = $this->updateService->performUpdate($id, Auth::id(), false);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'อัพเดทสำเร็จ!',
                    'log' => $result['log'],
                    'verification' => $result['verification'] ?? null,
                ]);
            } else {
                // Return detailed error information
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'เกิดข้อผิดพลาดในการอัพเดท',
                    'error_type' => $result['error_type'] ?? 'unknown',
                    'error_solution' => $result['error_solution'] ?? null,
                    'retryable' => $result['retryable'] ?? false,
                    'log' => $result['log'] ?? null,
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Update installation failed', [
                'update_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable APP_DEBUG to see stack trace',
                ],
            ], 500);
        }
    }

    /**
     * Show update logs
     */
    public function logs()
    {
        $logs = UpdateLog::with(['systemUpdate', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.updates.logs', compact('logs'));
    }

    /**
     * Show specific log details
     */
    public function showLog($id)
    {
        $log = UpdateLog::with(['systemUpdate', 'initiator'])->findOrFail($id);

        // If AJAX request, return JSON
        if (request()->expectsJson()) {
            return response()->json([
                'log' => $log
            ]);
        }

        return view('admin.updates.log-detail', compact('log'));
    }

    /**
     * Rollback to previous version
     */
    public function rollback($id)
    {
        try {
            $result = $this->updateService->rollback($id);

            return response()->json([
                'success' => true,
                'message' => 'Rollback สำเร็จ!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show settings page or get current settings (API)
     */
    public function getSettings()
    {
        // Get encrypted token from database
        $encryptedToken = Setting::get('update_github_token');
        $hasToken = !empty($encryptedToken);
        $maskedToken = null;

        // Decrypt and mask token if exists
        if ($hasToken) {
            try {
                $decryptedToken = Crypt::decryptString($encryptedToken);
                // Show first 7 chars and mask the rest
                $maskedToken = substr($decryptedToken, 0, 7) . '***' . substr($decryptedToken, -4);
            } catch (\Exception $e) {
                // If decryption fails, just show generic masked
                $maskedToken = '***';
                \Log::warning('Failed to decrypt GitHub token for display', ['error' => $e->getMessage()]);
            }
        }

        $settings = [
            'auto_check' => Setting::get('update_auto_check', false),
            'auto_update' => Setting::get('update_auto_update', false),
            'backup_before_update' => Setting::get('update_backup_before_update', true),
            'notification_email' => Setting::get('update_notification_email'),
            'github_token' => $maskedToken,
            'has_github_token' => $hasToken,
        ];

        // If AJAX request, return JSON
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'settings' => $settings,
            ]);
        }

        // Otherwise, return the settings view
        return view('admin.updates.settings');
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_check' => 'nullable|boolean',
            'auto_update' => 'nullable|boolean',
            'backup_before_update' => 'nullable|boolean',
            'notification_email' => 'nullable|email',
            'github_token' => 'nullable|string',
        ]);

        try {
            foreach ($validated as $key => $value) {
                // Skip null values
                if ($value === null && $key !== 'notification_email') {
                    continue;
                }

                // Encrypt GitHub token before storing
                if ($key === 'github_token') {
                    if (!empty($value)) {
                        // Validate token format (GitHub tokens start with ghp_, gho_, ghs_, or ghu_)
                        if (!preg_match('/^(ghp|gho|ghs|ghu)_[a-zA-Z0-9]{36,}$/', $value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'รูปแบบ GitHub Token ไม่ถูกต้อง (ต้องขึ้นต้นด้วย ghp_, gho_, ghs_, หรือ ghu_)',
                            ], 422);
                        }

                        // Encrypt token before storing
                        $encryptedToken = Crypt::encryptString($value);
                        Setting::set('update_github_token', $encryptedToken, 'string', 'update');

                        \Log::info('GitHub token updated', ['masked_token' => substr($value, 0, 7) . '***']);
                    } else {
                        // Remove token if empty
                        Setting::where('key', 'update_github_token')->delete();
                        \Log::info('GitHub token removed');
                    }
                } else {
                    $type = is_bool($value) ? 'boolean' : 'string';
                    Setting::set('update_' . $key, $value, $type, 'update');
                }
            }

            // Clear version caches to use new token immediately
            $this->versionService->clearCache();
            Cache::forget('available_updates');

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าสำเร็จ',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get update notifications
     */
    public function notifications()
    {
        $notifications = UpdateNotification::with('systemUpdate')
            ->where('user_id', Auth::id())
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    /**
     * Dismiss notification
     */
    public function dismissNotification($id)
    {
        $notification = UpdateNotification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'ทำเครื่องหมายว่าอ่านแล้ว'
        ]);
    }

    /**
     * Test GitHub connection
     */
    public function testConnection()
    {
        try {
            $results = $this->versionService->testGitHubConnection();

            // Count passed, failed, and warnings
            $passedCount = count($results['checks'] ?? []);
            $failedErrors = array_filter($results['errors'] ?? [], fn($e) => $e['status'] === 'failed');
            $warnings = array_filter($results['errors'] ?? [], fn($e) => $e['status'] === 'warning');
            $failedCount = count($failedErrors);
            $warningCount = count($warnings);

            // Build detailed message
            $message = $results['success']
                ? "การเชื่อมต่อ GitHub ทำงานปกติ ({$passedCount} ผ่าน"
                    . ($warningCount > 0 ? ", {$warningCount} คำเตือน" : "") . ")"
                : "พบปัญหาร้ายแรงในการเชื่อมต่อ GitHub ({$failedCount} ล้มเหลว)";

            return response()->json([
                'success' => $results['success'],
                'results' => $results,
                'message' => $message,
                'summary' => [
                    'passed' => $passedCount,
                    'failed' => $failedCount,
                    'warnings' => $warningCount,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('GitHub connection test failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'results' => [
                    'success' => false,
                    'checks' => [],
                    'errors' => [
                        [
                            'check' => 'System Error',
                            'status' => 'failed',
                            'message' => $e->getMessage(),
                        ]
                    ],
                    'debug_info' => [
                        'type' => get_class($e),
                        'file' => $e->getFile() . ':' . $e->getLine(),
                        'trace' => config('app.debug') ? explode("\n", $e->getTraceAsString()) : ['Enable APP_DEBUG to see stack trace'],
                    ],
                ],
            ], 500);
        }
    }
}
