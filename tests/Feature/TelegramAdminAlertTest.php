<?php

namespace Tests\Feature;

use App\Services\TelegramAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🔔 แจ้งเตือนแอดมินผ่าน Telegram (2026-09-19)
 *
 * เจ้าของสั่งหลังเคสรูปดวงรายวันหายเงียบ 2 วัน: "แจ้งเตือนทุกเรื่องที่สำคัญ ๆ"
 *
 * ล็อกไว้:
 *   1. ไม่ได้ตั้งค่า → เงียบสนิท ไม่ยิง API ไม่ throw (ของเดิมต้องไม่พังเพราะตัวเตือน)
 *   2. ตั้งครบ → ยิงเข้า Bot API พร้อม chat_id ที่ถูกต้อง
 *   3. ตัวกันรัว — เรื่องเดียวกันในหน้าต่างเวลาเดียวกัน ส่งออกครั้งเดียว
 *   4. Telegram ล่ม/ตอบ error → คืน false (ผู้เรียกจะได้ fallback ไป LINE ได้)
 */
class TelegramAdminAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function configure(): void
    {
        config([
            'services.telegram_alert.token' => '123456:TEST-TOKEN',
            'services.telegram_alert.chat_id' => '987654321',
        ]);
    }

    #[Test]
    public function it_stays_silent_when_not_configured(): void
    {
        config(['services.telegram_alert.token' => '', 'services.telegram_alert.chat_id' => '']);
        Http::fake();

        $svc = new TelegramAlertService;

        $this->assertFalse($svc->isConfigured());
        $this->assertFalse($svc->send('ทดสอบ'));
        Http::assertNothingSent();
    }

    #[Test]
    public function a_chat_id_alone_is_not_enough_and_a_token_alone_is_not_either(): void
    {
        Http::fake();

        // บอทส่งหาคนที่ไม่เคยทักมันไม่ได้ ⇒ ขาด chat id = ส่งไม่ได้
        config(['services.telegram_alert.token' => '123:ABC', 'services.telegram_alert.chat_id' => '']);
        $this->assertFalse((new TelegramAlertService)->isConfigured());

        config(['services.telegram_alert.token' => '', 'services.telegram_alert.chat_id' => '999']);
        $this->assertFalse((new TelegramAlertService)->isConfigured());

        Http::assertNothingSent();
    }

    #[Test]
    public function it_posts_to_the_bot_api_with_the_configured_chat(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $this->assertTrue((new TelegramAlertService)->send('🖼️ ดวงรายวัน: สร้างรูปไม่ได้ 8 ใบ'));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/bot123456:TEST-TOKEN/sendMessage')
                && ($body['chat_id'] ?? null) === '987654321'
                && str_contains((string) ($body['text'] ?? ''), 'สร้างรูปไม่ได้ 8 ใบ');
        });
    }

    #[Test]
    public function the_same_alert_is_only_sent_once_inside_its_throttle_window(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $svc = new TelegramAlertService;
        $key = 'horoscope_image_fail:2026-09-19:8';

        $this->assertTrue($svc->send('รูปล้ม 8 ใบ', $key, 720));
        $this->assertFalse($svc->send('รูปล้ม 8 ใบ', $key, 720), 'เรื่องเดิมห้ามยิงซ้ำ');
        $this->assertFalse($svc->send('ข้อความต่างแต่คีย์เดิม', $key, 720), 'ตัวกันรัวยึดตามคีย์');

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_different_key_still_gets_through(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $svc = new TelegramAlertService;

        // คีย์ของรูปล้มผูกกับวันที่ — พรุ่งนี้ยังพังอยู่ต้องเตือนใหม่ ไม่ใช่เงียบยาว
        $this->assertTrue($svc->send('รูปล้ม', 'horoscope_image_fail:2026-09-19:8', 720));
        $this->assertTrue($svc->send('รูปล้ม', 'horoscope_image_fail:2026-09-20:8', 720));

        Http::assertSentCount(2);
    }

    #[Test]
    public function a_failing_telegram_returns_false_so_the_caller_can_fall_back(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'chat not found'], 400)]);

        $this->assertFalse((new TelegramAlertService)->send('ทดสอบ'));
    }

    #[Test]
    public function a_network_exception_never_escapes(): void
    {
        $this->configure();
        Http::fake(fn () => throw new \RuntimeException('network down'));

        // ตัวเตือนพังต้องไม่ทำให้งานที่มันเฝ้าพังตาม
        $this->assertFalse((new TelegramAlertService)->send('ทดสอบ'));
    }
}
