<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeSourcesJob;
use App\Jobs\AnalyzeTrendDataJob;
use App\Jobs\DetectViralTrendsJob;
use Illuminate\Console\Command;

class RunTrendDetection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trends:detect {--scrape} {--analyze} {--detect} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run trend detection pipeline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scrape = $this->option('scrape');
        $analyze = $this->option('analyze');
        $detect = $this->option('detect');
        $all = $this->option('all');

        if (!$scrape && !$analyze && !$detect && !$all) {
            $this->error('Please specify at least one option: --scrape, --analyze, --detect, or --all');
            return 1;
        }

        if ($all || $scrape) {
            $this->info('Dispatching scraping job...');
            ScrapeSourcesJob::dispatch();
            $this->info('Scraping job dispatched');
        }

        if ($all || $analyze) {
            $this->info('Dispatching analysis job...');
            AnalyzeTrendDataJob::dispatch();
            $this->info('Analysis job dispatched');
        }

        if ($all || $detect) {
            $this->info('Dispatching detection job...');
            DetectViralTrendsJob::dispatch();
            $this->info('Detection job dispatched');
        }

        $this->info('All jobs have been dispatched to the queue');

        return 0;
    }
}
