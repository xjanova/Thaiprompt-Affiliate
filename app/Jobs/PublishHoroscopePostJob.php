<?php

namespace App\Jobs;

use App\Models\FortuneHoroscopePost;
use App\Services\FortuneHoroscopePublishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * โพสดวงรายวันลง Facebook/LINE
 */
class PublishHoroscopePostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $retryAfter = 60;

    public function __construct(
        public FortuneHoroscopePost $post
    ) {}

    /**
     * โพสเนื้อหา
     */
    public function handle(FortuneHoroscopePublishService $service): void
    {
        Log::info('PublishHoroscopePostJob: เริ่มโพส', [
            'post_id' => $this->post->id,
            'platform' => $this->post->platform,
        ]);

        $service->publish($this->post);
    }

    /**
     * Job ล้มเหลวถาวร
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PublishHoroscopePostJob: ล้มเหลวถาวร', [
            'post_id' => $this->post->id,
            'platform' => $this->post->platform,
            'error' => $exception->getMessage(),
        ]);

        $this->post->markFailed('ล้มเหลวถาวร: ' . $exception->getMessage());
    }
}
