<?php

namespace App\Console\Commands;

use App\Services\DemoDataUserGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * คำสั่งสำหรับจัดการข้อมูล Demo
 *
 * ใช้สำหรับลบข้อมูล demo และสามารถเลือกได้ว่าจะลบข้อมูลประเภทไหน
 *
 * 🛡️ (2026-10-04) K1-4: หมวดผู้ใช้/KYC เลือกแถวผ่าน DemoDataUserGuard เท่านั้น
 *    ห้ามลบ: บัญชีร้านทางการ · แอดมิน/ทีมงาน · เจ้าของร้าน/ผู้ขายตลาดสด/ไรเดอร์ · คนที่มีออเดอร์/เงินจริง
 *    ลบผู้ใช้ทีละคนโดยเปิด FOREIGN_KEY_CHECKS (ตารางลูกถูก cascade, FK แบบ restrict = ข้ามคนนั้น)
 *    `--dry-run` = แสดงรายชื่อที่จะลบ/ที่ถูกกันไว้ โดยไม่ลบอะไร
 */
class ResetDemoData extends Command
{
    /**
     * ชื่อและรูปแบบของคำสั่ง
     *
     * @var string
     */
    protected $signature = 'demo:reset
                            {--fresh : เคลียร์ฐานข้อมูลทั้งหมดและ migrate ใหม่}
                            {--all : ลบข้อมูล demo ทั้งหมด}
                            {--users : ลบเฉพาะผู้ใช้ demo}
                            {--pages : ลบเฉพาะหน้า demo pages}
                            {--kyc : ลบเฉพาะ KYC demo}
                            {--line : ลบเฉพาะ LINE demo sessions}
                            {--accounting : ลบเฉพาะข้อมูลบัญชี demo}
                            {--dry-run : แสดงรายชื่อผู้ใช้ที่จะลบและที่ถูกกันไว้ โดยไม่ลบอะไร}
                            {--force : ไม่ถามยืนยัน (ใช้ระวัง!)}';

    /**
     * คำอธิบายคำสั่ง
     *
     * @var string
     */
    protected $description = 'จัดการข้อมูล Demo - ลบและรีเซ็ตข้อมูลทดสอบ (สามารถเลือกได้ว่าจะลบอะไรบ้าง)';

    /**
     * รายการตารางที่เป็น Demo Data แยกตามหมวดหมู่
     *
     * @var array
     */
    protected $demoTables = [
        'users' => [
            'label' => 'ผู้ใช้ทดสอบ (Demo Users)',
            'tables' => ['users', 'affiliates', 'commissions'],
            // เลือกแถวผ่าน DemoDataUserGuard (ดู cleanDemoUsers) — ห้ามใช้ whereRaw อีเมลตรงๆ
            'condition' => null,
            'handler' => 'cleanDemoUsers',
            'warning' => '⚠️  จะลบเฉพาะผู้ใช้ทดสอบ (@example.com / @thaiprompt.com) ที่ไม่ใช่ร้านทางการ แอดมิน เจ้าของร้าน ไรเดอร์ หรือผู้ที่มีออเดอร์/เงินจริง',
        ],
        'pages' => [
            'label' => 'หน้าเพจทดสอบ (Demo Pages)',
            'tables' => ['pages'],
            'condition' => "type IN ('about', 'faq', 'contact', 'terms', 'privacy', 'custom')",
            'warning' => '⚠️  จะลบหน้า About, FAQ, Contact, Terms, Privacy',
        ],
        'kyc' => [
            'label' => 'KYC ทดสอบ (Demo KYC)',
            'tables' => ['kyc_verifications'],
            // เดิมลบ KYC ทุกสถานะ = KYC ของลูกค้าจริงทั้งหมด → จำกัดเฉพาะผู้ใช้ทดสอบที่ลบได้
            'condition' => null,
            'handler' => 'cleanDemoKyc',
            'warning' => '⚠️  จะลบข้อมูล KYC ของผู้ใช้ทดสอบที่ลบได้เท่านั้น',
        ],
        'line' => [
            'label' => 'LINE Sessions ทดสอบ (Demo LINE)',
            'tables' => ['line_signup_sessions', 'line_bot_ai_profiles'],
            'condition' => null,
            'warning' => '⚠️  จะลบ LINE signup sessions และ bot profiles ทดสอบ',
        ],
        'accounting' => [
            'label' => 'ข้อมูลบัญชีทดสอบ (Demo Accounting)',
            'tables' => [
                'accounting_journal_entries',
                'accounting_transactions',
                'accounting_accounts',
            ],
            'condition' => "description LIKE '%demo%' OR description LIKE '%ทดสอบ%'",
            'warning' => '⚠️  จะลบรายการบัญชีที่มีคำว่า "demo" หรือ "ทดสอบ"',
        ],
    ];

