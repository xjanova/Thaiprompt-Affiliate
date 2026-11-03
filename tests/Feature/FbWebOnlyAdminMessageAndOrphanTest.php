<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FortuneTakeoverController;
use App\Http\Controllers\Admin\FortuneUsersController;
use App\Models\FortunePage;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Models\SmsPaymentNotification;
use App\Services\FcmNotificationService;
use App\Services\Fortune\FbWebOnlyAdminMessageFilter;
use App\Services\Fortune\FortunePageContext;
use App\Services\SmsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03 FB web-only · T19) กรองข้อความแอดมิน + เงินกำพร้า
 *
 * ล็อกกติกา:
 *  1. ธงเปิด → แอดมินส่ง "โอนมาที่ 123-4-56789-0" ไปลูกค้า FB ต้องถูกบล็อก (หน้าผู้ใช้ + หน้าเทคโอเวอร์)
 *     และการบล็อกต้องไม่ทิ้งเทคโอเวอร์ค้างไว้
 *  2. ลูกค้า LINE ไม่โดนกรองเลย · ธงปิด = ส่งได้เหมือนเดิม
 *  3. broadcast ใต้ธง: ลูกค้า FB ต้องอยู่ในหน้าต่าง 24 ชม. · คำเรื่องเงินข้าม FB แต่ LINE ยังได้ · ใช้ token เพจสาขา
 *  4. เงินกำพร้ายอด Celtic 99 หลัง T0 ต้องถูกปักธง · ก่อน T0 / ธงปิด = ไม่ปัก (พฤติกรรมเดิม)
 */
