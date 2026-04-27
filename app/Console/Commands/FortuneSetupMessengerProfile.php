<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use Illuminate\Console\Command;

/**
 * ติดตั้ง Facebook Messenger Profile (Get Started + Greeting + Ice Breakers + Persistent Menu)
 *
 * ใช้หลังแก้ buildPersistentMenuActions() / buildIceBreakerActions() ใน FacebookWebhookService
 * เพื่อ push config ใหม่ไปยัง Facebook Page Profile API
 *
 * Usage:
 *   php artisan fortune:setup-messenger          # ติดตั้ง/อัพเดต profile
 *   php artisan fortune:setup-messenger --show   # ดู profile ปัจจุบัน
 *   php artisan fortune:setup-messenger --reset  # ลบ profile (debug)
 */
class FortuneSetupMessengerProfile extends Command
{
    protected $signature = 'fortune:setup-messenger
        {--show : แสดง profile ปัจจุบัน}
        {--reset : ลบ profile ทั้งหมด}';

    protected $description = 'ติดตั้ง/อัพเดต Facebook Messenger Profile (Get Started + Ice Breakers + Persistent Menu)';

    public function handle(): int
    {
        $settings = FortuneTellingSetting::getSettings();

        // ใช้ field ตามที่ FacebookWebhookService ใช้: facebook_page_token
        $token = $settings->facebook_page_token ?? $settings->facebook_page_access_token ?? null;
        if (empty($token)) {
            $this->error('❌ ไม่พบ Page Access Token ใน fortune_telling_settings');
            $this->line('กรุณาตั้งค่า facebook_page_token ในหน้า admin ก่อน');

            return self::FAILURE;
        }

        $service = new FacebookWebhookService($settings);

        if ($this->option('show')) {
            return $this->showProfile($service);
        }

        if ($this->option('reset')) {
            return $this->resetProfile($service);
        }

        return $this->setupProfile($service);
    }

    protected function setupProfile(FacebookWebhookService $service): int
    {
        $this->info('🚀 กำลังติดตั้ง Facebook Messenger Profile...');
        $result = $service->setupMessengerProfile();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            $this->line('');
            $this->line('สิ่งที่ติดตั้ง:');
            $this->line('  • Get Started button (payload: GET_STARTED)');
            $this->line('  • Greeting Text (ภาษาไทย)');
            $this->line('  • Ice Breakers — 4 คำถาม');
            $this->line('  • Persistent Menu — 3 รายการ (สมัครสมาชิก / ดูดวง / รู้จักเรา)');

            return self::SUCCESS;
        }

        $this->error('❌ ' . $result['message']);

        return self::FAILURE;
    }

    protected function showProfile(FacebookWebhookService $service): int
    {
        $this->info('📋 ดึง Messenger Profile ปัจจุบัน...');
        $result = $service->getMessengerProfile();

        if (! ($result['success'] ?? false)) {
            $this->error('❌ ' . ($result['message'] ?? 'ไม่สามารถดึงข้อมูลได้'));

            return self::FAILURE;
        }

        $this->line(json_encode($result['data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    protected function resetProfile(FacebookWebhookService $service): int
    {
        if (! $this->confirm('⚠️ ยืนยันลบ Messenger Profile ทั้งหมด?')) {
            return self::SUCCESS;
        }

        $this->warn('🗑 กำลังลบ Messenger Profile...');
        $result = $service->deleteMessengerProfile();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);

            return self::SUCCESS;
        }

        $this->error('❌ ' . $result['message']);

        return self::FAILURE;
    }
}
