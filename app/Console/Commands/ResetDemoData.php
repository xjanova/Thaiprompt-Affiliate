<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * คำสั่งสำหรับจัดการข้อมูล Demo
 *
 * ใช้สำหรับลบข้อมูล demo และสามารถเลือกได้ว่าจะลบข้อมูลประเภทไหน
 *
 * @package App\Console\Commands
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
            'condition' => "email LIKE '%@example.com' OR email LIKE '%@thaiprompt.com'",
            'warning' => '⚠️  จะลบผู้ใช้ทดสอบทั้งหมด (ยกเว้น Super Admin ที่สร้างตอนติดตั้ง)',
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
            'condition' => "status IN ('pending', 'approved', 'rejected')",
            'warning' => '⚠️  จะลบข้อมูล KYC verification ทั้งหมด',
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

        // ตรวจสอบว่าเป็น production หรือไม่
        if (app()->environment('production') && !$this->option('force')) {
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

        if (!$this->option('force')) {
            if (!$this->confirm('คุณแน่ใจหรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!', false)) {
                $this->info('ยกเลิกการดำเนินการ');
                return 0;
            }

            // ยืนยันอีกครั้ง
            if (!$this->confirm('ยืนยันอีกครั้ง - ต้องการลบทุกอย่างและเริ่มใหม่?', false)) {
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

        if (!$this->option('force')) {
            if (!$this->confirm('ดำเนินการต่อ?', false)) {
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
        $this->line("  0) ยกเลิก");
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

            if (isset($choices[(int)$num])) {
                $categoriesToClean[] = $choices[(int)$num];
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
     * @param array $categories
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

        if (!$this->option('force')) {
            if (!$this->confirm('ดำเนินการต่อ?', false)) {
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
     * @param string $category
     * @return void
     */
    protected function cleanDemoCategory(string $category)
    {
        if (!isset($this->demoTables[$category])) {
            $this->error("ไม่พบหมวดหมู่: {$category}");
            return;
        }

        $config = $this->demoTables[$category];
        $this->info("🧹 กำลังลบ: {$config['label']}");

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($config['tables'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->line("   ⊘ ข้าม {$table} (ไม่มีตาราง)");
                continue;
            }

            try {
                if ($config['condition']) {
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
