<?php

namespace App\Jobs\BotAutomation;

use App\Models\BotAutomation\BotScheduledPost;
use App\Services\BotAutomation\BotPlatformService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120; // 2 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BotScheduledPost $post
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BotPlatformService $service): void
    {
        if ($this->post->status !== 'pending') {
            Log::info("Skipping non-pending post", ['post_id' => $this->post->id]);
            return;
        }

        try {
            $this->post->update(['status' => 'processing']);

            $result = $service->publishPost($this->post);

            if (!$result['success'] && $this->post->retry_count < 3) {
                $this->post->scheduleRetry(30);
            }
        } catch (\Exception $e) {
            Log::error("Post publication job failed", [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
            ]);

            $this->post->markAsFailed($e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->post->markAsFailed($exception->getMessage());

        Log::error("Post publication job failed permanently", [
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
