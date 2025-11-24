<?php

namespace App\Console\Commands;

use App\Services\AiRental\BudgetService;
use App\Models\AiRentalBudgetLimit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * AI Rental Reset Budgets Command
 *
 * รีเซ็ต budget limits ที่หมดอายุ period อัตโนมัติ
 * ใช้สำหรับ cron job ทุกวันเวลา 00:00
 */
class AiRentalResetBudgets extends Command
{
    /**
     * Command signature
     *
     * @var string
     */
    protected $signature = 'ai-rental:reset-budgets
                            {--type= : Reset specific budget type only (daily, weekly, monthly)}
                            {--user= : Reset budgets for specific user ID only}
                            {--dry-run : Preview what will be reset without actually resetting}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'รีเซ็ต budget limits ที่หมดอายุ period';

    /**
     * Budget Service
     *
     * @var BudgetService
     */
    protected BudgetService $budgetService;

    /**
     * Statistics
     *
     * @var array
     */
    protected array $stats = [
        'total_expired' => 0,
        'reset_success' => 0,
        'reset_failed' => 0,
        'total_rollover' => 0,
    ];

    /**
     * Constructor
     *
     * @param BudgetService $budgetService
     */
    public function __construct(BudgetService $budgetService)
    {
        parent::__construct();
        $this->budgetService = $budgetService;
    }

    /**
     * Execute the command
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('💰 เริ่มรีเซ็ต Budget Limits ที่หมดอายุ...');
        $this->newLine();

        $startTime = now();

        try {
            // ดึง budgets ที่หมดอายุ
            $expiredBudgets = $this->getExpiredBudgets();

            $this->stats['total_expired'] = $expiredBudgets->count();

            if ($this->stats['total_expired'] === 0) {
                $this->info('✅ ไม่มี budget ที่หมดอายุต้องรีเซ็ต');
                return 0;
            }

            $this->info("📊 พบ {$this->stats['total_expired']} budgets ที่หมดอายุ");
            $this->newLine();

            // แสดง dry-run info
            if ($this->option('dry-run')) {
                $this->warn('🔍 Dry-run mode: จะไม่มีการรีเซ็ตจริง');
                $this->newLine();
            }

            // แสดงรายการ budgets ที่จะรีเซ็ต
            $this->displayExpiredBudgets($expiredBudgets);

            // ถามยืนยัน (ถ้าไม่ใช่ dry-run และไม่ได้ใช้ --force)
            if (!$this->option('dry-run') && !$this->option('no-interaction')) {
                if (!$this->confirm('ต้องการรีเซ็ต budgets เหล่านี้หรือไม่?', true)) {
                    $this->warn('❌ ยกเลิกการรีเซ็ต');
                    return 0;
                }
                $this->newLine();
            }

            // รีเซ็ต budgets
            if (!$this->option('dry-run')) {
                $this->resetBudgets($expiredBudgets);
            }

            // แสดงสรุปผล
            $this->displaySummary($startTime);

            return $this->stats['reset_failed'] > 0 ? 1 : 0;
        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาดในการรีเซ็ต budgets: ' . $e->getMessage());
            Log::error('AI Rental budget reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * ดึง budgets ที่หมดอายุ
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getExpiredBudgets()
    {
        $query = AiRentalBudgetLimit::active()
            ->whereNotNull('period_end')
            ->where('period_end', '<', now());

        // กรองตาม type
        if ($type = $this->option('type')) {
            $query->where('budget_type', $type);
        }

        // กรองตาม user
        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        return $query->with(['user', 'cloudConfig', 'deployment'])->get();
    }

    /**
     * แสดงรายการ budgets ที่หมดอายุ
     *
     * @param \Illuminate\Support\Collection $budgets
     * @return void
     */
    protected function displayExpiredBudgets($budgets): void
    {
        $tableData = [];

        foreach ($budgets as $budget) {
            $rollover = $budget->allow_rollover && $budget->current_spending < $budget->limit_amount
                ? $budget->limit_amount - $budget->current_spending
                : 0;

            if ($rollover > 0) {
                $this->stats['total_rollover'] += $rollover;
            }

            $tableData[] = [
                $budget->id,
                $budget->user->name ?? 'N/A',
                $budget->budget_name,
                $budget->budget_type,
                "\${$budget->current_spending}/\${$budget->limit_amount}",
                $budget->allow_rollover ? '✅' : '❌',
                $rollover > 0 ? "\${$rollover}" : '-',
                $budget->period_end->format('Y-m-d H:i'),
            ];
        }

        $this->table(
            ['ID', 'User', 'Budget Name', 'Type', 'Spending', 'Rollover?', 'Rollover Amount', 'Expired At'],
            $tableData
        );

        $this->newLine();
    }

    /**
     * รีเซ็ต budgets
     *
     * @param \Illuminate\Support\Collection $budgets
     * @return void
     */
    protected function resetBudgets($budgets): void
    {
        $bar = $this->output->createProgressBar($budgets->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        foreach ($budgets as $budget) {
            $bar->setMessage("Resetting: {$budget->budget_name}");

            try {
                $this->budgetService->resetBudget($budget);
                $this->stats['reset_success']++;

                if ($this->output->isVerbose()) {
                    $this->line("  ✅ รีเซ็ต: {$budget->budget_name}");
                }
            } catch (\Exception $e) {
                $this->stats['reset_failed']++;

                if ($this->output->isVerbose()) {
                    $this->error("  ❌ {$budget->budget_name}: {$e->getMessage()}");
                }

                Log::error("Failed to reset budget {$budget->id}", [
                    'budget_id' => $budget->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * แสดงสรุปผล
     *
     * @param \Carbon\Carbon $startTime
     * @return void
     */
    protected function displaySummary(\Carbon\Carbon $startTime): void
    {
        $duration = $startTime->diffInSeconds(now());

        $this->info('📈 สรุปผลการรีเซ็ต:');
        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['Budgets หมดอายุทั้งหมด', $this->stats['total_expired']],
                ['✅ รีเซ็ตสำเร็จ', $this->stats['reset_success']],
                ['❌ รีเซ็ตล้มเหลว', $this->stats['reset_failed']],
                ['💰 Rollover รวม', '$' . number_format($this->stats['total_rollover'], 2)],
                ['⏱️  ระยะเวลา', "{$duration} วินาที"],
            ]
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔍 Dry-run mode: ไม่มีการเปลี่ยนแปลงใดๆ');
        } elseif ($this->stats['reset_failed'] > 0) {
            $this->newLine();
            $this->warn("⚠️  มี {$this->stats['reset_failed']} budgets ที่รีเซ็ตไม่สำเร็จ");
        } elseif ($this->stats['reset_success'] > 0) {
            $this->newLine();
            $this->info('✅ รีเซ็ต budgets สำเร็จทั้งหมด!');
        }

        $this->newLine();
        $this->info('✨ เสร็จสิ้นการรีเซ็ต!');
    }
}
