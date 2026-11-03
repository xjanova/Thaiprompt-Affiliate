<?php

namespace Tests\Feature;

use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortuneGreetingService;
use App\Services\Fortune\FortunePageContext;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T1 — ธง FB web-only + แกนนโยบายใน FortuneBotMode
 *
 * ล็อกกติกาที่พลาดแล้ว = เพจโดนแบน (สลิปใน Messenger) หรือ LINE พัง:
 *
 *  1. ธงปิด = พฤติกรรมเดิมทุกบิต (ทางถอย / ดวงรายวัน / ไพ่ฟรี)
 *  2. ธงเปิด → เฉพาะ id ของ FB ที่โดน · LINE (`U…`) และ Telegram (`tg_…`) ไม่โดนเด็ดขาด
 *  3. FCS ที่สร้างโดยไม่เรียก setPlatform (currentPlatform='line' default) + PSID ของ FB → ต้องโดน
 *  4. เพจสาขา override ปิดธงไม่ได้
 *  5. exception ระหว่างตรวจในบริบท FB → fail-closed (บล็อก) แต่ LINE ไม่โดน
 *  6. ระดับ A (legacy paid in-flight) / ระดับ B (บิลค้างจ่ายยังไม่หมดอายุ) แยกถูก
 */
class FbWebOnlyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000077';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    private const TG_UID = 'tg_123456789';

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /**
     * ตั้งค่าแถวกลางแล้วอ่านกลับ (ล้าง static memo ทั้งก่อนและหลัง — ดู FortuneTransferModeTest)
     */
    private function settings(array $override = []): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'facebook_web_only' => false,
            'transfer_fallback_attempts' => 3,
            'transfer_fallback_days' => 30,
            'enable_free_card_reading' => true,
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        $fresh = FortuneTellingSetting::getSettings();

        foreach ($override as $key => $expected) {
            if (is_scalar($expected)) {
                $this->assertSame(
                    (string) $expected,
                    (string) $fresh->{$key},
                    "ตั้งค่า {$key} ไม่ลง DB — เทสต์จะอ่านค่า default แล้วผลลวง"
                );
            }
        }

        return $fresh;
    }

    private function on(array $override = []): FortuneBotMode
    {
        return new FortuneBotMode($this->settings(array_merge(['facebook_web_only' => true], $override)));
    }

    private function off(array $override = []): FortuneBotMode
    {
        return new FortuneBotMode($this->settings(array_merge(['facebook_web_only' => false], $override)));
    }

    private function grantFallbackAndAttempts(string $platform, string $uid): void
    {
        FortuneUserCredit::getOrCreate($uid, $platform)->forceFill([
            'fb_fallback_granted_at' => now()->subDay(),
            'transfer_attempts' => 5,
        ])->save();
    }

    private function reading(array $attrs = []): FortuneReading
    {
        return FortuneReading::create(array_merge([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ลูกค้า FB',
            'questions' => 'การงานจะเป็นอย่างไร',
            'is_paid' => false,
        ], $attrs));
    }

    private function ageReading(FortuneReading $reading, int $minutes): void
    {
        // ⚠️ ต้องใช้ query builder ดิบ — Eloquent update() จะประทับ updated_at = now ทับ
        $at = now()->subMinutes($minutes);
        DB::table('fortune_readings')->where('id', $reading->id)
            ->update(['updated_at' => $at, 'created_at' => $at]);
        // 🔐 (รีวิว) grace ระดับ A นับจาก paid_at → บิลที่จ่ายแล้วต้องแก่ทั้งก้อน
        DB::table('fortune_readings')->where('id', $reading->id)->where('is_paid', true)
            ->update(['paid_at' => $at]);
    }

    private function fakeDailyArticlesReady(): void
    {
        $greeting = \Mockery::mock(FortuneGreetingService::class);
        $greeting->shouldReceive('dailyArticlesReadyToday')->andReturn(true);
        $this->app->instance(FortuneGreetingService::class, $greeting);
    }

    // ────────────────────────────────────────────────────────────
    // 1. ธงปิด = เหมือนเดิม
    // ────────────────────────────────────────────────────────────

    public function test_ธงปิด_พฤติกรรมเดิมทุกโหมด(): void
    {
        $this->grantFallbackAndAttempts('facebook', self::PSID);

        foreach ([FortuneBotMode::MODE_CLASSIC, FortuneBotMode::MODE_TRANSFER, FortuneBotMode::MODE_DAILY] as $botMode) {
            $mode = $this->off(['fortune_bot_mode' => $botMode]);

            $this->assertFalse($mode->fbWebOnly(), $botMode);
            $this->assertFalse($mode->fbWebOnlyFor('facebook', self::PSID), $botMode);
            $this->assertTrue($mode->hasFbFallback('facebook', self::PSID), $botMode);
            $this->assertTrue($mode->attemptsExhausted('facebook', self::PSID), $botMode);
            $this->assertTrue($mode->freeCardEnabledFor('facebook'), $botMode);
            $this->assertTrue($mode->freeCardEnabledFor('facebook', self::PSID), $botMode);

            // transfer = ปิดเลนรายวันอยู่แล้ว (พฤติกรรมเดิม) · โหมดอื่นเปิดทั้ง FB และ LINE
            $expectedDaily = $botMode !== FortuneBotMode::MODE_TRANSFER;
            $this->assertSame($expectedDaily, $mode->dailyReplyAllowedFor('facebook', self::PSID), $botMode);
            $this->assertSame($expectedDaily, $mode->dailyReplyAllowedFor('line', self::LINE_UID), $botMode);
        }
    }

    public function test_ธงปิด_is_daily_serving_เหมือนเดิม(): void
    {
        $this->fakeDailyArticlesReady();

        $mode = $this->off(['fortune_bot_mode' => FortuneBotMode::MODE_DAILY]);

        $this->assertTrue($mode->isDailyServing());
        $this->assertTrue($mode->isDailyServing('facebook'));
        $this->assertTrue($mode->isDailyServing('line'));
    }

    // ────────────────────────────────────────────────────────────
    // 2. ธงเปิด → เฉพาะ FB
    // ────────────────────────────────────────────────────────────

    public function test_ธงเปิด_เฉพาะ_id_ของ_f_b_ที่โดน(): void
    {
        $mode = $this->on();

        $this->assertTrue($mode->fbWebOnly());
        $this->assertTrue($mode->fbWebOnlyFor('facebook', self::PSID));
        $this->assertTrue($mode->fbWebOnlyFor(null, self::PSID));

        // LINE / Telegram นอกขอบเขต — แม้ caller จะส่ง hint 'facebook' ผิดมา
        $this->assertFalse($mode->fbWebOnlyFor('line', self::LINE_UID));
        $this->assertFalse($mode->fbWebOnlyFor('facebook', self::LINE_UID));
        $this->assertFalse($mode->fbWebOnlyFor('telegram', self::TG_UID));
        $this->assertFalse($mode->fbWebOnlyFor('facebook', self::TG_UID));
    }

    public function test_resolve_platform_รูปทรง_id_ชนะ_hint(): void
    {
        $this->assertSame('line', FortuneBotMode::resolvePlatform('facebook', self::LINE_UID));
        $this->assertSame('telegram', FortuneBotMode::resolvePlatform('line', self::TG_UID));
        // PSID ตัวเลขล้วน + hint 'line' (currentPlatform default) → facebook
        $this->assertSame('facebook', FortuneBotMode::resolvePlatform('line', self::PSID));
        $this->assertSame('facebook', FortuneBotMode::resolvePlatform(null, self::PSID));
        // ไม่มี id → ใช้ hint · ไม่มีทั้งคู่ → facebook
        $this->assertSame('line', FortuneBotMode::resolvePlatform('line', ''));
        $this->assertSame('facebook', FortuneBotMode::resolvePlatform(null, null));
    }

    public function test_resolve_platform_ใช้_platform_ของบิลเมื่อ_id_ไม่บอก(): void
    {
        $this->settings();

        $lineRow = $this->reading(['platform' => 'line', 'facebook_user_id' => 'legacy-line-row']);
        $fbRow = $this->reading(['platform' => 'facebook', 'facebook_user_id' => 'legacy-fb-row']);

        $this->assertSame('line', FortuneBotMode::resolvePlatform('facebook', 'legacy-line-row', $lineRow));
        $this->assertSame('facebook', FortuneBotMode::resolvePlatform('line', 'legacy-fb-row', $fbRow));
    }

    // ────────────────────────────────────────────────────────────
    // 3. FCS ไม่ได้ setPlatform (cron / SMS / Stripe webhook)
    // ────────────────────────────────────────────────────────────

    public function test_fc_s_ที่ไม่ได้เรียก_set_platform_กับ_psi_d_ของ_f_b_ต้องโดนบล็อก(): void
    {
        $settings = $this->settings(['facebook_web_only' => true]);

        $fcs = new FortuneConversationService($settings);
        $prop = new \ReflectionProperty($fcs, 'currentPlatform');
        $prop->setAccessible(true);
        $currentPlatform = $prop->getValue($fcs);

        // ยืนยันสมมติฐาน: ค่าเริ่มต้นเป็น 'line' จริง (ถ้าเปลี่ยน เทสต์นี้ต้องทบทวน)
        $this->assertSame('line', $currentPlatform);

        $mode = new FortuneBotMode($settings);
        $this->assertTrue($mode->fbWebOnlyFor($currentPlatform, self::PSID));
        $this->assertFalse($mode->hasFbFallback($currentPlatform, self::PSID));
        $this->assertFalse($mode->attemptsExhausted($currentPlatform, self::PSID));
        $this->assertFalse($mode->dailyReplyAllowedFor($currentPlatform, self::PSID));
        $this->assertFalse($mode->freeCardEnabledFor($currentPlatform, self::PSID));

        // ลูกค้า LINE ตัวจริงผ่าน FCS เดียวกัน → ไม่โดน
        $this->assertFalse($mode->fbWebOnlyFor($currentPlatform, self::LINE_UID));
    }

    // ────────────────────────────────────────────────────────────
    // 4. เพจสาขา override ไม่ได้
    // ────────────────────────────────────────────────────────────

    public function test_เพจสาขา_override_ปิดธงไม่ได้(): void
    {
        $this->settings(['facebook_web_only' => true, 'transfer_fallback_days' => 30]);

        $page = new FortunePage([
            'platform' => 'facebook',
            'settings_override' => [
                'facebook_web_only' => false,
                'fb_web_only_since' => null,
                // คีย์ปกติยัง override ได้ — พิสูจน์ว่าการ merge ทำงานจริง
                'transfer_fallback_days' => 7,
            ],
        ]);
        $page->setAttribute('id', 987654);

        FortunePageContext::set($page);
        FortuneTellingSetting::clearSettingsCache();

        $merged = FortuneTellingSetting::getSettings();

        $this->assertSame(7, (int) $merged->transfer_fallback_days, 'override ปกติต้องยังทำงาน');
        $this->assertTrue((bool) $merged->facebook_web_only, 'เพจสาขาปิดธง FB web-only ได้ = เพจกลับมารับเงินบน FB');
        $this->assertNotNull($merged->fb_web_only_since);
        $this->assertTrue((new FortuneBotMode($merged))->fbWebOnlyFor('facebook', self::PSID));
        $this->assertContains('facebook_web_only', FortuneTellingSetting::PAGE_OVERRIDE_DENY_LIST);
    }

    public function test_เปิดธงแล้วประทับ_fb_web_only_since_เอง(): void
    {
        $settings = $this->settings(['facebook_web_only' => false, 'fb_web_only_since' => null]);
        $this->assertNull($settings->fb_web_only_since);

        $mode = $this->on();
        $this->assertNotNull($mode->fbWebOnlySince());
        $this->assertTrue($mode->fbWebOnlySince()->gt(now()->subMinute()));

        // ปิดธง = เก็บเวลาเดิมไว้เป็นประวัติ
        $since = $mode->fbWebOnlySince()->toDateTimeString();
        $offAgain = $this->off();
        $this->assertSame($since, $offAgain->fbWebOnlySince()?->toDateTimeString());
    }

    // ────────────────────────────────────────────────────────────
    // 5. fail-closed เฉพาะบริบท FB
    // ────────────────────────────────────────────────────────────

    public function test_exception_ในบริบท_f_b_ต้อง_fail_closed_แต่_lin_e_ไม่โดน(): void
    {
        $this->settings(['facebook_web_only' => false]);

        $broken = new class extends FortuneTellingSetting
        {
            public function getAttribute($key)
            {
                if ($key === 'facebook_web_only') {
                    throw new \RuntimeException('อ่านธงไม่ได้ (จำลอง)');
                }

                return parent::getAttribute($key);
            }
        };
        $broken->forceFill(['fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC, 'enable_free_card_reading' => true]);

        $mode = new FortuneBotMode($broken);

        $this->assertTrue($mode->fbWebOnly());
        $this->assertTrue($mode->fbWebOnlyFor('facebook', self::PSID));
        $this->assertFalse($mode->freeCardEnabledFor('facebook', self::PSID));
        $this->assertFalse($mode->dailyReplyAllowedFor('facebook', self::PSID));

        // LINE / Telegram ไม่แตะธงเลย → ไม่โดนแม้ธงพัง
        $this->assertFalse($mode->fbWebOnlyFor('line', self::LINE_UID));
        $this->assertFalse($mode->fbWebOnlyFor('telegram', self::TG_UID));
        $this->assertTrue($mode->freeCardEnabledFor('line', self::LINE_UID));
        $this->assertTrue($mode->dailyReplyAllowedFor('line', self::LINE_UID));
    }

    // ────────────────────────────────────────────────────────────
    // 6. เมธอดเดิมที่ต้องรับรู้ FB web-only
    // ────────────────────────────────────────────────────────────

    public function test_ธงเปิด_ไม่มีทางถอยบน_f_b_แต่_lin_e_เหมือนเดิม(): void
    {
        $this->grantFallbackAndAttempts('facebook', self::PSID);
        $this->grantFallbackAndAttempts('line', self::LINE_UID);

        $mode = $this->on(['fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER]);

        $this->assertFalse($mode->hasFbFallback('facebook', self::PSID));
        $this->assertFalse($mode->attemptsExhausted('facebook', self::PSID));

        $this->assertTrue($mode->hasFbFallback('line', self::LINE_UID));
        $this->assertTrue($mode->attemptsExhausted('line', self::LINE_UID));
    }

    public function test_daily_reply_allowed_for_line_ไม่เปลี่ยน_แต่_f_b_ปิด(): void
    {
        foreach ([FortuneBotMode::MODE_CLASSIC, FortuneBotMode::MODE_DAILY] as $botMode) {
            $mode = $this->on(['fortune_bot_mode' => $botMode]);

            $this->assertTrue($mode->dailyReplyAllowedFor('line', self::LINE_UID), $botMode);
            $this->assertFalse($mode->dailyReplyAllowedFor('facebook', self::PSID), $botMode);
            $this->assertFalse($mode->dailyReplyAllowedFor('facebook', null), $botMode);
        }

        // transfer ยังปิด LINE เหมือนเดิม (ไม่ใช่ผลของธงนี้)
        $this->assertFalse($this->on(['fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER])
            ->dailyReplyAllowedFor('line', self::LINE_UID));
    }

    public function test_is_daily_serving_ฝั่ง_f_b_ปิด_ฝั่ง_lin_e_เหมือนเดิม(): void
    {
        $this->fakeDailyArticlesReady();

        $mode = $this->on(['fortune_bot_mode' => FortuneBotMode::MODE_DAILY]);

        $this->assertFalse($mode->isDailyServing());
        $this->assertFalse($mode->isDailyServing('facebook'));
        $this->assertTrue($mode->isDailyServing('line'));
    }

    public function test_free_card_enabled_for_ฝั่ง_f_b_ปิด_ฝั่ง_lin_e_เหมือนเดิม(): void
    {
        $mode = $this->on(['enable_free_card_reading' => true]);

        $this->assertFalse($mode->freeCardEnabledFor('facebook'));
        $this->assertFalse($mode->freeCardEnabledFor('facebook', self::PSID));
        $this->assertFalse($mode->freeCardEnabledFor('line', self::PSID), 'hint line แต่ id เป็น PSID = FB');
        $this->assertTrue($mode->freeCardEnabledFor('line'));
        $this->assertTrue($mode->freeCardEnabledFor('line', self::LINE_UID));

        // transfer + สวิตช์หลักปิด → LINE ยังได้ฟรี (กติกาเดิม) · FB ไม่ได้
        $transfer = $this->on(['enable_free_card_reading' => false, 'fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER]);
        $this->assertTrue($transfer->freeCardEnabledFor('line', self::LINE_UID));
        $this->assertFalse($transfer->freeCardEnabledFor('facebook', self::PSID));
    }

    // ────────────────────────────────────────────────────────────
    // 7. ระดับ A — legacy paid in-flight
    // ────────────────────────────────────────────────────────────

    public function test_legacy_paid_ครอบ_deep_collecting_ที่จ่ายแล้ว(): void
    {
        $mode = $this->on();

        $this->reading(['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE]);

        $this->assertTrue($mode->fbLegacyPaidInFlight(self::PSID));
    }

    public function test_legacy_paid_ไม่ครอบ_collecting_ที่ยังไม่จ่าย(): void
    {
        $mode = $this->on();

        $this->reading(['is_paid' => false, 'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE]);

        $this->assertFalse($mode->fbLegacyPaidInFlight(self::PSID));
    }

    public function test_legacy_paid_ครอบ_celtic_และ_in_prediction(): void
    {
        $mode = $this->on();

        $this->reading(['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING]);
        $this->assertTrue($mode->fbLegacyPaidInFlight(self::PSID));

        $this->assertNotNull($mode->findFbLegacyPaidReading(self::PSID));

        // 39฿ ที่จ่ายแล้วกำลัง gen (STATUS_PAID ∈ IN_PREDICTION_STATUSES)
        $this->reading([
            'facebook_user_id' => self::PSID.'9',
            'is_paid' => true,
            'conversation_status' => FortuneReading::STATUS_PAID,
        ]);
        $this->assertTrue($mode->fbLegacyPaidInFlight(self::PSID.'9'));
    }

    public function test_legacy_paid_ครอบ_pro_session(): void
    {
        $mode = $this->on();

        $r = $this->reading([
            'is_paid' => true,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'conversation_state' => ['pro_session_active' => true, 'reading_sent_directly' => true],
        ]);
        $this->ageReading($r, 120);

        $this->assertTrue($mode->fbLegacyPaidInFlight(self::PSID));
    }

    public function test_legacy_paid_ครอบ_deep_response_ที่ค้างส่ง(): void
    {
        $mode = $this->on();

        $r = $this->reading([
            'is_paid' => true,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'deep_response' => 'คำทำนายเต็มที่ยังไม่ได้ส่ง',
        ]);
        $this->ageReading($r, 120);

        $this->assertTrue($mode->fbLegacyPaidInFlight(self::PSID));
    }

    public function test_legacy_paid_grace_30_นาทีหลังส่งของ(): void
    {
        $mode = $this->on();

        $r = $this->reading([
            'is_paid' => true,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'deep_response' => 'ส่งแล้ว',
            'conversation_state' => ['reading_sent_directly' => true],
        ]);
        $this->ageReading($r, 10);
        $this->assertTrue($mode->fbLegacyPaidInFlight(self::PSID), 'ยังอยู่ใน grace');

        $this->ageReading($r, 45);
        $this->assertFalse($mode->fbLegacyPaidInFlight(self::PSID), 'พ้น grace แล้ว');
    }

    public function test_legacy_paid_ไม่ใช่_f_b_คืน_false(): void
    {
        $mode = $this->on();

        $this->reading([
            'platform' => 'line',
            'facebook_user_id' => self::LINE_UID,
            'platform_user_id' => self::LINE_UID,
            'is_paid' => true,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
        ]);

        $this->assertFalse($mode->fbLegacyPaidInFlight(self::LINE_UID));
        $this->assertFalse($mode->fbLegacyPaidInFlight(''));
    }

    // ────────────────────────────────────────────────────────────
    // 8. ระดับ B — บิลค้างจ่ายยังไม่หมดอายุ
    // ────────────────────────────────────────────────────────────

    public function test_บิลค้างจ่ายภายใน_timeout_คือระดับ_b(): void
    {
        $mode = $this->on();

        foreach (FortuneBotMode::FB_PENDING_BILL_STATUSES as $i => $status) {
            $r = $this->reading([
                'facebook_user_id' => self::PSID.$i,
                'conversation_status' => $status,
            ]);
            $this->ageReading($r, 60);

            $this->assertTrue($mode->fbPendingBillOpen(self::PSID.$i), $status);
            $this->assertSame($r->id, $mode->findFbPendingBill(self::PSID.$i)?->id);
            // ยังไม่จ่าย = ไม่ใช่ระดับ A
            $this->assertFalse($mode->fbLegacyPaidInFlight(self::PSID.$i), $status);
        }
    }

    public function test_บิลค้างจ่ายเกิน_timeout_ไม่ใช่ระดับ_b(): void
    {
        $mode = $this->on();

        $r = $this->reading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
        $this->ageReading($r, FortuneReading::billTimeoutMinutes() + 5);

        $this->assertFalse($mode->fbPendingBillOpen(self::PSID));
    }

    public function test_สถานะอื่นไม่ใช่ระดับ_b(): void
    {
        $mode = $this->on();

        $this->reading(['conversation_status' => FortuneReading::STATUS_AWAITING_CONFIRMATION]);

        $this->assertFalse($mode->fbPendingBillOpen(self::PSID));
        $this->assertFalse($mode->fbPendingBillOpen(self::LINE_UID));
    }
}
