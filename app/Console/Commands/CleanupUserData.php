<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * CleanupUserData - ลบข้อมูล User ทั้งหมด ยกเว้น Admin
 *
 * ใช้สำหรับ Deploy ระบบใหม่
 * ลบข้อมูลที่เกี่ยวข้องกับ User ทั้งหมด รวมถึง:
 * - MLM Members
 * - Wallets และ Transactions
 * - Orders
 * - Posts, Comments
 * - Notifications
 * - Uploaded Files
 * - ฯลฯ
 */
class CleanupUserData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:users
                            {--force : ข้ามการยืนยัน}
                            {--keep-admin : เก็บ Admin users ไว้ (default)}
                            {--dry-run : แสดงสิ่งที่จะลบโดยไม่ลบจริง}';

    /**
     * The console command description.
     */
    protected $description = 'ลบข้อมูล User ทั้งหมด ยกเว้น Admin (สำหรับ Deploy)';

    /**
     * ตารางที่มี user_id และต้องลบก่อน users
     */
    private array $tablesWithUserId = [
        // MLM System
        'mlm_members',
        'mlm_commissions',
        'mlm_pv_transactions',
        'mlm_prospects',
        'mlm_binary_placements',

        // Wallet System
        'wallets',
        'wallet_transactions',
        'wallet_logs',
        'withdrawal_requests',

        // E-commerce
        'orders',
        'order_items',
        'shopping_cart',
        'shipping_addresses',
        'product_reviews',
        'wishlists',

        // Vendor System
        'vendor_stores',
        'vendor_public_products',
        'vendor_store_reviews',

        // Learning/Academy
        'user_article_progress',
        'quiz_attempts',
        'quiz_answers',
        'certificates',
        'course_enrollments',

        // LINE Integration
        'line_login_logs',
        'line_bot_conversations',
        'line_user_profiles',
        'line_avatars',
        'line_encrypted_tokens',

        // AI/Bot System
        'ai_bot_profiles',
        'ai_bot_rentals',
        'ai_rental_transactions',
        'ai_owner_earnings',
        'ai_conversations',
        'ai_usage_logs',
        'ai_gen_subscriptions',
        'ai_gen_usage_logs',
        'ai_gen_generations',

        // TPIX/Crypto
        'tpix_token_balances',
        'tpix_token_transfers',
        'tpix_staking',
        'tpix_referrals',
        'tpix_swaps',
        'tpix_liquidity_positions',
        'staking_positions',
        'roi_distributions',

        // Trading Bot
        'trading_bot_subscriptions',

        // Notifications & Logs
        'notifications',
        'activity_logs',
        'security_logs',
        'email_logs',
        'email_preferences',

        // KYC & Verification
        'kyc_verifications',
        'otp_verifications',
        'two_factor_user_settings',

        // Rank System
        'rank_promotions',
        'user_rank_progress',

        // Tickets & Support
        'tickets',
        'ticket_replies',

        // Hotel & Booking
        'hotel_bookings',
        'hotel_reviews',

        // Tarot System
        'tarot_readings',
        'tarot_user_limits',
        'tarot_cart_items',

        // POS System
        'pos_sessions',
        'pos_transactions',
        'pos_clock_records',

        // HRM System
        'employees',
        'employee_documents',
        'attendance_records',
        'leave_requests',
        'payroll_records',
        'performance_reviews',
        'performance_goals',
        'training_enrollments',

        // Forum/Community
        'forum_posts',
        'forum_comments',
        'forum_user_trophies',

        // Cookie & Privacy
        'cookie_consents',

        // Payment
        'payment_transactions',
        'payment_methods',

        // QR/Barcode
        'qr_barcodes',
        'qr_barcode_templates',

        // Theme System
        'user_themes',

        // Bot Automation
        'bot_platform_connections',
        'bot_content_templates',

        // Accounting
        'journal_entries',
        'journal_entry_lines',

        // Service Booking
        'service_bookings',
        'service_reviews',

        // Video Missions
        'video_mission_progress',
        'user_video_rewards',
    ];

    /**
     * โฟลเดอร์ที่เก็บไฟล์ของ User
     */
    private array $userStorageFolders = [
        'avatars',
        'user-uploads',
        'kyc-documents',
        'id-cards',
        'products',
        'reviews',
        'tickets',
        'qr-codes',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('🧹 Cleanup User Data - ลบข้อมูล User ทั้งหมด (ยกเว้น Admin)');
        $this->info('');

        // นับจำนวน users ที่จะลบ
        $adminCount = User::where('role', 'admin')->count();
        $userCount = User::where('role', '!=', 'admin')->count();

        $this->info('📊 สถิติ:');
        $this->info("   • Admin users (เก็บไว้): {$adminCount} คน");
        $this->info("   • Users ที่จะลบ: {$userCount} คน");
        $this->info('');

        if ($userCount === 0) {
            $this->info('✅ ไม่มี User ที่ต้องลบ');

            return Command::SUCCESS;
        }

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - แสดงสิ่งที่จะลบโดยไม่ลบจริง');
            $this->showDryRun();

            return Command::SUCCESS;
        }

        // ยืนยันก่อนลบ
        if (! $this->option('force')) {
            $this->warn('⚠️  คำเตือน: การดำเนินการนี้จะลบข้อมูลทั้งหมดและไม่สามารถกู้คืนได้!');
            $this->warn('   ข้อมูลที่จะลบ:');
            $this->warn('   - Users ทั้งหมด (ยกเว้น Admin)');
            $this->warn('   - MLM Members และ Commissions');
            $this->warn('   - Wallets และ Transactions');
            $this->warn('   - Orders และ Order Items');
            $this->warn('   - ไฟล์ที่ upload ทั้งหมด');
            $this->warn('   - และข้อมูลอื่นๆ ที่เกี่ยวข้อง');
            $this->info('');

            if (! $this->confirm('คุณแน่ใจหรือไม่ที่จะดำเนินการ?')) {
                $this->info('❌ ยกเลิกการดำเนินการ');

                return Command::FAILURE;
            }
        }

        $this->info('🚀 เริ่มลบข้อมูล...');
        $this->info('');

        // ดึง user IDs ที่จะลบ
        $userIdsToDelete = User::where('role', '!=', 'admin')->pluck('id')->toArray();

        if (empty($userIdsToDelete)) {
            $this->info('✅ ไม่มี User ที่ต้องลบ');

            return Command::SUCCESS;
        }

        // ปิด foreign key checks ชั่วคราว
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // 1. ลบข้อมูลจากตารางที่เกี่ยวข้อง
            $this->deleteRelatedData($userIdsToDelete);

            // 2. ลบไฟล์ที่ upload
            $this->deleteUserFiles($userIdsToDelete);

            // 3. ลบ Users
            $this->deleteUsers($userIdsToDelete);

            // 4. Reset auto increment ถ้าต้องการ
            $this->resetAutoIncrements();

            $this->info('');
            $this->info('✨ ลบข้อมูลเสร็จสิ้น!');
            $this->info("   • ลบ Users: {$userCount} คน");
            $this->info("   • Admin ที่เหลือ: {$adminCount} คน");

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: '.$e->getMessage());

            return Command::FAILURE;
        } finally {
            // เปิด foreign key checks กลับ
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return Command::SUCCESS;
    }

    /**
     * แสดงข้อมูลที่จะลบ (Dry Run)
     */
    private function showDryRun(): void
    {
        $userIds = User::where('role', '!=', 'admin')->pluck('id')->toArray();

        if (empty($userIds)) {
            $this->info('ไม่มี User ที่ต้องลบ');

            return;
        }

        $this->info('');
        $this->info('📋 ตารางที่จะลบข้อมูล:');

        foreach ($this->tablesWithUserId as $table) {
            if (Schema::hasTable($table)) {
                $column = $this->getUserIdColumn($table);
                if ($column && Schema::hasColumn($table, $column)) {
                    $count = DB::table($table)->whereIn($column, $userIds)->count();
                    if ($count > 0) {
                        $this->info("   • {$table}: {$count} records");
                    }
                }
            }
        }

        $this->info('');
        $this->info('📁 โฟลเดอร์ที่จะลบไฟล์:');
        foreach ($this->userStorageFolders as $folder) {
            if (Storage::disk('public')->exists($folder)) {
                $files = Storage::disk('public')->allFiles($folder);
                $count = count($files);
                if ($count > 0) {
                    $this->info("   • {$folder}: {$count} files");
                }
            }
        }
    }

    /**
     * ลบข้อมูลจากตารางที่เกี่ยวข้อง
     */
    private function deleteRelatedData(array $userIds): void
    {
        $this->info('📋 กำลังลบข้อมูลจากตารางที่เกี่ยวข้อง...');

        $totalDeleted = 0;

        foreach ($this->tablesWithUserId as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $column = $this->getUserIdColumn($table);
            if (! $column || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            try {
                $count = DB::table($table)->whereIn($column, $userIds)->count();
                if ($count > 0) {
                    DB::table($table)->whereIn($column, $userIds)->delete();
                    $this->line("   ✓ {$table}: {$count} records");
                    $totalDeleted += $count;
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠ {$table}: ".$e->getMessage());
            }
        }

        $this->info("   รวม: {$totalDeleted} records");
    }

    /**
     * หา column name ที่เก็บ user_id
     */
    private function getUserIdColumn(string $table): ?string
    {
        // ตารางพิเศษที่ใช้ชื่อ column อื่น
        $specialColumns = [
            'mlm_members' => 'user_id',
            'employees' => 'user_id',
            'vendor_stores' => 'user_id',
            'ai_bot_profiles' => 'owner_id',
            'ai_bot_rentals' => 'renter_id',
            'ai_owner_earnings' => 'owner_id',
            'products' => 'seller_id',
            'orders' => 'user_id',
            'tickets' => 'user_id',
            'forum_posts' => 'user_id',
        ];

        if (isset($specialColumns[$table])) {
            return $specialColumns[$table];
        }

        // Default: user_id
        if (Schema::hasColumn($table, 'user_id')) {
            return 'user_id';
        }

        // ลอง owner_id
        if (Schema::hasColumn($table, 'owner_id')) {
            return 'owner_id';
        }

        // ลอง seller_id
        if (Schema::hasColumn($table, 'seller_id')) {
            return 'seller_id';
        }

        // ลอง customer_id
        if (Schema::hasColumn($table, 'customer_id')) {
            return 'customer_id';
        }

        return null;
    }

    /**
     * ลบไฟล์ที่ upload
     */
    private function deleteUserFiles(array $userIds): void
    {
        $this->info('');
        $this->info('📁 กำลังลบไฟล์ที่ upload...');

        $totalFiles = 0;

        foreach ($this->userStorageFolders as $folder) {
            if (! Storage::disk('public')->exists($folder)) {
                continue;
            }

            try {
                $files = Storage::disk('public')->allFiles($folder);
                $count = count($files);

                if ($count > 0) {
                    // ลบทั้งโฟลเดอร์
                    Storage::disk('public')->deleteDirectory($folder);
                    // สร้างโฟลเดอร์ใหม่ว่างๆ
                    Storage::disk('public')->makeDirectory($folder);

                    $this->line("   ✓ {$folder}: {$count} files");
                    $totalFiles += $count;
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠ {$folder}: ".$e->getMessage());
            }
        }

        $this->info("   รวม: {$totalFiles} files");
    }

    /**
     * ลบ Users
     */
    private function deleteUsers(array $userIds): void
    {
        $this->info('');
        $this->info('👤 กำลังลบ Users...');

        $count = User::whereIn('id', $userIds)->count();

        // ลบ users (ใช้ delete ปกติ)
        User::whereIn('id', $userIds)->delete();

        $this->info("   ✓ ลบ Users: {$count} คน");
    }

    /**
     * Reset auto increment values
     */
    private function resetAutoIncrements(): void
    {
        // ไม่ reset เพื่อป้องกันปัญหา ID ซ้ำ
        // สามารถเปิดใช้งานได้ถ้าต้องการ
        /*
        $tables = ['users', 'mlm_members', 'wallets', 'orders'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $maxId = DB::table($table)->max('id') ?? 0;
                DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = " . ($maxId + 1));
            }
        }
        */
    }
}
