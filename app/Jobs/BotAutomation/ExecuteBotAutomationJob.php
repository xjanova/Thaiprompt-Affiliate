<?php

namespace App\Jobs\BotAutomation;

use App\Models\BotAutomation\BotAutomation;
use App\Services\BotAutomation\BotAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteBotAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BotAutomation $automation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BotAutomationService $service): void
    {
        if (!$this->automation->is_active) {
            Log::info("Skipping inactive automation", ['automation_id' => $this->automation->id]);
            return;
        }

        try {
            $service->executeAutomation($this->automation, 'scheduled');

            // Schedule next execution if applicable
            if ($this->automation->schedule_type !== 'once') {
                $service->scheduleExecution($this->automation);
            }
        } catch (\Exception $e) {
            Log::error("Bot automation job failed", [
                'automation_id' => $this->automation->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Bot automation job failed permanently", [
            'automation_id' => $this->automation->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
