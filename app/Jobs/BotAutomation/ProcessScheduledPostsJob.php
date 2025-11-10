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

class ProcessScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600; // 10 minutes

    /**
     * Execute the job.
     */
    public function handle(BotPlatformService $service): void
    {
        $posts = BotScheduledPost::dueForPublishing()->get();

        Log::info("Processing scheduled posts", ['count' => $posts->count()]);

        foreach ($posts as $post) {
            try {
                PublishScheduledPostJob::dispatch($post);
            } catch (\Exception $e) {
                Log::error("Failed to dispatch post job", [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