    /**
     * ตารางที่เป็น Essential Data (ห้ามลบ)
     *
     * @var array
     */
    protected $essentialTables = [
        'migrations',
        'app_settings',
        'settings',
        'mlm_global_settings',
        'mlm_plans',
        'mlm_packages',
        'ranks',
        'payment_gateways',
        'crypto_currencies',
        'ai_providers',
        'email_templates',
        'product_categories',
        'line_oa_settings',
        'theme_presets',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->displayHeader();

        // ดูอย่างเดียว — ไม่ลบอะไร (ใช้ได้ทุก environment)
        if ($this->option('dry-run')) {
            return $this->showDryRun();
        }

        // ตรวจสอบว่าเป็น production หรือไม่
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('🚫 คุณกำลังอยู่ใน Production environment!');
            $this->warn('   ใช้ --force ถ้าต้องการดำเนินการจริงๆ');

            return 1;
        }

        // กรณีใช้ --fresh
        if ($this->option('fresh')) {
            return $this->handleFreshMigration();
        }

        // กรณีใช้ --all
        if ($this->option('all')) {
            return $this->cleanAllDemoData();
        }

        // ตรวจสอบว่ามี option อะไรถูกเลือกบ้าง
        $selectedOptions = $this->getSelectedOptions();

        if (empty($selectedOptions)) {
            // ถ้าไม่มี option ให้แสดง interactive menu
            return $this->showInteractiveMenu();
        }

