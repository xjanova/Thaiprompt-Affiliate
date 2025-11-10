<?php

namespace App\Console\Commands;

use App\Services\BotAutomation\BotAutomationService;
use Illuminate\Console\Command;

class ProcessBotAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:process-automations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all due bot automations';

    /**
     * Execute the console command.
     */
    public function handle(BotAutomationService $service): int
    {
        $this->info('Processing bot automations...');

        $results = $service->processDueAutomations();

        $this->info("Processed " . count($results) . " automations");

        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $this->error("Automation {$result['automation_id']}: {$result['error']}");
            } else {
                $this->info("Automation {$result['automation_id']}: {$result['status']}");
            }
        }

        return Command::SUCCESS;
    }
}
