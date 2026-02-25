<?php

namespace App\Console\Commands;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FortuneCommissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ทดสอบระบบปันผลดูดวง End-to-End แบบ Simulation
 *
 * สร้างข้อมูลจำลอง → เรียก service → ตรวจสอบผลลัพธ์ทุกขั้นตอน
 * รองรับการรันทั้งแบบ interactive (local) และ CI mode (GitHub Actions)
 */
class FortuneTestCommission extends Command
{
    /**
     * ชื่อ command และ arguments
     *
     * @var string
     */
    protected $signature = 'fortune:test-commission
        {--ci : รันในโหมด CI (ไม่มี interactive, exit code ตามผล)}
        {--cleanup : ลบข้อมูลทดสอบหลังรันเสร็จ}
        {--verbose-output : แสดงรายละเอียดทุกขั้นตอน}';

    /**
     * คำอธิบาย command
     *
     * @var string
     */
    protected $description = '🔮 ทดสอบระบบปันผลดูดวง (Fortune Commission) แบบ End-to-End Simulation';

    /**
     * จำนวน test ที่ผ่าน/ไม่ผ่าน
     */
    protected int $passed = 0;

    protected int $failed = 0;

    protected array $results = [];

    /**
     * ข้อมูลทดสอบที่สร้างขึ้น (สำหรับ cleanup)
     */
    protected array $createdIds = [
        'users' => [],
        'mlm_members' => [],
        'fortune_readings' => [],
        'fortune_commissions' => [],
        'wallets' => [],
    ];

    /**
     * รัน command หลัก
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $isCi = $this->option('ci');

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║  🔮 Fortune Commission E2E Simulation Test              ║');
        $this->line('║  ทดสอบระบบปันผลดูดวง Level 1 + Level 2 แบบ End-to-End  ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($isCi) {
            $this->info('🤖 โหมด CI: รันอัตโนมัติ ไม่มี interactive prompts');
        }

        try {
            // ===== PHASE 1: เตรียมสภาพแวดล้อม =====
            $this->runTest('Phase 1: เตรียมสภาพแวดล้อม', function () {
                return $this->setupEnvironment();
            });

            // ===== PHASE 2: ทดสอบ Level 1 Commission (Fixed) =====
            $this->runTest('Phase 2: Level 1 Commission (Fixed Amount)', function () {
                return $this->testLevel1FixedCommission();
            });

            // ===== PHASE 3: ทดสอบ Level 2 Commission (Fixed) =====
            $this->runTest('Phase 3: Level 2 Commission (Fixed Amount)', function () {
                return $this->testLevel2FixedCommission();
            });

            // ===== PHASE 4: ทดสอบ Percent Commission =====
            $this->runTest('Phase 4: Percent Commission คำนวณถูกต้อง', function () {
                return $this->testPercentCommission();
            });

            // ===== PHASE 5: ทดสอบ Level 2 Disabled =====
            $this->runTest('Phase 5: Level 2 ปิด → ไม่จ่ายชั้นหลาน', function () {
                return $this->testLevel2Disabled();
            });

            // ===== PHASE 6: ทดสอบ Duplicate Prevention =====
            $this->runTest('Phase 6: ป้องกันจ่ายซ้ำ (Duplicate Prevention)', function () {
                return $this->testDuplicatePrevention();
            });

            // ===== PHASE 7: ทดสอบ Orphan User =====
            $this->runTest('Phase 7: ไม่มี Sponsor → ไม่ได้ Commission', function () {
                return $this->testOrphanUser();
            });

            // ===== PHASE 8: ทดสอบ Deep Tree (4 ชั้น → จ่ายแค่ 2) =====
            $this->runTest('Phase 8: สายงาน 4 ชั้น → จ่ายแค่ Level 1+2', function () {
                return $this->testDeepTreeOnly2Levels();
            });

            // ===== PHASE 9: ทดสอบ Wallet Balance =====
            $this->runTest('Phase 9: Wallet Balance เพิ่มขึ้นถูกต้อง', function () {
                return $this->testWalletBalance();
            });

            // ===== PHASE 10: ทดสอบ Free Reading =====
            $this->runTest('Phase 10: ดูดวงฟรี (amount=0) → ไม่จ่ายคอมฯ', function () {
                return $this->testFreeReading();
            });

        } catch (\Throwable $e) {
            $this->error("❌ Fatal Error: {$e->getMessage()}");
            $this->line("   📍 {$e->getFile()}:{$e->getLine()}");
            $this->failed++;
            $this->results[] = ['Fatal Error', false, $e->getMessage()];
        }

        // ===== ลบข้อมูลทดสอบ =====
        if ($this->option('cleanup') || $isCi) {
            $this->cleanup();
        }

        // ===== สรุปผลลัพธ์ =====
        $elapsed = round(microtime(true) - $startTime, 2);

        return $this->printSummary($elapsed, $isCi);
    }

    /**
     * รัน test case พร้อม try-catch
     */
    protected function runTest(string $name, callable $test): void
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("  🧪 {$name}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            $result = $test();
            if ($result === true) {
                $this->passed++;
                $this->results[] = [$name, true, ''];
                $this->info("  ✅ PASSED: {$name}");
            } else {
                $this->failed++;
                $reason = is_string($result) ? $result : 'ไม่ผ่าน';
                $this->results[] = [$name, false, $reason];
                $this->error("  ❌ FAILED: {$name} - {$reason}");
            }
        } catch (\Throwable $e) {
            $this->failed++;
            $this->results[] = [$name, false, $e->getMessage()];
            $this->error("  ❌ EXCEPTION: {$name}");
            $this->line("     💬 {$e->getMessage()}");
            $this->line("     📍 {$e->getFile()}:{$e->getLine()}");
        }