class FbWebOnlyAdminMessageAndOrphanTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const PSID = '61550000000077';

    private const PSID_OLD = '61550000000078';

    private const PSID_NONE = '61550000000079';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    private const MONEY_MSG = 'โอนมาที่ 123-4-56789-0 นะคะ';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortunePageContext::forget();

        $this->settings([
            'is_enabled' => true,
            'facebook_page_token' => 'EAAglobalToken',
            'line_channel_access_token' => 'LINEtestToken',
            'facebook_web_only' => false,
        ]);

        Http::fake(['*' => Http::response(['message_id' => 'm_1', 'recipient_id' => self::PSID], 200)]);

        $this->mock(FcmNotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyOrphanFortunePayment')->andReturn(true);
            $mock->shouldIgnoreMissing();
        });
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    private function settings(array $override): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill($override)->save();
        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function webOnlyOn(): void
    {
        $fresh = $this->settings(['facebook_web_only' => true]);
        $this->assertTrue((bool) $fresh->facebook_web_only, 'ธงต้องลง DB');
    }

    /** คำขอ /me/messages ที่ยิงออกไปจริง */
    private function fbSends()
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), '/me/messages'))->map(fn ($pair) => $pair[0]);
    }

    private function lineSends()
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.line.me'))->map(fn ($pair) => $pair[0]);
    }

    private function usersController(): FortuneUsersController
    {
        return app(FortuneUsersController::class);
    }

    // ============================================================
    // ตัวตรวจคำ
    // ============================================================

    public function test_ตัวตรวจคำเงิน_จับคำโอน_เลขบัญชี_และปล่อยข้อความปกติ(): void
    {
        $this->assertNotNull(FbWebOnlyAdminMessageFilter::findMoneyWord(self::MONEY_MSG));
        $this->assertNotNull(FbWebOnlyAdminMessageFilter::findMoneyWord('เลข 1234567890 ค่ะ'), 'เลขบัญชีล้วนต้องโดน');
        $this->assertNotNull(FbWebOnlyAdminMessageFilter::findMoneyWord('ดูเชิงลึกแค่ 39 บาทเองค่ะ'));
        $this->assertNotNull(FbWebOnlyAdminMessageFilter::findMoneyWord('สแกน QR ได้เลย'));
        $this->assertNull(FbWebOnlyAdminMessageFilter::findMoneyWord('สวัสดีค่ะ แม่หมอคิดถึงนะคะ วันนี้ดวงเป็นยังไงบ้าง'));
    }

    public function test_block_reason_ธงเปิด_บล็อก_fb_แต่ไม่บล็อก_line(): void
    {
        $this->webOnlyOn();

        $this->assertNotNull(FbWebOnlyAdminMessageFilter::blockReason('facebook', self::PSID, self::MONEY_MSG));
        // hint ผิดเป็น line แต่ id เป็น PSID → ยังต้องโดน
        $this->assertNotNull(FbWebOnlyAdminMessageFilter::blockReason('line', self::PSID, self::MONEY_MSG));
        $this->assertNull(FbWebOnlyAdminMessageFilter::blockReason('line', self::LINE_UID, self::MONEY_MSG), 'LINE ห้ามโดน');
        $this->assertNull(FbWebOnlyAdminMessageFilter::blockReason('facebook', self::PSID, 'สวัสดีค่ะ'));
    }

    // ============================================================
    // หน้าผู้ใช้ — ส่งรายคน
    // ============================================================

    public function test_หน้าผู้ใช้_ธงเปิด_ส่งคำโอนเงินไป_fb_ถูกบล็อก_ไม่ยิงออก(): void
    {
        $this->webOnlyOn();

        $request = HttpRequest::create('/admin/fortune/users/send-message', 'POST', [
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'message' => self::MONEY_MSG,
        ]);
        $this->usersController()->sendMessage($request);

        $this->assertStringContainsString('ดูดวงบนเว็บเท่านั้น', (string) session('error'));
        $this->assertCount(0, $this->fbSends(), 'ห้ามมีข้อความหลุดเข้า Messenger');
    }

    public function test_หน้าผู้ใช้_ธงปิด_ส่งได้เหมือนเดิม(): void
    {
        $request = HttpRequest::create('/admin/fortune/users/send-message', 'POST', [
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'message' => self::MONEY_MSG,
        ]);
        $this->usersController()->sendMessage($request);

        $this->assertNull(session('error'));
        $this->assertCount(1, $this->fbSends());
    }

    public function test_หน้าผู้ใช้_ธงเปิด_ข้อความปกติไป_fb_ยังส่งได้(): void
    {
        $this->webOnlyOn();

        $request = HttpRequest::create('/admin/fortune/users/send-message', 'POST', [
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'message' => 'แม่หมอคิดถึงนะคะ แวะมาคุยกันได้เสมอค่ะ',
        ]);
        $this->usersController()->sendMessage($request);

        $this->assertNull(session('error'));
        $this->assertCount(1, $this->fbSends());
    }

    // ============================================================
    // หน้าเทคโอเวอร์
    // ============================================================

    public function test_เทคโอเวอร์_ธงเปิด_คำโอนเงินไป_fb_ได้_422_ไม่ส่ง_ไม่เริ่มเทคโอเวอร์(): void
    {
        $this->webOnlyOn();
        Auth::login($this->makeAdmin());
        $reading = $this->makeReading();

        $request = HttpRequest::create('/admin/takeover/'.$reading->id.'/send', 'POST', ['message' => self::MONEY_MSG]);
        $response = app(FortuneTakeoverController::class)->sendMessage($request, $reading);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertSame(FbWebOnlyAdminMessageFilter::REASON_MONEY_WORD, $response->getData(true)['blocked']);
        $this->assertCount(0, $this->fbSends());
        $this->assertNull($reading->fresh()->admin_takeover_until, 'บล็อกแล้วห้ามทิ้งเทคโอเวอร์ค้าง (บอทเงียบฟรี ๆ)');
    }

    public function test_เทคโอเวอร์_ธงเปิด_ลูกค้า_line_ส่งคำเงินได้ตามเดิม(): void
    {
        $this->webOnlyOn();
        Auth::login($this->makeAdmin());
        $reading = $this->makeReading([
            'platform' => 'line',
            'platform_user_id' => self::LINE_UID,
            'facebook_user_id' => self::LINE_UID,
        ]);

        $request = HttpRequest::create('/admin/takeover/'.$reading->id.'/send', 'POST', ['message' => self::MONEY_MSG]);
        $response = app(FortuneTakeoverController::class)->sendMessage($request, $reading);

        $this->assertArrayNotHasKey('blocked', $response->getData(true), 'LINE ต้องไม่ผ่านด่านกรองเงิน');
        $this->assertCount(0, $this->fbSends());
    }

    // ============================================================
    // 🔐 รีวิว — แอปแอดมิน (API) + หน้าคำถามที่ฝากไว้
    // ============================================================

    public function test_แอปแอดมิน_ธงเปิด_คำเงินไป_fb_ได้_422_ไม่ส่ง_ไม่เริ่มเทคโอเวอร์_line_ส่งได้(): void
    {
        $this->webOnlyOn();
        $admin = $this->makeAdmin();
        $reading = $this->makeReading();

        $request = HttpRequest::create('/api/admin/chat/send', 'POST', ['reading_id' => $reading->id, 'text' => self::MONEY_MSG]);
        $request->setUserResolver(fn () => $admin);
        $response = app(\App\Http\Controllers\Api\Admin\ChatController::class)->send($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(FbWebOnlyAdminMessageFilter::REASON_MONEY_WORD, $response->getData(true)['blocked']);
        $this->assertStringContainsString('ส่งไม่ได้', $response->getData(true)['message']);
        $this->assertCount(0, $this->fbSends());
        $this->assertNull($reading->fresh()->admin_takeover_until, 'บล็อกแล้วห้ามทิ้งเทคโอเวอร์ค้าง');

        // LINE ไม่โดนกรอง
        $request = HttpRequest::create('/api/admin/chat/send', 'POST', [
            'platform' => 'line', 'platform_user_id' => self::LINE_UID, 'text' => self::MONEY_MSG,
        ]);
        $request->setUserResolver(fn () => $admin);
        $response = app(\App\Http\Controllers\Api\Admin\ChatController::class)->send($request);
        $this->assertArrayNotHasKey('blocked', $response->getData(true));
    }

    public function test_คำถามที่ฝากไว้_ธงเปิด_ตอบเรื่องเงินไป_fb_ถูกบล็อก_และไม่แปะคำถามเงินของลูกค้า(): void
    {
        $this->webOnlyOn();
        Auth::login($this->makeAdmin());
        $question = \App\Models\FortuneSavedQuestion::create([
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'user_name' => 'ลูกค้า',
            'question' => 'โอนแล้วนะคะ สลิปส่งไปแล้ว',
            'reason' => 'fb_web_only_payment',
        ]);
        $controller = app(\App\Http\Controllers\Admin\FortuneSavedQuestionsController::class);

        // ตอบเรื่องเงิน → 422 (JSON) ไม่บันทึก ไม่ส่ง
        $request = HttpRequest::create('/admin/fortune/saved-questions/'.$question->id.'/reply', 'POST', ['admin_reply' => self::MONEY_MSG]);
        $request->headers->set('Accept', 'application/json');
        $response = $controller->reply($request, $question);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse((bool) $question->fresh()->is_replied);
        $this->assertCount(0, $this->fbSends());

        // ตอบปกติ → ส่งได้ และข้อความไม่มีคำถามเรื่องเงินของลูกค้าแปะไป
        $request = HttpRequest::create('/admin/fortune/saved-questions/'.$question->id.'/reply', 'POST', ['admin_reply' => 'ทีมตรวจให้เรียบร้อยแล้วค่ะ แม่หมอรออยู่ในห้องนะคะ']);
        $controller->reply($request, $question->fresh());

        $sent = $this->fbSends()->map(fn ($r) => json_encode($r->data(), JSON_UNESCAPED_UNICODE))->implode("\n");
        $this->assertStringContainsString('ทีมตรวจให้เรียบร้อยแล้วค่ะ', $sent);
        $this->assertStringNotContainsString('สลิป', $sent);
        $this->assertStringNotContainsString('โอนแล้ว', $sent);
        $this->assertTrue((bool) $question->fresh()->is_sent_to_user);
    }

    public function test_คำถามที่ฝากไว้_ส่งซ้ำคำตอบเงินเก่าเข้า_fb_ใต้ธงไม่ได้_line_ได้(): void
    {
        Auth::login($this->makeAdmin());
        $fb = \App\Models\FortuneSavedQuestion::create([
            'platform' => 'facebook', 'platform_user_id' => self::PSID, 'question' => 'ถามดวง',
            'admin_reply' => self::MONEY_MSG, 'is_replied' => true, 'is_sent_to_user' => false,
        ]);
        $this->webOnlyOn();

        $controller = app(\App\Http\Controllers\Admin\FortuneSavedQuestionsController::class);
        $controller->resend($fb);
        $this->assertCount(0, $this->fbSends(), 'คำตอบเก่าที่มีเรื่องเงินห้ามส่งซ้ำเข้า FB');
        $this->assertFalse((bool) $fb->fresh()->is_sent_to_user);
    }

    // ============================================================
    // broadcast
    // ============================================================

    /** ลูกค้า FB 3 คน (ใน 24 ชม. / เกิน / ไม่มีข้อมูล) + LINE 1 คน */
    private function broadcastWorld(): FortunePage
    {
        $page = FortunePage::create([
            'code' => 'branch-a',
            'name' => 'สาขา A',
            'platform' => 'facebook',
            'external_page_id' => '900000000000001',
            'page_access_token' => 'EAApageAToken',
            'is_active' => true,
        ]);

        $this->makeReading(['platform_user_id' => self::PSID, 'facebook_user_id' => self::PSID, 'fortune_page_id' => $page->id]);
        $this->makeReading(['platform_user_id' => self::PSID_OLD, 'facebook_user_id' => self::PSID_OLD]);
        $this->makeReading(['platform_user_id' => self::PSID_NONE, 'facebook_user_id' => self::PSID_NONE]);
        $this->makeReading(['platform' => 'line', 'platform_user_id' => self::LINE_UID, 'facebook_user_id' => self::LINE_UID]);

        FortuneUserCredit::getOrCreate(self::PSID, 'facebook')->forceFill(['last_dm_at' => now()->subHour()])->save();
        FortuneUserCredit::getOrCreate(self::PSID_OLD, 'facebook')->forceFill(['last_dm_at' => now()->subHours(30)])->save();

        return $page;
    }

    public function test_broadcast_ธงเปิด_fb_เฉพาะคนใน_24_ชม_และใช้_token_เพจสาขา(): void
    {
        $this->webOnlyOn();
        $this->broadcastWorld();

        $request = HttpRequest::create('/admin/fortune/users/broadcast', 'POST', [
            'platform' => 'facebook',
            'target' => 'all',
            'message' => 'แม่หมอคิดถึงนะคะ',
        ]);
        $this->usersController()->broadcastMessage($request);

        $sends = $this->fbSends();
        $this->assertCount(1, $sends, 'ต้องส่งเฉพาะลูกค้าที่ทักมาใน 24 ชม.');
        $first = $sends->first();
        $this->assertSame(self::PSID, $first->data()['recipient']['id'] ?? null);
        $this->assertStringContainsString('EAApageAToken', $first->url().json_encode($first->data()).implode(',', $first->header('Authorization')),
            'ต้องส่งด้วย token ของเพจสาขาลูกค้า ไม่ใช่ค่ากลาง');
        $this->assertStringContainsString('ไม่ได้ทักมาใน 24 ชม.', (string) session('success'));
    }

    public function test_broadcast_ธงเปิด_คำเงินเลือก_facebook_ถูกปฏิเสธทั้งก้อน(): void
    {
        $this->webOnlyOn();
        $this->broadcastWorld();

        $request = HttpRequest::create('/admin/fortune/users/broadcast', 'POST', [
            'platform' => 'facebook',
            'target' => 'all',
            'message' => self::MONEY_MSG,
        ]);
        $this->usersController()->broadcastMessage($request);

        $this->assertStringContainsString('ดูดวงบนเว็บเท่านั้น', (string) session('error'));
        $this->assertCount(0, $this->fbSends());
    }

    public function test_broadcast_ธงเปิด_คำเงินเลือก_all_ข้าม_fb_แต่_line_ยังได้(): void
    {
        $this->webOnlyOn();
        $this->broadcastWorld();

        $request = HttpRequest::create('/admin/fortune/users/broadcast', 'POST', [
            'platform' => 'all',
            'target' => 'all',
            'message' => self::MONEY_MSG,
        ]);
        $this->usersController()->broadcastMessage($request);

        $this->assertCount(0, $this->fbSends(), 'ลูกค้า FB ต้องไม่ได้รับข้อความเรื่องเงิน');
        $this->assertGreaterThanOrEqual(1, $this->lineSends()->count(), 'LINE ต้องได้รับตามเดิม');
        $this->assertStringContainsString('ข้อความพูดเรื่องเงิน', (string) session('success'));
    }

    public function test_broadcast_ธงปิด_ส่ง_fb_ทุกคนเหมือนเดิม(): void
    {
        $this->broadcastWorld();

        $request = HttpRequest::create('/admin/fortune/users/broadcast', 'POST', [
            'platform' => 'facebook',
            'target' => 'all',
            'message' => 'แม่หมอคิดถึงนะคะ',
        ]);
        $this->usersController()->broadcastMessage($request);

        $this->assertCount(3, $this->fbSends(), 'ธงปิด = ไม่มีด่าน 24 ชม. ใหม่');
    }

    // ============================================================
    // เงินกำพร้า
    // ============================================================

    private function sms(float $amount, $at): SmsPaymentNotification
    {
        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'sender_or_receiver' => 'X-4572',
            'sms_timestamp' => $at,
            'device_id' => 'SMSCHK-T19',
            'nonce' => Str::random(24),
            'status' => 'pending',
        ]);
        $sms->forceFill(['created_at' => $at])->save();

        return $sms->fresh();
    }

    /** เรียก 2 ด่านตามลำดับเดียวกับ processNotification (ด่านเดิม → ด่านแพ็กเกจ FB web-only) */
    private function runOrphanGuards(SmsPaymentNotification $sms): bool
    {
        $service = app(SmsPaymentService::class);
        $legacy = new \ReflectionMethod($service, 'flagOrphanFortunePayment');
        $legacy->setAccessible(true);
        if ($legacy->invoke($service, $sms)) {
            return true;
        }

        $pkg = new \ReflectionMethod($service, 'flagFbWebOnlyPackageOrphan');
        $pkg->setAccessible(true);

        return (bool) $pkg->invoke($service, $sms);
    }

    private function webOnlySince($since): void
    {
        $this->webOnlyOn();
        $fresh = $this->settings(['fb_web_only_since' => $since, 'deep_reading_price' => 39, 'celtic_cross_price' => 99]);
        $this->assertTrue($fresh->fb_web_only_since->equalTo($since), 'จุดตัด T0 ต้องลง DB ตามที่ตั้ง');
    }

    public function test_เงินกำพร้า_celtic_99_หลัง_t0_ถูกปักธง(): void
    {
        $this->webOnlySince(now()->subHour()->startOfSecond());
        $sms = $this->sms(99.37, now());

        $this->assertTrue($this->runOrphanGuards($sms));

        $fresh = $sms->fresh();
        $this->assertSame('requires_admin_review', $fresh->status);
        $raw = json_decode((string) $fresh->raw_payload, true);
        $this->assertIsArray($raw, 'ธงต้องถูกเก็บลง raw_payload (คอลัมน์จริง)');
        $this->assertTrue($raw['orphan_fortune_payment']);
        $this->assertSame('fb_web_only_orphan_package_price', $raw['orphan_reason']);
        $this->assertEquals(99.0, $raw['expected_price_range'][0]);
    }

    public function test_เงินกำพร้า_celtic_99_ก่อน_t0_ไม่ปักธง(): void
    {
        $this->webOnlySince(now()->subHour()->startOfSecond());
        $sms = $this->sms(99.37, now()->subHours(3));

        $this->assertFalse($this->runOrphanGuards($sms));
        $this->assertSame('pending', $sms->fresh()->status);
    }

    public function test_เงินกำพร้า_ธงปิด_พฤติกรรมเดิม_ปักเฉพาะช่วงเชิงลึก(): void
    {
        $this->settings(['deep_reading_price' => 39, 'celtic_cross_price' => 99]);

        $this->assertFalse($this->runOrphanGuards($this->sms(99.37, now())), 'ธงปิด Celtic 99 ไม่ถูกปัก (เหมือนเดิม)');
        $this->assertTrue($this->runOrphanGuards($this->sms(39.21, now())), 'ช่วงเชิงลึกยังปักเหมือนเดิม');
    }

    public function test_เงินกำพร้า_ธงเปิด_ยอดไม่ตรงแพ็กเกจไม่ปักธง(): void
    {
        $this->webOnlySince(now()->subHour()->startOfSecond());

        $this->assertFalse($this->runOrphanGuards($this->sms(250.00, now())));
    }
}
