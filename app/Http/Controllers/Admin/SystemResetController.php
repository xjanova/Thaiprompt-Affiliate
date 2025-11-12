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
            // Quick Reset Option
            'full_reset' => [
                'label' => '🔄 รีเซ็ตระบบทั้งหมด (Full Reset)',
                'description' => 'ลบข้อมูลธุรกรรมและผู้ใช้ทั้งหมด เหลือเฉพาะ Super Admin และการตั้งค่าระบบ',
                'count' => $this->getTransactionalDataCount(),
                'icon' => 'sync-alt',
                'danger_level' => 'critical',
                'tables' => [] // Will be handled specially
            ],

            // User Data
            'users_auth' => [
                'label' => '👥 ผู้ใช้และการยืนยันตัวตน',
                'description' => 'ลบผู้ใช้ทั้งหมด (ยกเว้น Super Admin), sessions, tokens, OTP, KYC',
                'count' => $this->getTableCount(['users', 'sessions', 'password_reset_tokens']),
                'icon' => 'users',
                'danger_level' => 'critical',
                'tables' => ['sessions', 'password_reset_tokens', 'personal_access_tokens',
                            'two_factor_user_settings', 'otp_verifications', 'kyc_verifications',
                            'user_themes', 'user_taskbar_shortcuts', 'blocked_ips', 'threat_ips',
                            'line_avatars', 'line_login_logs']
            ],

            // Transactions
            'transactions' => [
                'label' => '💰 ธุรกรรมและคำสั่งซื้อ',
                'description' => 'ลบคำสั่งซื้อ, การชำระเงิน, คอมมิชชั่น, ธุรกรรม wallet',
                'count' => $this->getTableCount(['orders', 'payment_transactions', 'commissions', 'wallet_transactions']),
                'icon' => 'shopping-cart',
                'danger_level' => 'critical',
                'tables' => ['orders', 'order_items', 'payment_transactions', 'commissions',
                            'wallet_transactions', 'wallet_logs', 'wallets', 'withdrawal_requests',
                            'shopping_cart', 'shipping_addresses', 'affiliates', 'pos_transactions',
                            'pos_transaction_items', 'pos_sessions', 'pos_offline_queue',
                            'accounting_invoices', 'accounting_invoice_items', 'accounting_expenses',
                            'accounting_expense_items', 'accounting_payments', 'accounting_journal_entries',
                            'installment_payments', 'marketplace_orders', 'marketplace_order_items',
                            'marketplace_commissions']
            ],

            // MLM Data
            'mlm_data' => [
                'label' => '👨‍👩‍👧‍👦 ข้อมูล MLM สมาชิก',
                'description' => 'ลบสมาชิก MLM, โครงสร้าง, คอมมิชชั่น, PV, การลงทุน',
                'count' => $this->getTableCount(['mlm_members', 'mlm_commissions', 'mlm_genealogy']),
                'icon' => 'network-wired',
                'danger_level' => 'critical',
                'tables' => ['mlm_members', 'mlm_binary_positions', 'mlm_commissions', 'mlm_genealogy',
                            'mlm_pv_transactions', 'mlm_rank_achievements', 'mlm_prospects',
                            'rank_promotions', 'rank_bonuses', 'user_rank_progress',
                            'staking_positions', 'roi_distributions', 'investment_plans',
                            'coin_exchange_requests']
            ],

            // All Logs
            'all_logs' => [
                'label' => '📊 บันทึกและ Log ทั้งหมด',
                'description' => 'ลบ logs ทุกประเภท (รวม system_reset_logs, email, security, API, analytics)',
                'count' => $this->getTableCount(['email_logs', 'security_logs', 'ai_usage_logs', 'system_reset_logs']),
                'icon' => 'file-alt',
                'danger_level' => 'medium',
                'tables' => ['email_logs', 'security_logs', 'ai_usage_logs', 'ai_gen_usage_logs',
                            'rag_usage_logs', 'api_usage_logs', 'accounting_activity_logs',
                            'system_analytics', 'ai_installation_logs', 'ticket_notification_logs',
                            'line_login_logs', 'marketplace_sync_logs', 'system_reset_logs',
                            'webp_conversion_stats', 'vendor_analytics', 'vendor_features_usage',
                            'smart_slider_analytics', 'cookie_trackings', 'cookie_analytics_keywords',
                            'trend_analytics', 'trend_data', 'trend_data_keyword', 'viral_trends',
                            'vendor_store_visits', 'marketplace_link_clicks', 'deployments',
                            'failed_jobs', 'jobs', 'job_batches']
            ],

            // Conversations
            'conversations' => [
                'label' => '💬 การสนทนาและข้อความ',
                'description' => 'ลบการสนทนา AI, LINE bot, ข้อความซัพพอร์ต, broadcast',
                'count' => $this->getTableCount(['ai_conversations', 'line_bot_conversations', 'bot_support_conversations']),
                'icon' => 'comments',
                'danger_level' => 'high',
                'tables' => ['ai_conversations', 'ai_messages', 'bot_support_conversations',
                            'bot_support_messages', 'bot_sales_conversations', 'bot_sales_messages',
                            'line_bot_conversations', 'line_bot_messages', 'line_broadcast_messages',
                            'notifications', 'knowledge_chunks', 'line_bot_knowledge_bases']
            ],

            // Bookings
            'bookings' => [
                'label' => '🏨 การจองและรีวิว',
                'description' => 'ลบการจองโรงแรม, รีวิว, ความว่างของห้อง, การอ่านไพ่',
                'count' => $this->getTableCount(['hotel_bookings', 'hotel_reviews', 'tarot_readings']),
                'icon' => 'hotel',
                'danger_level' => 'high',
                'tables' => ['hotel_bookings', 'hotel_reviews', 'hotel_review_votes',
                            'room_availability', 'tarot_readings', 'tarot_reading_cards']
            ],

            // User Activities
            'user_activities' => [
                'label' => '🎯 กิจกรรมและความคืบหน้าผู้ใช้',
                'description' => 'ลบการอ่านบทความ, ดูวิดีโอ, เควส, ควิซ, การเข้างาน',
                'count' => $this->getTableCount(['user_article_progress', 'user_video_watches', 'quiz_attempts']),
                'icon' => 'tasks',
                'danger_level' => 'medium',
                'tables' => ['user_article_progress', 'user_daily_streaks', 'user_quest_progress',
                            'user_video_watches', 'user_video_levels', 'video_watch_sessions',
                            'video_coin_transactions', 'video_referral_rewards', 'quiz_attempts',
                            'quiz_answers', 'training_enrollments', 'learning_articles',
                            'attendance_records', 'leave_requests', 'job_applications',
                            'tarot_cart_items', 'tarot_user_limits']
            ],

            // Tickets
            'tickets' => [
                'label' => '🎫 ตั๋วซัพพอร์ต',
                'description' => 'ลบตั๋วซัพพอร์ต, การตอบกลับ, ไฟล์แนบ, คะแนน',
                'count' => $this->getTableCount(['tickets']),
                'icon' => 'ticket-alt',
                'danger_level' => 'medium',
                'tables' => ['tickets', 'ticket_replies', 'ticket_attachments', 'ticket_ratings',
                            'ticket_relationships', 'kb_article_ticket', 'kb_article_attachments',
                            'article_permissions']
            ],

            // AI Activities
            'ai_activities' => [
                'label' => '🤖 กิจกรรม AI และ Bot',
                'description' => 'ลบการสร้าง AI, การเช่า bot, automation, รีวิว',
                'count' => $this->getTableCount(['ai_gen_generations', 'ai_bot_rentals', 'bot_automation_executions']),
                'icon' => 'robot',
                'danger_level' => 'medium',
                'tables' => ['ai_gen_generations', 'ai_bot_rentals', 'ai_rental_transactions',
                            'ai_owner_earnings', 'bot_automation_executions', 'bot_scheduled_posts',
                            'bot_marketplace_reviews', 'bot_rental_subscriptions', 'bot_platform_connections',
                            'product_reviews', 'email_preferences', 'cookie_consents']
            ],

            // Crypto
            'crypto' => [
                'label' => '💱 ธุรกรรม Cryptocurrency',
                'description' => 'ลบธุรกรรมคริปโต, กระเป๋า, ที่อยู่, คำขอถอน',
                'count' => $this->getTableCount(['crypto_transactions', 'crypto_wallets']),
                'icon' => 'bitcoin',
                'danger_level' => 'critical',
                'tables' => ['crypto_transactions', 'crypto_wallets', 'crypto_addresses',
                            'crypto_deposit_addresses', 'crypto_withdrawal_requests',
                            'crypto_exchange_transactions', 'crypto_exchange_rates',
                            'video_coins', 'coin_exchange_rates']
            ],

            // Trading
            'trading' => [
                'label' => '📈 การเทรดและการลงทุน',
                'description' => 'ลบบัญชีเทรด, บอท, กลยุทธ์, รายการเทรด, สัญญาณ',
                'count' => $this->getTableCount(['trading_accounts', 'trading_bots', 'trading_trades']),
                'icon' => 'chart-line',
                'danger_level' => 'critical',
                'tables' => ['trading_accounts', 'trading_bots', 'trading_bot_subscriptions',
                            'trading_strategies', 'trading_trades', 'trading_signals',
                            'trading_market_data', 'trading_portfolio_snapshots', 'trading_backtests',
                            'trading_notifications', 'trading_strategy_purchases', 'trading_strategy_reviews']
            ],

            // HR Data
            'hr_data' => [
                'label' => '🏢 ข้อมูล HR และพนักงาน',
                'description' => 'ลบพนักงาน, เอกสาร, เงินเดือน, การประเมิน',
                'count' => $this->getTableCount(['employees', 'payroll_records']),
                'icon' => 'building',
                'danger_level' => 'high',
                'tables' => ['employees', 'employee_documents', 'payroll_records',
                            'performance_reviews', 'performance_goals', 'job_postings',
                            'accounting_bank_accounts', 'accounting_contacts']
            ],

            // Marketplace
            'marketplace' => [
                'label' => '🛍️ Marketplace และ Vendor',
                'description' => 'ลบบัญชี marketplace, ลิงก์ affiliate, subscription',
                'count' => $this->getTableCount(['marketplace_accounts', 'vendor_subscriptions']),
                'icon' => 'store',
                'danger_level' => 'medium',
                'tables' => ['marketplace_accounts', 'marketplace_affiliate_links',
                            'vendor_subscriptions', 'vendor_marketing_campaigns']
            ],

            // Quotations
            'quotations' => [
                'label' => '📜 ใบเสนอราคา',
                'description' => 'ลบใบเสนอราคาซอฟต์แวร์และรายการ',
                'count' => $this->getTableCount(['software_quotations']),
                'icon' => 'file-invoice',
                'danger_level' => 'low',
                'tables' => ['software_quotations', 'software_quotation_items',
                            'software_quotation_selected_options']
            ],

            // Membership
            'membership' => [
                'label' => '💳 การต่ออายุสมาชิก',
                'description' => 'ลบประวัติการต่ออายุ, รายการ, สถานะ',
                'count' => $this->getTableCount(['membership_retention_history', 'membership_retention_transactions']),
                'icon' => 'id-card',
                'danger_level' => 'medium',
                'tables' => ['membership_retention_advance_renewals', 'membership_retention_history',
                            'membership_retention_repairs', 'membership_retention_status',
                            'membership_retention_transactions']
            ],

            // Academy
            'academy' => [
                'label' => '🎓 ใบประกาศนียบัตร',
                'description' => 'ลบใบประกาศนียบัตรที่ออกให้',
                'count' => $this->getTableCount(['certificates']),
                'icon' => 'certificate',
                'danger_level' => 'low',
                'tables' => ['certificates', 'article_prerequisites']
            ],
        ];
    }

    /**
     * Get total count of transactional data
     */
    private function getTransactionalDataCount(): int
    {
        $count = 0;
        $count += User::where('is_super_admin', false)->count();

        // Sample a few key tables for the count
        $keyTables = ['orders', 'wallets', 'mlm_members', 'tickets', 'ai_conversations'];
        foreach ($keyTables as $table) {
            if (Schema::hasTable($table)) {
                $count += DB::table($table)->count();
            }
        }

        return $count;
    }

    /**
     * Get count from multiple tables
     */
    private function getTableCount(array $tables): int
    {
        $count = 0;
        foreach ($tables as $table) {
            if ($table === 'users') {
                $count += User::where('is_super_admin', false)->count();
            } elseif (Schema::hasTable($table)) {
                $count += DB::table($table)->count();
            }
        }
        return $count;
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

                // Handle full reset specially
                if ($option === 'full_reset') {
                    $summary = $this->performFullReset();
                    break; // Full reset covers everything
                }

                $optionData = $resetOptions[$option];
                $deletedCount = 0;

                // Special handling for users_auth - don't delete super admins
                if ($option === 'users_auth') {
                    $deletedCount = User::where('is_super_admin', false)->delete();

                    // Delete from other auth-related tables
                    foreach ($optionData['tables'] as $table) {
                        if (Schema::hasTable($table)) {
                            $count = DB::table($table)->count();
                            DB::table($table)->truncate();
                            $deletedCount += $count;
                        }
                    }
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
     * Perform full system reset - delete all transactional data
     */
    private function performFullReset(): array
    {
        $summary = [];

        // All transactional tables grouped by category
        $transactionalTables = [
            'users_auth' => ['sessions', 'password_reset_tokens', 'personal_access_tokens',
                            'two_factor_user_settings', 'otp_verifications', 'kyc_verifications',
                            'user_themes', 'user_taskbar_shortcuts', 'blocked_ips', 'threat_ips',
                            'line_avatars'],
            'transactions' => ['orders', 'order_items', 'payment_transactions', 'commissions',
                            'wallet_transactions', 'wallet_logs', 'wallets', 'withdrawal_requests',
                            'shopping_cart', 'shipping_addresses', 'affiliates', 'pos_transactions',
                            'pos_transaction_items', 'pos_sessions', 'pos_offline_queue',
                            'accounting_invoices', 'accounting_invoice_items', 'accounting_expenses',
                            'accounting_expense_items', 'accounting_payments', 'accounting_journal_entries',
                            'installment_payments', 'marketplace_orders', 'marketplace_order_items',
                            'marketplace_commissions'],
            'mlm_data' => ['mlm_members', 'mlm_binary_positions', 'mlm_commissions', 'mlm_genealogy',
                            'mlm_pv_transactions', 'mlm_rank_achievements', 'mlm_prospects',
                            'rank_promotions', 'rank_bonuses', 'user_rank_progress',
                            'staking_positions', 'roi_distributions', 'investment_plans',
                            'coin_exchange_requests'],
            'logs' => ['email_logs', 'security_logs', 'ai_usage_logs', 'ai_gen_usage_logs',
                            'rag_usage_logs', 'api_usage_logs', 'accounting_activity_logs',
                            'system_analytics', 'ai_installation_logs', 'ticket_notification_logs',
                            'line_login_logs', 'marketplace_sync_logs', 'system_reset_logs',
                            'webp_conversion_stats', 'vendor_analytics', 'vendor_features_usage',
                            'smart_slider_analytics', 'cookie_trackings', 'cookie_analytics_keywords',
                            'trend_analytics', 'trend_data', 'trend_data_keyword', 'viral_trends',
                            'vendor_store_visits', 'marketplace_link_clicks', 'deployments',
                            'failed_jobs', 'jobs', 'job_batches'],
            'conversations' => ['ai_conversations', 'ai_messages', 'bot_support_conversations',
                            'bot_support_messages', 'bot_sales_conversations', 'bot_sales_messages',
                            'line_bot_conversations', 'line_bot_messages', 'line_broadcast_messages',
                            'notifications', 'knowledge_chunks', 'line_bot_knowledge_bases'],
            'bookings' => ['hotel_bookings', 'hotel_reviews', 'hotel_review_votes',
                            'room_availability', 'tarot_readings', 'tarot_reading_cards'],
            'user_activities' => ['user_article_progress', 'user_daily_streaks', 'user_quest_progress',
                            'user_video_watches', 'user_video_levels', 'video_watch_sessions',
                            'video_coin_transactions', 'video_referral_rewards', 'quiz_attempts',
                            'quiz_answers', 'training_enrollments', 'learning_articles',
                            'attendance_records', 'leave_requests', 'job_applications',
                            'tarot_cart_items', 'tarot_user_limits'],
            'tickets' => ['tickets', 'ticket_replies', 'ticket_attachments', 'ticket_ratings',
                            'ticket_relationships', 'kb_article_ticket', 'kb_article_attachments',
                            'article_permissions'],
            'ai_activities' => ['ai_gen_generations', 'ai_bot_rentals', 'ai_rental_transactions',
                            'ai_owner_earnings', 'bot_automation_executions', 'bot_scheduled_posts',
                            'bot_marketplace_reviews', 'bot_rental_subscriptions', 'bot_platform_connections',
                            'product_reviews', 'email_preferences', 'cookie_consents'],
            'crypto' => ['crypto_transactions', 'crypto_wallets', 'crypto_addresses',
                            'crypto_deposit_addresses', 'crypto_withdrawal_requests',
                            'crypto_exchange_transactions', 'crypto_exchange_rates',
                            'video_coins', 'coin_exchange_rates'],
            'trading' => ['trading_accounts', 'trading_bots', 'trading_bot_subscriptions',
                            'trading_strategies', 'trading_trades', 'trading_signals',
                            'trading_market_data', 'trading_portfolio_snapshots', 'trading_backtests',
                            'trading_notifications', 'trading_strategy_purchases', 'trading_strategy_reviews'],
            'hr_data' => ['employees', 'employee_documents', 'payroll_records',
                            'performance_reviews', 'performance_goals', 'job_postings',
                            'accounting_bank_accounts', 'accounting_contacts'],
            'marketplace' => ['marketplace_accounts', 'marketplace_affiliate_links',
                            'vendor_subscriptions', 'vendor_marketing_campaigns'],
            'quotations' => ['software_quotations', 'software_quotation_items',
                            'software_quotation_selected_options'],
            'membership' => ['membership_retention_advance_renewals', 'membership_retention_history',
                            'membership_retention_repairs', 'membership_retention_status',
                            'membership_retention_transactions'],
            'academy' => ['certificates', 'article_prerequisites'],
            'api_keys' => ['api_keys'], // User-specific API keys
        ];

        $totalDeleted = 0;

        // Delete users first (except super admin)
        $usersDeleted = User::where('is_super_admin', false)->delete();
        $totalDeleted += $usersDeleted;

        // Delete all transactional data
        foreach ($transactionalTables as $category => $tables) {
            $categoryDeleted = 0;

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $categoryDeleted += $count;
                }
            }

            if ($categoryDeleted > 0) {
                $summary[$category] = [
                    'label' => $this->getCategoryLabel($category),
                    'deleted_count' => $categoryDeleted,
                    'tables' => $tables
                ];
            }

            $totalDeleted += $categoryDeleted;
        }

        // Add users to summary
        $summary['users'] = [
            'label' => 'ผู้ใช้ (ยกเว้น Super Admin)',
            'deleted_count' => $usersDeleted,
            'tables' => ['users']
        ];

        Log::info("Full system reset completed", [
            'total_deleted' => $totalDeleted,
            'performed_by' => auth()->id()
        ]);

        return $summary;
    }

    /**
     * Get category label for summary
     */
    private function getCategoryLabel(string $category): string
    {
        $labels = [
            'users_auth' => 'ข้อมูลผู้ใช้และการยืนยันตัวตน',
            'transactions' => 'ธุรกรรมและคำสั่งซื้อ',
            'mlm_data' => 'ข้อมูล MLM',
            'logs' => 'บันทึกและ Log ทั้งหมด',
            'conversations' => 'การสนทนาและข้อความ',
            'bookings' => 'การจองและรีวิว',
            'user_activities' => 'กิจกรรมผู้ใช้',
            'tickets' => 'ตั๋วซัพพอร์ต',
            'ai_activities' => 'กิจกรรม AI',
            'crypto' => 'ธุรกรรม Crypto',
            'trading' => 'การเทรด',
            'hr_data' => 'ข้อมูล HR',
            'marketplace' => 'Marketplace',
            'quotations' => 'ใบเสนอราคา',
            'membership' => 'การต่ออายุ',
            'academy' => 'ใบประกาศนียบัตร',
            'api_keys' => 'API Keys',
        ];

        return $labels[$category] ?? $category;
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
