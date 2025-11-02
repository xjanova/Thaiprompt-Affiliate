<?php

namespace App\Console\Commands;

use App\Services\ThreatIntelligenceService;
use Illuminate\Console\Command;

class UpdateThreatIntelligence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'threat:update {--source= : Specific source to update (firehol, abuseipdb, ipqualityscore)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update threat intelligence from external sources';

    /**
     * Execute the console command.
     */
    public function handle(ThreatIntelligenceService $service): int
    {
        $this->info('🔍 Updating threat intelligence...');

        $source = $this->option('source');

        try {
            if ($source) {
                $this->info("Updating from {$source}...");

                $results = match($source) {
                    'firehol' => ['firehol' => $service->updateFromFirehol()],
                    default => throw new \InvalidArgumentException("Unknown source: {$source}"),
                };
            } else {
                $this->info('Updating from all sources...');
                $results = $service->updateAllSources();
            }

            // Display results
            if (isset($results['firehol'])) {
                $this->newLine();
                $this->line('📊 Firehol Blocklists:');

                foreach ($results['firehol'] as $list => $result) {
                    if (isset($result['success']) && $result['success']) {
                        $this->info("  ✅ {$list}: {$result['updated']} IPs updated");
                    } else {
                        $this->error("  ❌ {$list}: {$result['error']}");
                    }
                }
            }

            $this->newLine();
            $this->info('✨ Threat intelligence update completed!');
            $this->info("Total updated: {$results['total_updated']}");
            $this->info("Total errors: {$results['total_errors']}");

            // Clean expired
            $this->info('🧹 Cleaning expired threats...');
            $cleaned = $service->cleanExpired();
            $this->info("Cleaned {$cleaned} expired threats");

            // Show statistics
            $stats = $service->getStatistics();
            $this->newLine();
            $this->line('📈 Current Statistics:');
            $this->info("Total active threats: {$stats['total_threats']}");
            $this->info("High confidence threats: {$stats['high_confidence']}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error updating threat intelligence: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