        $this->newLine();
    }

    // =====================================================
    // PHASE 1: เตรียมสภาพแวดล้อม
    // =====================================================
    protected function setupEnvironment(): bool|string
    {
        $this->line('  📋 ตรวจสอบตาราง fortune_commissions...');

        if (! \Schema::hasTable('fortune_commissions')) {
            return 'ตาราง fortune_commissions ไม่มี - กรุณารัน migration ก่อน';
        }

        if (! \Schema::hasTable('mlm_members')) {
            return 'ตาราง mlm_members ไม่มี';
        }

        if (! \Schema::hasTable('fortune_readings')) {
            return 'ตาราง fortune_readings ไม่มี';
        }

        // ตรวจสอบคอลัมน์ Level 2 ใน fortune_telling_settings
        if (\Schema::hasTable('fortune_telling_settings')) {
            $hasL2 = \Schema::hasColumn('fortune_telling_settings', 'fortune_level2_enabled');
            if (! $hasL2) {
                return 'คอลัมน์ fortune_level2_enabled ไม่มี - กรุณารัน migration';
            }
            $this->line('  ✓ คอลัมน์ Level 2 settings พร้อม');
        }

        // ปิด volume retention เพื่อให้ test ง่าย
        // ⚠️ ใช้ '0' ไม่ใช่ 'false' เพราะ PHP: (bool)'false' = true
        MlmGlobalSetting::updateOrCreate(
            ['key' => 'volume_retention_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'retention']
        );
        \Illuminate\Support\Facades\Cache::flush();
        $this->line('  ✓ ปิด volume_retention_enabled (ให้ทุก member active)');

        $this->line('  ✓ สภาพแวดล้อมพร้อมสำหรับทดสอบ');

        return true;
    }

    // =====================================================
    // PHASE 2: Level 1 Commission (Fixed)
    // =====================================================
    protected function testLevel1FixedCommission(): bool|string
    {
        // สร้าง settings: L1 fixed 15 บาท
        $settings = $this->createSettings('fixed', 15, true, 'fixed', 5);
        $this->line("  📊 ตั้งค่า: L1 = fixed 15 บาท, L2 = fixed 5 บาท");

        // สร้าง users: Sponsor(A) → Member(B)
        $userA = $this->createUser('ทดสอบ-Sponsor-A');
        $userB = $this->createUser('ทดสอบ-Member-B');

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);
        $this->line("  👥 สร้างสายงาน: A(#{$memberA->id}) → B(#{$memberB->id})");

        // B จ่ายค่าดูดวง 99 บาท
        $reading = $this->createReading($userB, 99);
        $this->line("  💰 B จ่ายค่าดูดวง: 99 บาท (Bill #{$reading->id})");

        // เรียก service
        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $memberB, $settings);

        // ตรวจสอบ: A ต้องได้ Level 1 = 15 บาท
        $commission = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userA->id)
            ->where('level', 1)
            ->first();

        if (! $commission) {
            return 'ไม่พบ commission Level 1 สำหรับ Sponsor A';
        }

        if ((float) $commission->amount !== 15.00) {
            return "จำนวนผิด: คาดหวัง 15.00, ได้ {$commission->amount}";
        }

        if ($commission->commission_type !== 'fixed') {
            return "ประเภทผิด: คาดหวัง fixed, ได้ {$commission->commission_type}";
        }

        $this->line("  ✓ A ได้ Level 1 Commission = {$commission->amount} บาท (fixed)");

        return true;
    }

    // =====================================================
    // PHASE 3: Level 2 Commission (Fixed)
    // =====================================================
    protected function testLevel2FixedCommission(): bool|string
    {
        // สร้าง settings: L1 fixed 10, L2 fixed 5
        $settings = $this->createSettings('fixed', 10, true, 'fixed', 5);

        // สร้างสายงาน: A → B → C
        $userA = $this->createUser('ทดสอบ-Grandparent-A2');
        $userB = $this->createUser('ทดสอบ-Sponsor-B2');
        $userC = $this->createUser('ทดสอบ-Member-C2');

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);
        $memberC = $this->createMlmMember($userC, $memberB->id);
        $this->line("  👥 สายงาน: A(#{$memberA->id}) → B(#{$memberB->id}) → C(#{$memberC->id})");

        // C จ่ายค่าดูดวง
        $reading = $this->createReading($userC, 99);
        $this->line("  💰 C จ่ายค่าดูดวง: 99 บาท");

        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $memberC, $settings);

        // ตรวจ Level 1: B ต้องได้ 10 บาท
        $commL1 = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userB->id)
            ->where('level', 1)
            ->first();

        if (! $commL1 || (float) $commL1->amount !== 10.00) {
            return 'Level 1: B ไม่ได้ 10 บาท - ได้ ' . ($commL1 ? $commL1->amount : 'null');
        }
        $this->line("  ✓ B ได้ Level 1 = {$commL1->amount} บาท");

        // ตรวจ Level 2: A ต้องได้ 5 บาท
        $commL2 = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userA->id)
            ->where('level', 2)
            ->first();

        if (! $commL2 || (float) $commL2->amount !== 5.00) {
            return 'Level 2: A ไม่ได้ 5 บาท - ได้ ' . ($commL2 ? $commL2->amount : 'null');
        }
        $this->line("  ✓ A ได้ Level 2 = {$commL2->amount} บาท");

        return true;
    }

    // =====================================================
    // PHASE 4: Percent Commission
    // =====================================================
    protected function testPercentCommission(): bool|string
    {
        // ตั้ง L1 = 10%, L2 = 5%
        $settings = $this->createSettings('percent', 10, true, 'percent', 5);

        $userA = $this->createUser('ทดสอบ-GP-Pct');
        $userB = $this->createUser('ทดสอบ-SP-Pct');
        $userC = $this->createUser('ทดสอบ-Mb-Pct');

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);
        $memberC = $this->createMlmMember($userC, $memberB->id);

        // C จ่าย 200 บาท
        $reading = $this->createReading($userC, 200);
        $this->line("  💰 C จ่าย 200 บาท (percent mode: L1=10%, L2=5%)");

        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $memberC, $settings);

        // L1: B ต้องได้ 200 × 10% = 20 บาท
        $commL1 = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userB->id)
            ->where('level', 1)
            ->first();

        $expectedL1 = 20.00;
        if (! $commL1 || abs((float) $commL1->amount - $expectedL1) > 0.01) {
            return "L1: คาดหวัง {$expectedL1}, ได้ " . ($commL1 ? $commL1->amount : 'null');
        }
        $this->line("  ✓ B ได้ Level 1 = {$commL1->amount} บาท (10% ของ 200)");

        // L2: A ต้องได้ 200 × 5% = 10 บาท
        $commL2 = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userA->id)
            ->where('level', 2)
            ->first();

        $expectedL2 = 10.00;
        if (! $commL2 || abs((float) $commL2->amount - $expectedL2) > 0.01) {
            return "L2: คาดหวัง {$expectedL2}, ได้ " . ($commL2 ? $commL2->amount : 'null');
        }
        $this->line("  ✓ A ได้ Level 2 = {$commL2->amount} บาท (5% ของ 200)");

        return true;
    }

    // =====================================================
    // PHASE 5: Level 2 Disabled
    // =====================================================
    protected function testLevel2Disabled(): bool|string
    {
        // ตั้ง L2 disabled
        $settings = $this->createSettings('fixed', 10, false, 'fixed', 5);
        $this->line('  🚫 ตั้งค่า: Level 2 = ปิด');

        $userA = $this->createUser('ทดสอบ-GP-Off');
        $userB = $this->createUser('ทดสอบ-SP-Off');
        $userC = $this->createUser('ทดสอบ-Mb-Off');

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);
        $memberC = $this->createMlmMember($userC, $memberB->id);

        $reading = $this->createReading($userC, 99);

        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $memberC, $settings);

        // L1 ต้องมี
        $commL1 = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('level', 1)->first();
        if (! $commL1) {
            return 'Level 1 ไม่ถูกสร้าง แม้ว่าควรจะมี';
        }
        $this->line("  ✓ Level 1 ยังจ่ายปกติ ({$commL1->amount} บาท)");

        // L2 ต้องไม่มี
        $commL2 = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('level', 2)->first();
        if ($commL2) {
            return "Level 2 ถูกสร้างทั้งที่ปิด! amount={$commL2->amount}";
        }
        $this->line('  ✓ Level 2 ไม่ถูกจ่าย (ปิดตามที่ตั้งค่า)');

        return true;
    }

    // =====================================================
    // PHASE 6: Duplicate Prevention
    // =====================================================
    protected function testDuplicatePrevention(): bool|string
    {
        $settings = $this->createSettings('fixed', 10, true, 'fixed', 5);

        $userA = $this->createUser('ทดสอบ-SP-Dup');
        $userB = $this->createUser('ทดสอบ-Mb-Dup');

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);

        $reading = $this->createReading($userB, 99);

        $service = new FortuneCommissionService;

        // เรียก 3 ครั้ง
        $service->distributeCommissions($reading, $memberB, $settings);
        $service->distributeCommissions($reading, $memberB, $settings);
        $service->distributeCommissions($reading, $memberB, $settings);

        $count = FortuneCommission::where('fortune_reading_id', $reading->id)->count();
        if ($count > 1) {
            return "จ่ายซ้ำ! พบ {$count} records (ควรมีแค่ 1)";
        }
        if ($count === 0) {
            return 'ไม่พบ commission เลย';
        }
        $this->line("  ✓ เรียก 3 ครั้ง แต่สร้างแค่ {$count} record (ไม่ซ้ำ)");

        return true;
    }

    // =====================================================
    // PHASE 7: Orphan User (ไม่มี Sponsor)
    // =====================================================
    protected function testOrphanUser(): bool|string
    {
        $settings = $this->createSettings('fixed', 10, true, 'fixed', 5);

        $user = $this->createUser('ทดสอบ-Orphan');
        $member = $this->createMlmMember($user); // ไม่มี sponsor

        $reading = $this->createReading($user, 99);

        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $member, $settings);

        $count = FortuneCommission::where('fortune_reading_id', $reading->id)->count();
        if ($count > 0) {
            return "ไม่มี sponsor แต่ยังสร้าง commission ({$count} records)";
        }
        $this->line('  ✓ ไม่มี sponsor → ไม่สร้าง commission (ถูกต้อง)');

        return true;
    }

    // =====================================================
    // PHASE 8: Deep Tree (4 ชั้น → จ่ายแค่ 2)
    // =====================================================
    protected function testDeepTreeOnly2Levels(): bool|string
    {
        $settings = $this->createSettings('fixed', 10, true, 'fixed', 5);

        // สร้างสายงาน 4 ชั้น: A → B → C → D
        $userA = $this->createUser('ทดสอบ-Deep-A');
        $userB = $this->createUser('ทดสอบ-Deep-B');
        $userC = $this->createUser('ทดสอบ-Deep-C');
        $userD = $this->createUser('ทดสอบ-Deep-D');

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);
        $memberC = $this->createMlmMember($userC, $memberB->id);
        $memberD = $this->createMlmMember($userD, $memberC->id);
        $this->line("  👥 สายงาน 4 ชั้น: A→B→C→D");

        // D จ่ายค่าดูดวง
        $reading = $this->createReading($userD, 99);

        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $memberD, $settings);

        // C ต้องได้ Level 1
        $commC = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userC->id)->first();
        if (! $commC || $commC->level !== 1) {
            return 'C ไม่ได้ Level 1';
        }
        $this->line("  ✓ C ได้ Level 1 = {$commC->amount} บาท");

        // B ต้องได้ Level 2
        $commB = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userB->id)->first();
        if (! $commB || $commB->level !== 2) {
            return 'B ไม่ได้ Level 2';
        }
        $this->line("  ✓ B ได้ Level 2 = {$commB->amount} บาท");

        // A ต้องไม่ได้ (เกิน Level 2)
        $commA = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('user_id', $userA->id)->first();
        if ($commA) {
            return "A ได้ commission ทั้งที่เกินชั้น 2! Level={$commA->level}";
        }
        $this->line('  ✓ A ไม่ได้ commission (เกินชั้น 2 ถูกต้อง)');

        // ต้องมีแค่ 2 records
        $total = FortuneCommission::where('fortune_reading_id', $reading->id)->count();
        if ($total !== 2) {
            return "จำนวนรวมผิด: คาดหวัง 2, ได้ {$total}";
        }
        $this->line("  ✓ รวม {$total} records (Level 1 + Level 2 เท่านั้น)");

        return true;
    }

    // =====================================================
    // PHASE 9: Wallet Balance
    // =====================================================
    protected function testWalletBalance(): bool|string
    {
        $settings = $this->createSettings('fixed', 25, false, 'fixed', 0);

        $userA = $this->createUser('ทดสอบ-Wallet-A');
        $userB = $this->createUser('ทดสอบ-Wallet-B');

        // สร้าง wallet ให้ A มียอดเริ่มต้น 100 บาท
        $wallet = Wallet::create([
            'user_id' => $userA->id,
            'balance' => 100.00,
            'currency' => 'THB',
            'status' => 'active',
        ]);
        $this->createdIds['wallets'][] = $wallet->id;
        $this->line("  💳 Wallet A ยอดเริ่มต้น: {$wallet->balance} บาท");

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);

        $reading = $this->createReading($userB, 99);

        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $memberB, $settings);

        // รีเฟรช wallet
        $wallet->refresh();
        $expectedBalance = 125.00; // 100 + 25

        if (abs((float) $wallet->balance - $expectedBalance) > 0.01) {
            return "Wallet balance ผิด: คาดหวัง {$expectedBalance}, ได้ {$wallet->balance}";
        }
        $this->line("  ✓ Wallet A ยอดใหม่: {$wallet->balance} บาท (100 + 25 = 125)");

        return true;
    }

    // =====================================================
    // PHASE 10: Free Reading (amount = 0)
    // =====================================================
    protected function testFreeReading(): bool|string
    {
        $settings = $this->createSettings('fixed', 10, true, 'fixed', 5);

        $userA = $this->createUser('ทดสอบ-Free-A');
        $userB = $this->createUser('ทดสอบ-Free-B');

        $memberA = $this->createMlmMember($userA);
        $memberB = $this->createMlmMember($userB, $memberA->id);

        // สร้าง reading ที่ amount_paid = 0
        $reading = $this->createReading($userB, 0);
        $this->line('  🆓 ดูดวงฟรี: amount_paid = 0');

        $service = new FortuneCommissionService;
        $service->distributeCommissions($reading, $memberB, $settings);

        $count = FortuneCommission::where('fortune_reading_id', $reading->id)->count();
        if ($count > 0) {
            return "ดูดวงฟรีแต่ยังจ่ายคอมมิชชั่น ({$count} records)";
        }
        $this->line('  ✓ ดูดวงฟรี → ไม่จ่ายคอมมิชชั่น (ถูกต้อง)');

        return true;
    }

    // =====================================================
    // HELPER: สร้าง User ทดสอบ
    // =====================================================
    protected function createUser(string $name): User
    {
        $user = User::create([
            'name' => $name,
            'email' => 'test-fortune-' . Str::random(8) . '@test.local',
            'password' => bcrypt('test12345'),
        ]);
        $this->createdIds['users'][] = $user->id;

        return $user;
    }

    // =====================================================
    // HELPER: สร้าง MlmMember ทดสอบ
    // =====================================================
    protected function createMlmMember(User $user, ?int $sponsorId = null): MlmMember
    {
        // ดึงหรือสร้าง MlmPlan (จำเป็นสำหรับ FK)
        $plan = \App\Models\MlmPlan::first() ?? \App\Models\MlmPlan::create([
            'name' => 'E2E Test Plan',
            'name_th' => 'แผนทดสอบ E2E',
            'slug' => 'e2e-test-plan-'.strtolower(Str::random(6)),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        $member = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $plan->id,
            'unilevel_sponsor_id' => $sponsorId,
            'original_sponsor_id' => $sponsorId,
            'status' => 'active',
            'member_code' => 'TEST-' . strtoupper(Str::random(6)),
            'total_pv' => 0,
            'joined_at' => now(),
        ]);
        $this->createdIds['mlm_members'][] = $member->id;

        return $member;
    }

    // =====================================================
    // HELPER: สร้าง FortuneTellingSetting ทดสอบ
    // =====================================================
    protected function createSettings(
        string $l1Type,
        float $l1Amount,
        bool $l2Enabled,
        string $l2Type,
        float $l2Amount
    ): FortuneTellingSetting {
        // ดึง settings ที่มีอยู่ หรือสร้างใหม่พร้อม fields บังคับ
        $settings = FortuneTellingSetting::first();
        if ($settings) {
            $settings->update([
                'fortune_level1_commission_type' => $l1Type,
                'fortune_level1_commission_amount' => $l1Amount,
                'fortune_level2_enabled' => $l2Enabled,
                'fortune_level2_commission_type' => $l2Type,
                'fortune_level2_commission_amount' => $l2Amount,
            ]);

            return $settings->fresh();
        }

        return FortuneTellingSetting::create([
            'facebook_app_id' => 'test-e2e-app',
            'facebook_page_id' => 'test-e2e-page',
            'is_enabled' => true,
            'reading_price' => 29,
            'deep_reading_price' => 99,
            'fortune_level1_commission_type' => $l1Type,
            'fortune_level1_commission_amount' => $l1Amount,
            'fortune_level2_enabled' => $l2Enabled,
            'fortune_level2_commission_type' => $l2Type,
            'fortune_level2_commission_amount' => $l2Amount,
        ]);
    }

    // =====================================================
    // HELPER: สร้าง FortuneReading ทดสอบ
    // =====================================================
    protected function createReading(User $user, float $amount): FortuneReading
    {
        $reading = FortuneReading::create([
            'user_id' => $user->id,
            'facebook_user_id' => 'test_e2e_' . Str::random(10),
            'bill_reference' => 'TEST-' . strtoupper(Str::random(8)),
            'questions' => json_encode(['ดูดวงทดสอบ']),
            'categories' => json_encode(['ทั่วไป']),
            'ai_response' => 'ดวงดี (ทดสอบ)',
            'basic_response' => 'ดวงดี (ทดสอบ)',
            'ai_provider' => 'test',
            'ai_model' => 'test-model',
            'tokens_used' => 0,
            'is_paid' => $amount > 0,
            'amount_paid' => $amount,
            'paid_at' => $amount > 0 ? now() : null,
            'reading_type' => 'deep',
            'conversation_status' => $amount > 0 ? 'paid' : 'new',
            'platform' => 'test',
        ]);
        $this->createdIds['fortune_readings'][] = $reading->id;

        return $reading;
    }

    // =====================================================
    // CLEANUP: ลบข้อมูลทดสอบ
    // =====================================================
    protected function cleanup(): void
    {
        $this->line('🧹 กำลังลบข้อมูลทดสอบ...');

        // ลบ fortune_commissions ก่อน (FK)
        if (! empty($this->createdIds['fortune_readings'])) {
            FortuneCommission::whereIn('fortune_reading_id', $this->createdIds['fortune_readings'])
                ->forceDelete();
        }

        // ลบ fortune_readings
        if (! empty($this->createdIds['fortune_readings'])) {
            FortuneReading::whereIn('id', $this->createdIds['fortune_readings'])
                ->forceDelete();
        }

        // ลบ mlm_members (ต้องลบ FK references ก่อน)
        if (! empty($this->createdIds['mlm_members'])) {
            // set sponsor to null ก่อน
            MlmMember::whereIn('id', $this->createdIds['mlm_members'])
                ->update(['unilevel_sponsor_id' => null, 'original_sponsor_id' => null]);
            MlmMember::whereIn('id', $this->createdIds['mlm_members'])
                ->forceDelete();
        }

        // ลบ wallets
        if (! empty($this->createdIds['wallets'])) {
            Wallet::whereIn('id', $this->createdIds['wallets'])
                ->forceDelete();
        }

        // ลบ users
        if (! empty($this->createdIds['users'])) {
            // ลบ wallets ของ users เหล่านี้ด้วย
            Wallet::whereIn('user_id', $this->createdIds['users'])->forceDelete();
            User::whereIn('id', $this->createdIds['users'])->forceDelete();
        }

        $this->line('  ✓ ลบข้อมูลทดสอบเรียบร้อย');
    }

    // =====================================================
    // SUMMARY: สรุปผลลัพธ์
    // =====================================================
    protected function printSummary(float $elapsed, bool $isCi): int
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║  📊 สรุปผลการทดสอบ Fortune Commission E2E              ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // ตาราง results
        $tableData = [];
        foreach ($this->results as $i => $r) {
            $tableData[] = [
                $i + 1,
                $r[0],
                $r[1] ? '✅ PASS' : '❌ FAIL',
                $r[2] ?: '-',
            ];
        }
        $this->table(['#', 'Test', 'Result', 'Detail'], $tableData);

        $total = $this->passed + $this->failed;
        $this->newLine();

        if ($this->failed === 0) {
            $this->info("🎉 ผ่านทั้งหมด! {$this->passed}/{$total} tests ({$elapsed}s)");
            $this->newLine();
            $this->info('╔═══════════════════════════════════════════╗');
            $this->info('║  ✅ ALL FORTUNE COMMISSION TESTS PASSED!  ║');
            $this->info('║  ระบบปันผลดูดวงทำงานถูกต้องสมบูรณ์        ║');
            $this->info('╚═══════════════════════════════════════════╝');

            return self::SUCCESS;
        }

        $this->error("❌ ไม่ผ่าน {$this->failed}/{$total} tests ({$elapsed}s)");
        $this->newLine();
        $this->error('╔═══════════════════════════════════════════╗');
        $this->error('║  ❌ FORTUNE COMMISSION TESTS FAILED!      ║');
        $this->error('║  กรุณาตรวจสอบ error ด้านบน               ║');
        $this->error('╚═══════════════════════════════════════════╝');

        return $isCi ? self::FAILURE : self::SUCCESS;
    }
}
