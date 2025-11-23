<?php

namespace App\Console\Commands;

use App\Models\LineFailedMessage;
use App\Models\LineErrorLog;
use App\Services\LineAutoRetryService;
use Illuminate\Console\Command;

/**
 * LINE Auto-Retry Status Command
 *
 * คำสั่งสำหรับตรวจสอบสถานะระบบ Auto-Retry
 * แสดงข้อมูลสถิติและสุขภาพของระบบ
 *
 * Usage:
 * - php artisan line:retry-status
 * - php artisan line:retry-status --days=30     (สถิติ 30 วัน)
 * - php artisan line:retry-status --detailed    (แสดงรายละเอียดเพิ่มเติม)
 *
 * @package App\Console\Commands
 */
class LineRetryStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:retry-status
                            {--days=7 : จำนวนวันย้อนหลังสำหรับสถิติ}
                            {--detailed : แสดงรายละเอียดเพิ่มเติม}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ตรวจสอบสถานะระบบ LINE Auto-Retry';

    /**
     * LineAutoRetryService instance
     *
     * @var LineAutoRetryService
     */
    protected LineAutoRetryService $retryService;

    /**
     * Constructor
     *
     * @param LineAutoRetryService $retryService
     */
    public function __construct(LineAutoRetryService $retryService)
    {
        parent::__construct();
        $this->retryService = $retryService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info('🔍 LINE Auto-Retry System Status');
        $this->newLine();

        try {
            // 1. แสดงสถานะโดยรวม
            $this->displayOverallStatus();

            // 2. แสดงสถิติการ retry
            $this->displayRetryStatistics($days);

            // 3. แสดงข้อมูลรายละเอียด (ถ้าระบุ --detailed)
            if ($this->option('detailed')) {
                $this->displayDetailedInfo($days);
            }

            // 4. แสดง recommendations
            $this->displayRecommendations($days);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ เกิดข้อผิดพลาด: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * แสดงสถานะโดยรวม
     *
     * @return void
     */
    protected function displayOverallStatus(): void
    {
        $this->line('📊 สถานะโดยรวม:');

        // นับจำนวนข้อความแต่ละสถานะ
        $pending = LineFailedMessage::where('status', LineFailedMessage::STATUS_PENDING)->count();
        $retrying = LineFailedMessage::where('status', LineFailedMessage::STATUS_RETRYING)->count();
        $succeeded = LineFailedMessage::where('status', LineFailedMessage::STATUS_SUCCEEDED)->count();
        $failed = LineFailedMessage::where('status', LineFailedMessage::STATUS_FAILED)->count();
        $abandoned = LineFailedMessage::where('status', LineFailedMessage::STATUS_ABANDONED)->count();

        $total = $pending + $retrying + $succeeded + $failed + $abandoned;

        // นับ error logs ที่ยังไม่ resolve
        $unresolvedErrors = LineErrorLog::where('is_recovered', false)->count();

        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['⏳ Pending', number_format($pending), $total > 0 ? round(($pending / $total) * 100, 1) . '%' : '0%'],
                ['🔄 Retrying', number_format($retrying), $total > 0 ? round(($retrying / $total) * 100, 1) . '%' : '0%'],
                ['✅ Succeeded', number_format($succeeded), $total > 0 ? round(($succeeded / $total) * 100, 1) . '%' : '0%'],
                ['❌ Failed', number_format($failed), $total > 0 ? round(($failed / $total) * 100, 1) . '%' : '0%'],
                ['⚠️  Abandoned', number_format($abandoned), $total > 0 ? round(($abandoned / $total) * 100, 1) . '%' : '0%'],
                ['', '', ''],
                ['📝 Total Messages', number_format($total), '100%'],
                ['🔴 Unresolved Errors', number_format($unresolvedErrors), '-'],
            ]
        );

        // แสดง health indicator
        $this->newLine();
        $healthStatus = $this->calculateHealthStatus($pending, $abandoned, $unresolvedErrors);
        $this->line($healthStatus);
    }

    /**
     * แสดงสถิติการ retry
     *
     * @param int $days
     * @return void
     */
    protected function displayRetryStatistics(int $days): void
    {
        $this->newLine();
        $this->line("📈 สถิติย้อนหลัง {$days} วัน:");

        $stats = $this->retryService->getRetryStatistics($days);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Failed Messages', number_format($stats['total'])],
                ['Successfully Recovered', number_format($stats['succeeded']) . ' (' . $stats['success_rate'] . '%)'],
                ['Abandoned (Unrecoverable)', number_format($stats['abandoned']) . ' (' . $stats['abandonment_rate'] . '%)'],
                ['Still Pending', number_format($stats['pending'])],
                ['Average Retry Count', $stats['avg_retry_count']],
            ]
        );

        // แสดง breakdown ตาม error type
        if (!empty($stats['by_error_type'])) {
            $this->newLine();
            $this->line('🔍 Error Types Breakdown:');

            $errorRows = [];
            foreach ($stats['by_error_type'] as $type => $count) {
                $percentage = $stats['total'] > 0 ? round(($count / $stats['total']) * 100, 1) : 0;
                $errorRows[] = [$this->formatErrorType($type), number_format($count), $percentage . '%'];
            }

            $this->table(['Error Type', 'Count', 'Percentage'], $errorRows);
        }
    }

    /**
     * แสดงข้อมูลรายละเอียด
     *
     * @param int $days
     * @return void
     */
    protected function displayDetailedInfo(int $days): void
    {
        $this->newLine();
        $this->line('📋 ข้อมูลรายละเอียด:');

        // ข้อความที่รอ retry ใน 1 ชั่วโมงข้างหน้า
        $upcomingRetries = LineFailedMessage::where('status', LineFailedMessage::STATUS_PENDING)
            ->where('next_retry_at', '<=', now()->addHour())
            ->count();

        // ข้อความที่เกิน retry limit
        $exceededLimit = LineFailedMessage::whereColumn('retry_count', '>=', 'max_retries')
            ->where('status', '!=', LineFailedMessage::STATUS_ABANDONED)
            ->count();

        // Error severity breakdown
        $criticalErrors = LineErrorLog::where('severity', 'critical')
            ->where('occurred_at', '>=', now()->subDays($days))
            ->count();
        $highErrors = LineErrorLog::where('severity', 'high')
            ->where('occurred_at', '>=', now()->subDays($days))
            ->count();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Upcoming Retries (Next 1h)', number_format($upcomingRetries)],
                ['Exceeded Retry Limit', number_format($exceededLimit)],
                ['Critical Errors', number_format($criticalErrors)],
                ['High Priority Errors', number_format($highErrors)],
            ]
        );

        // แสดงข้อความล่าสุดที่ล้มเหลว
        $this->displayRecentFailures();
    }

    /**
     * แสดงข้อความล่าสุดที่ล้มเหลว
     *
     * @return void
     */
    protected function displayRecentFailures(): void
    {
        $this->newLine();
        $this->line('🕐 ข้อความล่าสุดที่ล้มเหลว (5 รายการ):');

        $recentFailures = LineFailedMessage::whereIn('status', [
            LineFailedMessage::STATUS_FAILED,
            LineFailedMessage::STATUS_PENDING
        ])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        if ($recentFailures->isEmpty()) {
            $this->info('   ไม่มีข้อความที่ล้มเหลวล่าสุด');
            return;
        }

        $rows = [];
        foreach ($recentFailures as $failure) {
            $rows[] = [
                $failure->id,
                $failure->message_type,
                $failure->error_type ?? 'unknown',
                $failure->retry_count . '/' . $failure->max_retries,
                $failure->next_retry_at ? $failure->next_retry_at->diffForHumans() : '-',
            ];
        }

        $this->table(
            ['ID', 'Type', 'Error', 'Retries', 'Next Retry'],
            $rows
        );
    }

    /**
     * แสดง recommendations
     *
     * @param int $days
     * @return void
     */
    protected function displayRecommendations(int $days): void
    {
        $this->newLine();
        $this->line('💡 Recommendations:');

        $stats = $this->retryService->getRetryStatistics($days);
        $recommendations = [];

        // ตรวจสอบ success rate
        if ($stats['success_rate'] < 70) {
            $recommendations[] = '⚠️  Success rate ต่ำ (' . $stats['success_rate'] . '%) - ตรวจสอบ LINE API credentials หรือ network connectivity';
        }

        // ตรวจสอบ abandonment rate
        if ($stats['abandonment_rate'] > 20) {
            $recommendations[] = '⚠️  Abandonment rate สูง (' . $stats['abandonment_rate'] . '%) - พิจารณาเพิ่ม max_retries หรือตรวจสอบ error patterns';
        }

        // ตรวจสอบจำนวน pending
        if ($stats['pending'] > 100) {
            $recommendations[] = '⚠️  มีข้อความรอ retry มาก (' . number_format($stats['pending']) . ') - เพิ่มความถี่ cron job หรือเพิ่ม limit';
        }

        // ตรวจสอบ error types
        if (isset($stats['by_error_type']['rate_limit']) && $stats['by_error_type']['rate_limit'] > 10) {
            $recommendations[] = '⚠️  เกิน Rate Limit บ่อย - ลดความถี่การส่งข้อความหรือเพิ่ม delay';
        }

        if (empty($recommendations)) {
            $recommendations[] = '✅ ระบบทำงานปกติ ไม่มีข้อเสนอแนะเพิ่มเติม';
        }

        foreach ($recommendations as $rec) {
            $this->line('   ' . $rec);
        }

        $this->newLine();
        $this->info('💬 Tip: ใช้ php artisan line:process-retries เพื่อประมวลผลข้อความที่รอ retry');
    }

    /**
     * คำนวณสถานะสุขภาพของระบบ
     *
     * @param int $pending
     * @param int $abandoned
     * @param int $unresolvedErrors
     * @return string
     */
    protected function calculateHealthStatus(int $pending, int $abandoned, int $unresolvedErrors): string
    {
        if ($unresolvedErrors > 50 || $abandoned > 100 || $pending > 500) {
            return '<fg=red>🔴 Health Status: CRITICAL - ต้องดำเนินการทันที!</>';
        }

        if ($unresolvedErrors > 20 || $abandoned > 50 || $pending > 200) {
            return '<fg=yellow>🟡 Health Status: WARNING - ควรตรวจสอบ</>';
        }

        return '<fg=green>🟢 Health Status: HEALTHY</>';
    }

    /**
     * Format error type เป็นรูปแบบที่อ่านง่าย
     *
     * @param string $errorType
     * @return string
     */
    protected function formatErrorType(string $errorType): string
    {
        $map = [
            'network' => '🌐 Network',
            'rate_limit' => '⏱️  Rate Limit',
            'timeout' => '⏰ Timeout',
            'invalid_token' => '🔑 Invalid Token',
            'user_not_found' => '👤 User Not Found',
            'blocked' => '🚫 Blocked',
            'unknown' => '❓ Unknown',
        ];

        return $map[$errorType] ?? $errorType;
    }
}
