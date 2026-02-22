<?php

namespace App\Jobs;

use App\Models\FortuneHoroscopeCampaign;
use App\Services\FortuneHoroscopeService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * สร้างเนื้อหาดวงรายวัน (AI text + image)
 *
 * สร้าง 7 คำทำนาย (7 วันเกิด) + 7 รูปภาพ
 * ใช้เวลาประมาณ 2-5 นาที
 */
class GenerateHoroscopeContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $retryAfter = 60;

    public function __construct(
        public FortuneHoroscopeCampaign $campaign,
        public Carbon $targetDate
    ) {}

    /**
     * สร้างเนื้อหาดวงรายวัน
     */
    public function handle(FortuneHoroscopeService $service): void
    {
        Log::info('GenerateHoroscopeContentJob: เริ่มสร้างเนื้อหา', [
            'campaign_id' => $this->campaign->id,
            'target_date' => $this->targetDate->toDateString(),
        ]);

        $result = $service->generateDailyContent($this->campaign, $this->targetDate);

        Log::info('GenerateHoroscopeContentJob: เสร็จ', [
            'campaign_id' => $this->campaign->id,
            'success' => $result['success'],
            'failed' => $result['failed'],
        ]);
    }

    /**
     * Job ล้มเหลวถาวร
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateHoroscopeContentJob: ล้มเหลวถาวร', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);

        $this->campaign->markError('สร้างเนื้อหาล้มเหลว: ' . $exception->getMessage());
    }
}
