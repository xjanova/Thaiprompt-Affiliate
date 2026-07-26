<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\TransferModeTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🔀 โหมด TRANSFER — ดักหน้าแชท FB พาลูกค้าไปดูดวงฟรีที่เว็บ/LINE
 *
 * ล็อกกติกาที่พลาดแล้วเสียลูกค้า/เสียเงิน:
 *
 *  1. โหมด classic = ไม่มีอะไรเปลี่ยนเลย (ตัวดักต้องคืน null ทุกกรณี)
 *  2. LINE ไม่ถูกดัก — เป็นปลายทางที่เราอยากให้ลูกค้าไปใช้
 *  3. ไม่มีปลายทางให้ไป (ปุ่มเว็บปิด + ไม่ได้ตั้ง LINE OA) = ห้ามดัก
 *     ไม่งั้นลูกค้าเจอกล่องที่กดแล้วไม่มีอะไรเกิดขึ้น
 *  4. ลูกค้าที่ยืนยันว่าทำเว็บ/ไลน์ไม่เป็น ต้องไม่ถูกดักซ้ำอีก (จำ 30 วัน)
 *  5. พยายามพาไปครบตามที่ตั้ง → ต้องถามความสมัครใจ แล้วยอมเปิดบิลให้
 *  6. rollout % ต้องให้ผลเดิมกับคนเดิมทุกครั้ง (ไม่สลับบุคลิกไปมา)
 *  7. รอบแจกสิทธิ์ฟรีใหม่ (free_card_regrant_at) = คนที่เคยใช้ได้สิทธิ์ใหม่
 */
class FortuneTransferModeTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000001';

    private function settings(array $override = []): FortuneTellingSetting
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER,
            'transfer_rollout_percent' => 100,
            'transfer_fallback_attempts' => 3,
            'transfer_fallback_days' => 30,
            'transfer_box_cooldown_hours' => 24,
            // ต้องมีปลายทางอย่างน้อยหนึ่งทาง ไม่งั้นตัวดักจะปล่อยผ่าน (ตามดีไซน์)
            'line_bot_basic_id' => '@maemor',
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    /**
     * ตัวทดสอบ trait — จำลอง FortuneConversationService เท่าที่ trait ต้องใช้
     */
    private function interceptor(FortuneTellingSetting $settings, string $platform = 'facebook'): object
    {
        return new class($settings, $platform)
        {
            use TransferModeTrait;

            public function __construct(public $settings, public string $currentPlatform) {}

            /** เปิด public ให้เทสต์เรียกได้ */
            public function run(string $psid, string $text): ?array
            {
                return $this->maybeTransferIntercept($psid, $text);
            }

            // ตัวตรวจ intent ของคลาสจริง — จำลองแบบง่ายให้ครอบเคสที่เทสต์ใช้
            protected function isGenericFortuneRequest(string $text): bool
            {
                return str_contains($text, 'ดูดวง') || str_contains($text, 'ทำนาย');
            }

            protected function isExplicitlyAsking39(string $text): bool
            {
                return str_contains($text, '39');
            }
        };
    }

    public function test_โหมด_classic_ไม่ดักอะไรเลย(): void
    {
        $settings = $this->settings(['fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC]);

        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'ดูดวง'));
    }

    public function test_โหมด_transfer_ดักคำขอดูดวงแล้วส่งกล่องพาไป(): void
    {
        $settings = $this->settings();

        $result = $this->interceptor($settings)->run(self::PSID, 'ดูดวง');

        $this->assertIsArray($result);
        $this->assertSame('transfer_box', $result['action']);
        $this->assertNotEmpty($result['fb_template']);

        // ต้องนับความพยายาม เพื่อให้ ladder เดินไปถึงทางถอยได้
        $this->assertSame(1, (new FortuneBotMode($settings))->attempts('facebook', self::PSID));
    }

    public function test_ไม่ดักข้อความที่ไม่ได้ขอดูดวง(): void
    {
        $settings = $this->settings();

        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'สวัสดีค่ะแม่หมอ'));
    }

    public function test_lin_e_ไม่ถูกดัก(): void
    {
        $settings = $this->settings();

        $this->assertNull($this->interceptor($settings, 'line')->run('U'.str_repeat('a', 32), 'ดูดวง'));
    }

    public function test_ไม่มีปลายทางให้ไป_ต้องไม่ดัก(): void
    {
        // ปุ่มเว็บปิด (default) + ไม่ได้ตั้ง LINE OA = ไม่มีที่ให้ไป
        $settings = $this->settings(['line_bot_basic_id' => null, 'enable_web_fortune_button' => false]);

        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'ดูดวง'));
    }

    public function test_ลูกค้าบอกว่าทำไม่เป็น_ต้องถามความสมัครใจ(): void
    {
        $settings = $this->settings();

        foreach (['ไม่มีไลน์', 'กดไม่ได้', 'ทำไม่เป็นค่ะ', 'ขอดูที่นี่ได้ไหม'] as $text) {
            $result = $this->interceptor($settings)->run(self::PSID, $text);

            $this->assertIsArray($result, "ควรดัก: {$text}");
            $this->assertSame('transfer_stay_confirm', $result['action'], "ควรถามยืนยัน: {$text}");
        }
    }

    public function test_พยายามครบแล้วต้องถามความสมัครใจ(): void
    {
        $settings = $this->settings(['transfer_fallback_attempts' => 2]);

        FortuneUserCredit::getOrCreate(self::PSID, 'facebook')
            ->forceFill(['transfer_attempts' => 2])->save();

        $result = $this->interceptor($settings)->run(self::PSID, 'ดูดวง');

        $this->assertSame('transfer_stay_confirm', $result['action']);
    }

    public function test_ยืนยันขอดูในแชทแล้ว_ต้องไม่ถูกดักอีก(): void
    {
        $settings = $this->settings();
        $mode = new FortuneBotMode($settings);

        $mode->grantFbFallback('facebook', self::PSID);

        $this->assertTrue($mode->hasFbFallback('facebook', self::PSID));
        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'ดูดวง'));
    }

    public function test_สิทธิ์ที่ยืนยันไว้หมดอายุตามจำนวนวัน(): void
    {
        $settings = $this->settings(['transfer_fallback_days' => 7]);

        FortuneUserCredit::getOrCreate(self::PSID, 'facebook')
            ->forceFill(['fb_fallback_granted_at' => now()->subDays(8)])->save();

        $this->assertFalse((new FortuneBotMode($settings))->hasFbFallback('facebook', self::PSID));
    }

    public function test_rollout_ให้ผลเดิมกับคนเดิมทุกครั้ง(): void
    {
        $mode = new FortuneBotMode($this->settings(['transfer_rollout_percent' => 50]));

        $first = $mode->inRollout(self::PSID);
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first, $mode->inRollout(self::PSID), 'ผลต้องคงที่ ไม่สลับไปมา');
        }

        // 0% = ไม่มีใครเข้า · 100% = ทุกคนเข้า
        $this->assertFalse((new FortuneBotMode($this->settings(['transfer_rollout_percent' => 0])))->inRollout(self::PSID));
        $this->assertTrue((new FortuneBotMode($this->settings(['transfer_rollout_percent' => 100])))->inRollout(self::PSID));
    }

    public function test_รอบแจกสิทธิ์ฟรีใหม่_คนที่เคยใช้ได้สิทธิ์ใหม่(): void
    {
        $settings = $this->settings();

        FortuneReading::create([
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'facebook_user_id' => self::PSID,
            'reading_type' => FortuneReading::READING_TYPE_FREE_CARD,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'questions' => [],
            'ai_response' => 'คำทำนายฟรีของรอบก่อน',
            'responded_at' => now()->subDays(3),
        ]);

        // ยังไม่ตั้งรอบแจกใหม่ → ถือว่าใช้สิทธิ์ไปแล้ว
        $this->assertFalse((new FortuneBotMode($settings))->freeCardAvailable('facebook', self::PSID));

        // ตั้งรอบแจกใหม่หลังจากนั้น → ได้สิทธิ์ใหม่ทันที
        $settings = $this->settings(['free_card_regrant_at' => now()->subDay()]);
        $this->assertTrue((new FortuneBotMode($settings))->freeCardAvailable('facebook', self::PSID));
    }

    public function test_ความยาวคำทำนายฟรีตั้งค่าได้(): void
    {
        $this->assertSame(0, (new FortuneBotMode($this->settings(['free_card_max_chars' => 0])))->freeCardMaxChars());
        $this->assertSame(500, (new FortuneBotMode($this->settings(['free_card_max_chars' => 500])))->freeCardMaxChars());
        // clamp ขอบเขต
        $this->assertSame(200, (new FortuneBotMode($this->settings(['free_card_max_chars' => 10])))->freeCardMaxChars());
        $this->assertSame(2000, (new FortuneBotMode($this->settings(['free_card_max_chars' => 9999])))->freeCardMaxChars());
    }
}
