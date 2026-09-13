<?php

namespace Tests\Feature;

use App\Jobs\FortuneAiPingJob;
use App\Jobs\SendBillReminderJob;
use App\Jobs\SendCelticDelayedPrediction;
use App\Jobs\SendPricingFollowUpJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortunePdpaDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ✈️ (2026-09-13) job/cron ที่เคยแยกแค่ facebook/line — ลูกค้า Telegram ต้องได้ของทาง Telegram
 *
 * ทุกเคสในไฟล์นี้คือบั๊กที่เจอจริงตอนไล่โค้ด (สาขา else ของ if line/facebook):
 *   - คำทำนาย Celtic 99฿ (SendCelticDelayedPrediction) เดิม else = LINE push → หาย
 *   - ทวงบิล / อธิบายค่าครู เดิมตก "platform ไม่รู้จัก" → ไม่ส่ง
 *   - กล่องบอกความคืบหน้าตอน AI คิดนาน เดิมไม่ส่ง
 *   - ลบข้อมูล PDPA เดิมแปลง telegram เป็น facebook → ตารางที่คีย์ด้วย platform ลบไม่ครบ
 *
 * @group telegram
 */
class TelegramJobRoutingTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsawZ';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->telegram_enabled = true;
        $settings->telegram_bot_token = self::TOKEN;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 9]]),
            '*' => Http::response([], 200),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function telegramMessages(): array
    {
        $out = [];
        foreach (Http::recorded() as [$request]) {
            /** @var Request $request */
            if (str_contains($request->url(), 'api.telegram.org') && str_ends_with($request->url(), '/sendMessage')) {
                $out[] = $request->data();
            }
        }

        return $out;
    }

    private function otherPlatformCalls(): int
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'graph.facebook.com') || str_contains($pair[0]->url(), 'api.line.me'))
            ->count();
    }

    private function invokePrivate(object $job, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($job, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($job, $args);
    }

    public function test_celtic_delayed_prediction_goes_to_telegram_not_line(): void
    {
        $reading = FortuneReading::create([
            'platform' => 'telegram',
            'platform_user_id' => 'tg_555',
            'facebook_user_id' => 'tg_555',
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING,
            'questions' => [],
            'ai_response' => '',
            'ai_provider' => 'none',
            'is_paid' => true,
        ]);

        (new SendCelticDelayedPrediction($reading->id, 'ไพ่ใบนี้บอกว่าเรื่องงานกำลังจะดีขึ้นค่ะ', 'telegram', 'tg_555'))->handle();

        $sent = $this->telegramMessages();
        $this->assertNotEmpty($sent, 'คำทำนาย Celtic ที่ลูกค้า Telegram จ่ายแล้วต้องถึง Telegram');
        $this->assertSame('555', (string) $sent[0]['chat_id']);
        $this->assertStringContainsString('เรื่องงานกำลังจะดีขึ้น', $sent[0]['text']);
        $this->assertSame(0, $this->otherPlatformCalls());
    }

    public function test_bill_reminder_is_sent_through_telegram(): void
    {
        $this->invokePrivate(new SendBillReminderJob(1), 'sendMessage', ['telegram', 'tg_555', 'ยังรอค่าครูอยู่นะคะ']);

        $this->assertSame('ยังรอค่าครูอยู่นะคะ', $this->telegramMessages()[0]['text'] ?? null);
        $this->assertSame(0, $this->otherPlatformCalls());
    }

    public function test_pricing_follow_up_is_sent_through_telegram(): void
    {
        $this->invokePrivate(new SendPricingFollowUpJob('telegram', 'tg_555'), 'sendMessage', ['ค่าครู 39 บาทนะคะ']);

        $this->assertSame('ค่าครู 39 บาทนะคะ', $this->telegramMessages()[0]['text'] ?? null);
    }

    public function test_ai_progress_ping_is_sent_through_telegram(): void
    {
        $ok = $this->invokePrivate(new FortuneAiPingJob(1, 'telegram', 'tg_555', 30, 's'), 'sendMessage', ['telegram', 'tg_555', '🔮 แม่หมอกำลังพิจารณาไพ่...']);

        $this->assertTrue($ok);
        $this->assertSame('🔮 แม่หมอกำลังพิจารณาไพ่...', $this->telegramMessages()[0]['text'] ?? null);
    }

    public function test_pdpa_deletion_reaches_rows_keyed_by_telegram_platform(): void
    {
        DB::table('fortune_customer_personas')->insert([
            'platform' => 'telegram',
            'platform_user_id' => 'tg_555',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(FortunePdpaDeletionService::class)->deleteForCustomer('telegram', 'tg_555');

        $this->assertTrue($result['ok']);
        $this->assertSame('telegram', $result['platform'], 'ห้ามแปลง telegram เป็น facebook');
        $this->assertSame(0, DB::table('fortune_customer_personas')->where('platform_user_id', 'tg_555')->count());
    }
}