        // ลบข้อมูลตาม options ที่เลือก
        return $this->cleanSelectedDemoData($selectedOptions);
    }

    /**
     * แสดง Header ของคำสั่ง
     *
     * @return void
     */
    protected function displayHeader()
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                                                                ║');
        $this->info('║          🧹 ระบบจัดการข้อมูล Demo Data                        ║');
        $this->info('║          ลบและรีเซ็ตข้อมูลทดสอบอย่างปลอดภัย                   ║');
        $this->info('║                                                                ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * จัดการ migrate:fresh
     *
     * @return int
     */
    protected function handleFreshMigration()
    {
        $this->warn('⚠️  โหมด FRESH MIGRATION');
        $this->warn('   จะลบฐานข้อมูลทั้งหมดและสร้างใหม่!');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('คุณแน่ใจหรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!', false)) {
                $this->info('ยกเลิกการดำเนินการ');

                return 0;
            }

            // ยืนยันอีกครั้ง
            if (! $this->confirm('ยืนยันอีกครั้ง - ต้องการลบทุกอย่างและเริ่มใหม่?', false)) {
                $this->info('ยกเลิกการดำเนินการ');

                return 0;
            }
        }

        $this->info('🔄 กำลัง migrate:fresh...');
        Artisan::call('migrate:fresh', [], $this->getOutput());

        $this->newLine();
        $this->info('🌱 กำลัง seed ข้อมูลเริ่มต้น...');
        Artisan::call('db:seed', [], $this->getOutput());

        $this->displaySuccess();

        return 0;
    }

    /**
     * ลบข้อมูล demo ทั้งหมด
     *
     * @return int
     */
    protected function cleanAllDemoData()
    {
        $this->warn('⚠️  จะลบข้อมูล Demo ทั้งหมด!');
        $this->newLine();

        // แสดงรายการที่จะลบ
        $this->line('📋 รายการที่จะลบ:');
        foreach ($this->demoTables as $key => $config) {
            $this->line("   • {$config['label']}");
        }
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('ดำเนินการต่อ?', false)) {
                $this->info('ยกเลิกการดำเนินการ');

                return 0;
            }
        }

        // ลบทีละหมวดหมู่
        foreach (array_keys($this->demoTables) as $category) {
            $this->cleanDemoCategory($category);
        }

        $this->displaySuccess();

        return 0;
    }

    /**
     * แสดง Interactive Menu
     *
     * @return int
     */
    protected function showInteractiveMenu()
    {
        $this->info('เลือกข้อมูล Demo ที่ต้องการลบ:');
        $this->newLine();

        $choices = [];
        $index = 1;

        foreach ($this->demoTables as $key => $config) {
            $this->line("  {$index}) {$config['label']}");
            $this->line("     {$config['warning']}");
            $this->newLine();
            $choices[$index] = $key;
            $index++;
        }

        $this->line("  {$index}) ลบทั้งหมด (All)");
        $this->line('  0) ยกเลิก');
        $this->newLine();

        $choice = $this->ask('กรุณาเลือก (ใส่หมายเลข หรือคั่นด้วย , เช่น 1,2,3)');

        if ($choice === '0' || $choice === null) {
            $this->info('ยกเลิกการดำเนินการ');

            return 0;
        }

        // แปลง input เป็น array
        $selected = array_map('trim', explode(',', $choice));
        $categoriesToClean = [];

        foreach ($selected as $num) {
            if ($num == $index) {
                // เลือกทั้งหมด
                return $this->cleanAllDemoData();
            }

            if (isset($choices[(int) $num])) {
                $categoriesToClean[] = $choices[(int) $num];
            }
        }

        if (empty($categoriesToClean)) {
            $this->error('ไม่มีตัวเลือกที่ถูกต้อง');

            return 1;
        }

        return $this->cleanSelectedDemoData($categoriesToClean);
    }

    /**
     * ดึง options ที่ถูกเลือก
     *
     * @return array
     */
    protected function getSelectedOptions()
    {
        $selected = [];

        foreach (array_keys($this->demoTables) as $key) {
            if ($this->option($key)) {
                $selected[] = $key;
            }
        }

        return $selected;
    }

    /**
     * ลบข้อมูล demo ที่เลือก
     *
     * @return int
     */
    protected function cleanSelectedDemoData(array $categories)
    {
        $this->newLine();
        $this->info('📋 จะลบข้อมูลดังนี้:');

        foreach ($categories as $category) {
            if (isset($this->demoTables[$category])) {
                $config = $this->demoTables[$category];
                $this->line("   • {$config['label']}");
            }
        }
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('ดำเนินการต่อ?', false)) {
                $this->info('ยกเลิกการดำเนินการ');

                return 0;
            }
        }

        foreach ($categories as $category) {
            $this->cleanDemoCategory($category);
        }

        $this->displaySuccess();

        return 0;
    }

    /**
     * ลบข้อมูล demo ตามหมวดหมู่
     *
     * @return void
     */
    protected function cleanDemoCategory(string $category)
    {
        if (! isset($this->demoTables[$category])) {
            $this->error("ไม่พบหมวดหมู่: {$category}");

            return;
        }

        $config = $this->demoTables[$category];
        $this->info("🧹 กำลังลบ: {$config['label']}");

        // หมวดที่ต้องเลือกแถวแบบปลอดภัย (ผู้ใช้/KYC) มีตัวจัดการของตัวเอง — ไม่ปิด FK checks
        if (! empty($config['handler'])) {
            $this->{$config['handler']}();
            $this->newLine();

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($config['tables'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->line("   ⊘ ข้าม {$table} (ไม่มีตาราง)");

                continue;
            }

            try {
                if ($config['condition']) {
                    // SECURITY: conditions ถูกกำหนดไว้ในโค้ดเท่านั้น ห้ามรับค่าจาก user input
                    // ลบแบบมีเงื่อนไข
                    $count = DB::table($table)->whereRaw($config['condition'])->count();
                    DB::table($table)->whereRaw($config['condition'])->delete();
                    $this->line("   ✓ ลบ {$table} ({$count} records)");
                } else {
                    // ลบทั้งตาราง
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->line("   ✓ ล้าง {$table} ({$count} records)");
                }
            } catch (\Exception $e) {
                $this->line("   ✗ ผิดพลาด {$table}: {$e->getMessage()}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->newLine();
    }

    /**
     * ลบผู้ใช้ทดสอบที่ลบได้จริง (ผ่าน DemoDataUserGuard) — ทีละคน เปิด FK checks
     */
    protected function cleanDemoUsers(): void
    {
        // คำนวณแผนใหม่ตอนลบจริง (ไม่ใช้ผลที่แสดงไว้ก่อนหน้า — ระหว่างรอยืนยันอาจมีออเดอร์/ร้านใหม่)
        $plan = app(DemoDataUserGuard::class)->plan();
        $ids = array_map(fn (array $row) => $row['id'], $plan['deletable']);

        $this->line('   🛡️  กันไว้ไม่ลบ '.count($plan['protected']).' บัญชี (ร้านทางการ/แอดมิน/ร้าน/ไรเดอร์/มีเงินจริง)');

        // หมวดอื่นปิด FK checks ระหว่างลบ — หมวดนี้ต้องเปิดเสมอ (ให้ FK เป็นด่านสุดท้าย)
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        if ($ids === []) {
            $this->line('   ⊘ ไม่มีผู้ใช้ทดสอบที่ลบได้');

            return;
        }

        // ตารางเก่าที่อ้าง user_id (ถ้ามี) — ลบพร้อมผู้ใช้คนนั้นใน transaction เดียวกัน
        $legacyTables = array_values(array_filter(
            ['affiliates', 'commissions'],
            fn (string $table) => Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')
        ));

        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            try {
                // FK checks เปิดอยู่: ตารางลูกแบบ cascade ถูกลบตาม · แบบ restrict = ลบไม่ได้ → ย้อนทั้งคน แล้วข้าม
                $deleted += DB::transaction(function () use ($id, $legacyTables) {
                    foreach ($legacyTables as $table) {
                        DB::table($table)->where('user_id', $id)->delete();
                    }

                    return DB::table('users')->where('id', $id)->delete();
                });
            } catch (\Throwable $e) {
                $skipped++;
                $this->line("   ✗ ข้ามผู้ใช้ #{$id} (ยังมีข้อมูลอื่นอ้างอิงอยู่)");
            }
        }

        $this->line("   ✓ ลบ users ({$deleted} records)".($skipped > 0 ? " · ข้าม {$skipped} บัญชี" : ''));
    }

    /**
     * ลบ KYC เฉพาะของผู้ใช้ทดสอบที่ลบได้ (ไม่แตะ KYC ลูกค้าจริง)
     */
    protected function cleanDemoKyc(): void
    {
        if (! Schema::hasTable('kyc_verifications')) {
            $this->line('   ⊘ ข้าม kyc_verifications (ไม่มีตาราง)');

            return;
        }

        $ids = app(DemoDataUserGuard::class)->deletableUserIds();

        $count = 0;
        foreach (array_chunk($ids, 500) as $chunk) {
            $count += DB::table('kyc_verifications')->whereIn('user_id', $chunk)->delete();
        }

        $this->line("   ✓ ลบ kyc_verifications ของผู้ใช้ทดสอบ ({$count} records)");
    }

    /**
     * จำนวน KYC ของผู้ใช้ชุดนี้
     *
     * @param  array<int, int>  $userIds
     */
    protected function countKycOf(array $userIds): int
    {
        if ($userIds === [] || ! Schema::hasTable('kyc_verifications')) {
            return 0;
        }

        $count = 0;
        foreach (array_chunk($userIds, 500) as $chunk) {
            $count += DB::table('kyc_verifications')->whereIn('user_id', $chunk)->count();
        }

        return $count;
    }

    /**
     * --dry-run: แสดงรายชื่อที่จะลบ/ที่ถูกกันไว้พร้อมเหตุผล — ไม่ลบอะไร
     */
    protected function showDryRun(): int
    {
        $plan = app(DemoDataUserGuard::class)->plan();

        $this->info('🔍 โหมดดูอย่างเดียว (dry-run) — ไม่มีการลบข้อมูล');
        $this->newLine();

        $this->info('🗑️  ผู้ใช้ทดสอบที่จะถูกลบ: '.count($plan['deletable']));
        if ($plan['deletable'] !== []) {
            $this->table(['ID', 'Email', 'ชื่อ'], array_map(
                fn (array $row) => [$row['id'], $row['email'], $row['name']],
                $plan['deletable']
            ));
        }

        $this->newLine();
        $this->info('🛡️  กันไว้ไม่ลบ: '.count($plan['protected']));
        if ($plan['protected'] !== []) {
            $this->table(['ID', 'Email', 'เหตุผล'], array_map(
                fn (array $row) => [
                    $row['id'],
                    $row['email'],
                    implode(', ', array_map([DemoDataUserGuard::class, 'reasonLabel'], $row['reasons'])),
                ],
                $plan['protected']
            ));
        }

        return 0;
    }

    /**
     * แสดงข้อความเมื่อสำเร็จ
     *
     * @return void
     */
    protected function displaySuccess()
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                                                                ║');
        $this->info('║                    ✅ ดำเนินการสำเร็จ!                         ║');
        $this->info('║                                                                ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->info('💡 ข้อมูล Demo ที่เหลืออยู่:');
        $this->newLine();

        // แสดงจำนวนข้อมูลที่เหลือ
        foreach ($this->demoTables as $category => $config) {
            // หมวดผู้ใช้: นับเฉพาะผู้ใช้ทดสอบที่ยังลบได้ (ไม่ใช่ทั้งตาราง users)
            if ($category === 'users' || $category === 'kyc') {
                $demoIds = app(DemoDataUserGuard::class)->deletableUserIds();
                $left = $category === 'users' ? count($demoIds) : $this->countKycOf($demoIds);
                if ($left > 0) {
                    $this->line("   • {$config['label']}: {$left} records");
                }

                continue;
            }

            $totalRecords = 0;
            foreach ($config['tables'] as $table) {
                if (Schema::hasTable($table)) {
                    $totalRecords += DB::table($table)->count();
                }
            }

            if ($totalRecords > 0) {
                $this->line("   • {$config['label']}: {$totalRecords} records");
            }
        }

        $this->newLine();
        $this->info('📧 บัญชี Demo ที่สามารถใช้ได้:');
        $this->line('   Super Admin: superadmin@thaiprompt.com');
        $this->line('   Admin: admin@thaiprompt.com');
        $this->line('   Manager: manager@thaiprompt.com');
        $this->line('   Password: password123');
        $this->newLine();
    }
}
