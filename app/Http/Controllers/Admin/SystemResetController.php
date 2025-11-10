<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemResetLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SystemResetController extends Controller
{
    /**
     * Show the system reset page
     */
    public function index()
    {
        // Only super admin can access
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'เฉพาะ Super Admin เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        // Get recent reset logs
        $recentLogs = SystemResetLog::with('performedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get available reset options with current counts
        $resetOptions = $this->getResetOptions();

        return view('admin.system-reset.index', compact('recentLogs', 'resetOptions'));
    }

    /**
     * Get available reset options with current data counts
     */
    private function getResetOptions(): array
    {
        return [
            'users' => [
                'label' => 'ผู้ใช้ (Users)',
                'description' => 'ลบผู้ใช้ทั้งหมด ยกเว้น Super Admin',
                'count' => User::where('is_super_admin', false)->count(),
                'icon' => 'users',
                'danger_level' => 'high',
                'tables' => ['users']
            ],
            'affiliates' => [
                'label' => 'พันธมิตร (Affiliates)',
                'description' => 'ลบข้อมูลพันธมิตรทั้งหมด',
                'count' => DB::table('affiliates')->count(),
                'icon' => 'user-friends',
                'danger_level' => 'high',
                'tables' => ['affiliates']
            ],
            'commissions' => [
                'label' => 'คอมมิชชั่น (Commissions)',
                'description' => 'ลบประวัติคอมมิชชั่นทั้งหมด',
                'count' => DB::table('commissions')->count(),
                'icon' => 'dollar-sign',
                'danger_level' => 'high',
                'tables' => ['commissions']
            ],
            'wallets' => [
                'label' => 'กระเป๋าเงิน (Wallets)',
                'description' => 'ลบข้อมูลกระเป๋าเงินและธุรกรรม',
                'count' => DB::table('wallets')->count(),
                'icon' => 'wallet',
                'danger_level' => 'critical',
                'tables' => ['wallets', 'wallet_transactions', 'withdrawal_requests']
            ],
            'hotels' => [
                'label' => 'โรงแรม (Hotels)',
                'description' => 'ลบข้อมูลโรงแรม ห้องพัก และการจอง',
                'count' => DB::table('hotels')->count(),
                'icon' => 'hotel',
                'danger_level' => 'high',
                'tables' => ['hotels', 'hotel_rooms', 'hotel_bookings', 'hotel_reviews']
            ],
            'ai_data' => [
                'label' => 'ข้อมูล AI',
                'description' => 'ลบประวัติการใช้งาน AI, บทสนทนา และ knowledge base',
                'count' => DB::table('ai_conversations')->count(),
                'icon' => 'robot',
                'danger_level' => 'medium',
                'tables' => ['ai_conversations', 'ai_messages', 'ai_usage_logs', 'ai_knowledge_bases', 'ai_knowledge_chunks']
            ],
            'orders' => [
                'label' => 'คำสั่งซื้อ (Orders)',
                'description' => 'ลบประวัติคำสั่งซื้อและรายการสินค้า',
                'count' => DB::table('orders')->count(),
                'icon' => 'shopping-cart',
                'danger_level' => 'high',
                'tables' => ['orders', 'order_items']
            ],
            'products' => [
                'label' => 'สินค้า (Products)',
                'description' => 'ลบข้อมูลสินค้าทั้งหมด',
                'count' => DB::table('products')->count(),
                'icon' => 'box',
                'danger_level' => 'medium',
                'tables' => ['products', 'product_images']
            ],
            'mlm' => [
                'label' => 'ระบบ MLM',
                'description' => 'ลบข้อมูล MLM, สมาชิก และคอมมิชชั่น',
                'count' => DB::table('mlm_members')->count(),
                'icon' => 'network-wired',
                'danger_level' => 'critical',
                'tables' => ['mlm_members', 'mlm_commissions', 'mlm_payouts']
            ],
            'crypto' => [
                'label' => 'Cryptocurrency',
                'description' => 'ลบข้อมูลกระเป๋า crypto และธุรกรรม',
                'count' => DB::table('crypto_wallets')->count(),
                'icon' => 'bitcoin',
                'danger_level' => 'critical',
                'tables' => ['crypto_wallets', 'crypto_transactions', 'crypto_addresses', 'crypto_withdrawal_requests']
            ],
            'notifications' => [
                'label' => 'การแจ้งเตือน',
                'description' => 'ลบการแจ้งเตือนทั้งหมด',
                'count' => DB::table('notifications')->count(),
                'icon' => 'bell',
                'danger_level' => 'low',
                'tables' => ['notifications']
            ],
            'logs' => [
                'label' => 'บันทึกระบบ (Logs)',
                'description' => 'ลบบันทึกการใช้งานระบบ (ยกเว้น system_reset_logs)',
                'count' => DB::table('email_logs')->count(),
                'icon' => 'file-alt',
                'danger_level' => 'low',
                'tables' => ['email_logs', 'activity_logs', 'accounting_activity_logs']
            ],
            'tickets' => [
                'label' => 'ตั๋วสนับสนุน (Support Tickets)',
                'description' => 'ลบตั๋วสนับสนุนและข้อความทั้งหมด',
                'count' => DB::table('tickets')->count(),
                'icon' => 'ticket-alt',
                'danger_level' => 'medium',
                'tables' => ['tickets', 'ticket_messages', 'ticket_attachments']
            ],
            'analytics' => [
                'label' => 'ข้อมูลวิเคราะห์',
                'description' => 'ลบข้อมูลสถิติและการวิเคราะห์',
                'count' => DB::table('cookie_trackings')->count(),
                'icon' => 'chart-line',
                'danger_level' => 'low',
                'tables' => ['cookie_trackings', 'cookie_analytics_keywords']
            ],
        ];
    }

    /**
     * Get statistics for confirmation
     */
    public function getStatistics(Request $request)
    {
        $options = $request->input('options', []);
        $resetOptions = $this->getResetOptions();

        $statistics = [];
        foreach ($options as $option) {
            if (isset($resetOptions[$option])) {
                $statistics[$option] = $resetOptions[$option];
            }
        }

        return response()->json([
            'success' => true,
            'statistics' => $statistics
        ]);
    }

    /**
     * Perform the system reset
     */
    public function reset(Request $request)
    {
        // Only super admin can reset
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะ Super Admin เท่านั้นที่สามารถรีเซ็ตระบบได้'
            ], 403);
        }

        $request->validate([
            'options' => 'required|array|min:1',
            'options.*' => 'string',
            'confirmation_text' => 'required|string|in:RESET',
        ]);

        $options = $request->input('options');
        $resetOptions = $this->getResetOptions();

        // Create reset log
        $resetLog = SystemResetLog::create([
            'performed_by' => auth()->id(),
            'reset_options' => $options,
            'reset_summary' => [],
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);

        try {
            // Update status to processing
            $resetLog->update(['status' => 'processing']);

            $summary = [];

            DB::beginTransaction();

            foreach ($options as $option) {
                if (!isset($resetOptions[$option])) {
                    continue;
                }

                $optionData = $resetOptions[$option];
                $deletedCount = 0;

                // Special handling for users - don't delete super admins
                if ($option === 'users') {
                    $deletedCount = User::where('is_super_admin', false)->delete();
                } else {
                    // Delete from all related tables
                    foreach ($optionData['tables'] as $table) {
                        if (Schema::hasTable($table)) {
                            $count = DB::table($table)->count();
                            DB::table($table)->truncate();
                            $deletedCount += $count;
                        }
                    }
                }

                $summary[$option] = [
                    'label' => $optionData['label'],
                    'deleted_count' => $deletedCount,
                    'tables' => $optionData['tables']
                ];

                Log::info("System reset: {$option} completed", [
                    'deleted_count' => $deletedCount,
                    'performed_by' => auth()->id()
                ]);
            }

            DB::commit();

            // Update reset log
            $completedAt = now();
            $resetLog->update([
                'status' => 'completed',
                'reset_summary' => $summary,
                'completed_at' => $completedAt,
                'duration_seconds' => $completedAt->diffInSeconds($resetLog->started_at),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'รีเซ็ตระบบเรียบร้อยแล้ว',
                'summary' => $summary,
                'log_id' => $resetLog->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Update reset log with error
            $resetLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('System reset failed', [
                'error' => $e->getMessage(),
                'performed_by' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรีเซ็ตระบบ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show reset log details
     */
    public function showLog($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'เฉพาะ Super Admin เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $log = SystemResetLog::with('performedBy')->findOrFail($id);

        return view('admin.system-reset.show', compact('log'));
    }

    /**
     * Get reset logs (for AJAX)
     */
    public function getLogs(Request $request)
    {
        $logs = SystemResetLog::with('performedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($logs);
    }
}
