<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use App\Models\SystemAnalytic;
use Illuminate\Console\Command;

class CollectSystemAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:collect
                            {--cleanup : Clean old metrics (older than 30 days)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect system analytics and performance metrics';

    protected $analyticsService;

    /**
     * Create a new command instance.
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Collecting system analytics...');

        try {
            $metric = $this->analyticsService->collectMetrics();

            $this->info('✅ Metrics collected successfully!');
            $this->line('');
            $this->line('📊 Snapshot Summary:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Active Users', $metric->active_users ?? 0],
                    ['Request Rate', number_format($metric->request_rate ?? 0, 2) . ' req/s'],
                    ['CPU Usage', number_format($metric->cpu_usage ?? 0, 2) . '%'],
                    ['Memory Usage', number_format($metric->memory_usage ?? 0, 2) . '%'],
                    ['Cache Hit Rate', number_format($metric->cache_hit_rate ?? 0, 2) . '%'],
                    ['DB Connections', ($metric->db_connections_active ?? 0) . '/' . ($metric->db_connections_total ?? 0)],
                    ['Error Rate', number_format($metric->error_rate ?? 0, 2) . '%'],
                ]
            );

            // System health status
            $health = SystemAnalytic::getHealthStatus();
            $statusEmoji = match($health['status']) {
                'healthy' => '🟢',
                'warning' => '🟡',
                'critical' => '🔴',
                default => '⚪'
            };

            $this->line('');
            $this->info($statusEmoji . ' System Health: ' . $health['message']);

            if (!empty($health['issues'])) {
                $this->warn('⚠️  Issues detected:');
                foreach ($health['issues'] as $issue) {
                    $this->line('   - ' . $issue);
                }
            }

            // Cleanup old metrics if requested
            if ($this->option('cleanup')) {
                $this->line('');
                $this->info('🧹 Cleaning old metrics...');
                $deleted = SystemAnalytic::cleanOldMetrics(30);
                $this->info("✅ Deleted {$deleted} old metric records (older than 30 days)");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to collect metrics: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
