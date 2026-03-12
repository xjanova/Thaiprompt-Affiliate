<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\LineFortuneService;
use Illuminate\Console\Command;

/**
 * ตรวจสอบโควต้า LINE push message และทดสอบส่ง push
 *
 * ใช้สำหรับ debug ปัญหา LINE push notification ไม่ทำงาน
 */
class LineQuotaCheck extends Command
{
    /**
     * @var string
     */
    protected $signature = 'line:quota
        {--test-push= : ส่ง push ทดสอบไปหา LINE user ID ที่ระบุ}
        {--message= : ข้อความทดสอบ (ใช้ร่วมกับ --test-push)}';

    /**
     * @var string
     */
    protected $description = 'ตรวจสอบโควต้า LINE push message และทดสอบส่ง push notification';

    /**
     * รัน command
     *
     * @return int
     */
    public function handle(): int
    {
        $settings = FortuneTellingSetting::getSettings();
        $lineService = new LineFortuneService($settings);

        $this->info('🔍 กำลังตรวจสอบโควต้า LINE Messaging API...');
        $this->newLine();

        // เช็คโควต้า
        $quota = $lineService->getMessageQuota();

        if ($quota['error']) {
            $this->error("❌ เช็คโควต้าไม่สำเร็จ: {$quota['error']}");
        } else {
            $this->table(
                ['รายการ', 'ค่า'],
                [
                    ['📊 โควต้าทั้งหมด (ต่อเดือน)', number_format($quota['quota'])],
                    ['📤 ใช้ไปแล้ว', number_format($quota['used'])],
                    ['📥 เหลืออีก', number_format($quota['remaining'])],
                    ['📈 ใช้ไป (%)', $quota['percentage'].'%'],
                ]
            );

            if ($quota['remaining'] <= 0) {
                $this->error('🚨 โควต้า LINE push หมดแล้ว! Push notification จะไม่ทำงาน!');
            } elseif ($quota['remaining'] < 50) {
                $this->warn("⚠️  โควต้าเหลือน้อย! เหลือ {$quota['remaining']} messages");
            } else {
                $this->info("✅ โควต้ายังเหลือเพียงพอ ({$quota['remaining']} messages)");
            }
        }

        // ทดสอบ push ถ้ามี --test-push
        $testUserId = $this->option('test-push');
        if ($testUserId) {
            $this->newLine();
            $this->info("📤 ทดสอบ push message ไปหา: {$testUserId}");

            $message = $this->option('message') ?? '🔔 ทดสอบ push notification จาก line:quota command';
            $result = $lineService->testPushMessage($testUserId, $message);

            if ($result['success']) {
                $this->info('✅ Push สำเร็จ! ตรวจสอบที่ LINE app ของ user');
            } else {
                $this->error("❌ Push ล้มเหลว!");
                $this->error("   Status: {$result['status']}");
                $this->error("   Error: {$result['error']}");

                // แนะนำการแก้ไขตาม error
                if ($result['status'] === 429) {
                    $this->warn('   → โดน rate limit! รอสักครู่แล้วลองใหม่');
                } elseif ($result['status'] === 400) {
                    $this->warn('   → User ID อาจไม่ถูกต้อง หรือ user ยังไม่ได้ add friend');
                } elseif ($result['status'] === 401) {
                    $this->warn('   → Channel Access Token ไม่ถูกต้อง! ตรวจสอบใน settings');
                }
            }
        }

        // แสดง FortuneReading ล่าสุดที่ยังไม่ได้ส่ง notification
        $this->newLine();
        $this->info('📋 FortuneReading ล่าสุดที่ชำระแล้ว (ดู platform):');

        $readings = \App\Models\FortuneReading::where('is_paid', true)
            ->where('created_at', '>=', now()->subDays(3))
            ->latest()
            ->limit(10)
            ->get(['id', 'platform', 'platform_user_id', 'facebook_user_id', 'conversation_status', 'bill_reference', 'conversation_state']);

        $rows = [];
        foreach ($readings as $r) {
            $notifSent = $r->getConversationState('reading_notification_sent', '—');
            $userId = $r->platform_user_id ?? $r->facebook_user_id;
            $isLineId = preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '') ? '✅' : '❌';

            $rows[] = [
                $r->id,
                $r->bill_reference ?? '-',
                $r->platform ?? '(null)',
                substr($userId ?? '-', 0, 15).'...',
                $isLineId,
                $r->conversation_status,
                is_bool($notifSent) ? ($notifSent ? '✅' : '❌') : $notifSent,
            ];
        }

        $this->table(
            ['ID', 'Bill', 'Platform', 'User ID', 'LINE?', 'Status', 'Notified'],
            $rows
        );

        return self::SUCCESS;
    }
}
