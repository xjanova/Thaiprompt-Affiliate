<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\FortuneMarketingController;
use App\Models\FortuneMarketingCampaign;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ส่งแคมเปญการตลาดดูดวงอัตโนมัติ
 *
 * คำสั่งนี้ถูก schedule ให้ทำงานทุก 5 นาที
 * จะดึงแคมเปญที่ถึงเวลาส่งแล้วมาทำงาน
 */
class FortuneMarketingSend extends Command
{
    protected $signature = 'fortune:marketing-send
                            {--dry-run : แสดงผลอย่างเดียว ไม่ส่งจริง}
                            {--campaign= : ส่งเฉพาะแคมเปญ ID ที่ระบุ}
                            {--regenerate : ให้ AI สร้างข้อความใหม่ก่อนส่ง}';

    protected $description = 'ส่งแคมเปญการตลาดดูดวงอัตโนมัติ ตามเวลาที่ตั้งไว้';

    public function handle(): int
    {
        $this->info('🚀 เริ่มตรวจสอบแคมเปญการตลาดดูดวง...');

        // ถ้าระบุ campaign ID เจาะจง
        if ($campaignId = $this->option('campaign')) {
            $campaign = FortuneMarketingCampaign::find($campaignId);
            if (!$campaign) {
                $this->error("ไม่พบแคมเปญ ID: {$campaignId}");
                return 1;
            }
            return $this->processCampaign($campaign);
        }

        // ดึงแคมเปญที่ถึงเวลาส่ง
        $campaigns = FortuneMarketingCampaign::readyToSend()->get();

        if ($campaigns->isEmpty()) {
            $this->info('ไม่มีแคมเปญที่ถึงเวลาส่ง');
            return 0;
        }

        $this->info("พบ {$campaigns->count()} แคมเปญที่ถึงเวลาส่ง");

        foreach ($campaigns as $campaign) {
            $this->processCampaign($campaign);
        }

        $this->info('✅ ตรวจสอบแคมเปญเสร็จสิ้น');
        return 0;
    }

    /**
     * ประมวลผลแคมเปญเดียว
     */
    private function processCampaign(FortuneMarketingCampaign $campaign): int
    {
        $this->info("📢 แคมเปญ #{$campaign->id}: {$campaign->name}");
        $this->info("   หัวข้อ: {$campaign->topic}");
        $this->info("   เป้าหมาย: {$campaign->target_label} ({$campaign->target_platform})");

        // ถ้าต้อง regenerate ข้อความ (สำหรับส่งซ้ำรายวัน/สัปดาห์ ให้ AI สร้างใหม่ทุกครั้ง)
        if ($this->option('regenerate') || ($campaign->use_ai_generate && in_array($campaign->schedule_type, ['daily', 'weekly']))) {
            $this->info('   🤖 AI กำลังสร้างข้อความใหม่...');
            $this->regenerateMessage($campaign);
        }

        $message = $campaign->getMessageToSend();
        if (!$message) {
            $this->error("   ❌ ไม่มีข้อความ ข้ามแคมเปญนี้");
            $campaign->markError('ไม่มีข้อความ');
            return 1;
        }

        // แสดงตัวอย่างข้อความ
        $this->line("   ข้อความ: " . mb_substr($message, 0, 100) . '...');

        // Dry run
        if ($this->option('dry-run')) {
            $this->warn('   [DRY RUN] ไม่ส่งจริง');
            return 0;
        }

        // ส่งจริง
        try {
            $controller = new FortuneMarketingController();
            $result = $controller->executeCampaign($campaign);

            $this->info("   ✅ ส่งสำเร็จ: {$result['sent']} คน | ล้มเหลว: {$result['failed']} คน");
            return 0;

        } catch (\Exception $e) {
            $this->error("   ❌ เกิดข้อผิดพลาด: {$e->getMessage()}");
            $campaign->markError($e->getMessage());
            Log::error('Fortune marketing send failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * ให้ AI สร้างข้อความใหม่
     */
    private function regenerateMessage(FortuneMarketingCampaign $campaign): void
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $aiService = new FortuneAIService($settings);

            $prompt = $campaign->ai_prompt ?: $this->buildAutoPrompt($campaign);
            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                readingType: 'basic'
            );

            $campaign->update(['ai_generated_message' => $result['response']]);
            $this->info('   ✅ AI สร้างข้อความใหม่สำเร็จ');

        } catch (\Exception $e) {
            $this->warn("   ⚠️ AI สร้างข้อความใหม่ล้มเหลว ใช้ข้อความเดิม: {$e->getMessage()}");
        }
    }

    /**
     * สร้าง prompt อัตโนมัติตามวัน
     */
    private function buildAutoPrompt(FortuneMarketingCampaign $campaign): string
    {
        $dayOfWeek = now()->locale('th')->dayName;
        $topic = $campaign->topic ?? 'ดูดวงประจำวัน';

        return <<<PROMPT
คุณคือ "แม่หมอจันทรา" หมอดูสาวสวยอายุ 35 ปี พลังหยั่งรู้ทำนายแม่นยำ

เขียนข้อความการตลาดเชิญชวนมาดูดวง สำหรับวัน{$dayOfWeek}นี้
หัวข้อ: {$topic}

กฎ:
- ใช้ภาษาไทย เป็นกันเอง อบอุ่น
- 100-200 คำ
- อ้างอิงดวงดาว ราศี ตามวัน{$dayOfWeek}
- แทรกเคล็ดลับมงคลประจำวัน (สี เลข ทิศ)
- ปิดด้วย call-to-action เชิญมาดูดวง
- ใส่ emoji ให้มีชีวิตชีวา
- ให้ความรู้สึกเหมือนเพื่อนส่งมาบอก
- ห้ามขายสินค้า ห้ามแนะนำลงทุน
- ข้อความต้องไม่ซ้ำกับครั้งก่อน ให้หลากหลาย
PROMPT;
    }
}
