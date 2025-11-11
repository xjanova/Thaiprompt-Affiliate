<?php

namespace App\Console\Commands;

use App\Services\ConversationTimeoutService;
use Illuminate\Console\Command;

/**
 * Cleanup expired LINE signup conversations
 *
 * This command checks for expired conversations and sends
 * appropriate notifications to users.
 *
 * Usage: php artisan line:cleanup-conversations
 * Scheduled: Runs every 5 minutes via Task Scheduler
 */
class CleanupConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:cleanup-conversations
                          {--dry-run : Show what would be cleaned without actually cleaning}
                          {--stats : Show conversation statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired LINE signup conversations and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(ConversationTimeoutService $timeoutService): int
    {
        if ($this->option('stats')) {
            return $this->showStatistics($timeoutService);
        }

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🧹 Starting conversation cleanup...');
        $this->newLine();

        if ($isDryRun) {
            // Just show counts
            $activeCount = \App\Models\MlmProspect::whereIn('status', ['in_progress', 'pending'])
                ->whereNotNull('conversation_started_at')
                ->where('conversation_expired', false)
                ->count();

            $this->info("ℹ️  Found {$activeCount} active conversations to check");
            return 0;
        }

        // Actual cleanup
        $stats = $timeoutService->cleanupExpiredConversations();

        $this->newLine();
        $this->info('✅ Cleanup completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Conversations checked', $stats['checked']],
                ['Conversations expired', $stats['expired']],
                ['Warnings sent', $stats['warnings_sent']],
            ]
        );

        if ($stats['expired'] > 0) {
            $this->warn("⏱️  {$stats['expired']} conversations expired and notified");
        }

        if ($stats['warnings_sent'] > 0) {
            $this->info("⏰ {$stats['warnings_sent']} timeout warnings sent");
        }

        if ($stats['checked'] === 0) {
            $this->info('✨ No active conversations to check');
        }

        return 0;
    }

    /**
     * Show conversation statistics
     */
    private function showStatistics(ConversationTimeoutService $timeoutService): int
    {
        $this->info('📊 LINE Conversation Statistics');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        $stats = $timeoutService->getStatistics();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Active conversations', $stats['active']],
                ['Expired conversations', $stats['expired']],
                ['Avg completion time', $this->formatDuration($stats['avg_duration'] ?? 0)],
                ['Avg progress', round($stats['avg_progress'] ?? 0, 1) . '%'],
            ]
        );

        $this->newLine();

        // Show recent expired conversations
        $this->info('🕒 Recent Expired Conversations:');
        $recentExpired = \App\Models\MlmProspect::where('conversation_expired', true)
            ->orderBy('conversation_updated_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentExpired->isEmpty()) {
            $this->line('  No expired conversations found');
        } else {
            $tableData = [];
            foreach ($recentExpired as $prospect) {
                $tableData[] = [
                    'ID' => $prospect->id,
                    'LINE User' => substr($prospect->line_user_id ?? 'N/A', 0, 12) . '...',
                    'Progress' => $prospect->conversation_progress_percent . '%',
                    'Messages' => $prospect->conversation_message_count,
                    'Expired At' => $prospect->conversation_updated_at?->diffForHumans(),
                ];
            }

            $this->table(
                ['ID', 'LINE User', 'Progress', 'Messages', 'Expired At'],
                $tableData
            );
        }

        return 0;
    }

    /**
     * Format duration in human-readable format
     */
    private function formatDuration(?float $seconds): string
    {
        if (!$seconds) {
            return 'N/A';
        }

        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
