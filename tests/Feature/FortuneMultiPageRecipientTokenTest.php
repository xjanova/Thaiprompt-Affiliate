<?php

namespace Tests\Feature;

use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 🏬 (2026-09-13) ระบบหลายเพจ — ผู้รับคนที่ 2+ ต้องได้ token ของเพจตัวเอง
 *
 * บั๊กเดิม: cron (bubble-recover / celtic-redeliver / summary-redeliver / remind-stuck-celtic)
 * และ queue worker สร้าง FacebookWebhookService ใหม่ต่อผู้รับหนึ่งคน
 *   ตัวแรก bind เพจ A แบบ lazy (static) → ตัวที่สองเห็นว่า "มี context + ตัวเองไม่ได้ bind"
 *   → เข้าใจผิดว่าเจ้าของงานตั้งไว้ → ไม่หาเพจใหม่ → ส่งหาลูกค้าเพจ B ด้วย token ของเพจ A
 *   → Graph 400 → คำตอบ Celtic / คำทำนายที่ลูกค้าจ่ายเงินแล้วหายเงียบ
 *
 * @group fortune-multipage
 */
class FortuneMultiPageRecipientTokenTest extends TestCase
{
    use RefreshDatabase;

    private const PSID_A = '11110000000000001';

    private const PSID_B = '22220000000000002';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortunePageContext::forget();
        FortunePageContext::flushMemo();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getGlobalSettings();
        $settings->facebook_page_token = 'TOKEN_GLOBAL';
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        $pageA = FortunePage::create([
            'code' => 'page-a',
            'name' => 'เพจหลัก A',
            'platform' => 'facebook',
            'external_page_id' => '1110001',
            'page_access_token' => 'TOKEN_A',
            'is_active' => true,
            'is_default' => true,
        ]);
        $pageB = FortunePage::create([
            'code' => 'page-b',
            'name' => 'เพจสาขา B',
            'platform' => 'facebook',
            'external_page_id' => '2220002',
            'page_access_token' => 'TOKEN_B',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->reading(self::PSID_A, $pageA->id, now()->subMinutes(40));
        $this->reading(self::PSID_B, $pageB->id, now()->subMinutes(30));

        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'x', 'message_id' => 'm.1'], 200),
        ]);
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        parent::tearDown();
    }

    private function reading(string $psid, int $pageId, $updatedAt): FortuneReading
    {
        $reading = FortuneReading::create([
            'platform' => 'facebook',
            'platform_user_id' => $psid,
            'facebook_user_id' => $psid,
            'fortune_page_id' => $pageId,
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            'questions' => [],
            'ai_response' => '',
            'ai_provider' => 'none',
        ]);

        $reading->timestamps = false;
        $reading->forceFill(['updated_at' => $updatedAt])->save();
        $reading->timestamps = true;

        return $reading;
    }

    /**
     * token ที่ใช้ส่งหาผู้รับแต่ละคน (จาก body ของ Graph Send API)
     *
     * @return array<string, array<int, string>>
     */
    private function tokensByRecipient(): array
    {
        $out = [];
        foreach (Http::recorded() as [$request]) {
            /** @var Request $request */
            if (! str_contains($request->url(), 'graph.facebook.com')) {
                continue;
            }
            $data = $request->data();
            $recipient = (string) ($data['recipient']['id'] ?? '');
            if ($recipient !== '') {
                $out[$recipient][] = (string) ($data['access_token'] ?? '');
            }
        }

        return $out;
    }

    public function test_new_sender_per_recipient_uses_each_recipients_own_page_token(): void
    {
        // ท่าเดียวกับ cron / queue worker — instance ใหม่ต่อผู้รับ ตัวแรก bind เพจ A ค้างไว้ใน static
        (new FacebookWebhookService)->sendMessage(self::PSID_A, 'คำตอบไพ่ของลูกค้าเพจ A');
        (new FacebookWebhookService)->sendMessage(self::PSID_B, 'คำตอบไพ่ของลูกค้าเพจสาขา B');

        $tokens = $this->tokensByRecipient();

        $this->assertSame(['TOKEN_A'], array_values(array_unique($tokens[self::PSID_A] ?? [])));
        $this->assertSame(['TOKEN_B'], array_values(array_unique($tokens[self::PSID_B] ?? [])),
            'ลูกค้าคนที่ 2 ของเพจสาขาต้องได้ token ของเพจตัวเอง ไม่ใช่ของเพจลูกค้าคนแรก');
    }

    public function test_context_set_by_the_job_owner_is_still_respected(): void
    {
        // เจ้าของงาน (webhook/job) ตั้งสาขาเอง = ของจริง — FB service ห้ามเดาทับ (พฤติกรรมเดิม)
        FortunePageContext::set(FortunePage::where('code', 'page-b')->first());

        (new FacebookWebhookService)->sendMessage(self::PSID_B, 'คำตอบในงานของเพจ B');

        $this->assertSame(['TOKEN_B'], $this->tokensByRecipient()[self::PSID_B] ?? []);
        $this->assertFalse(FortunePageContext::isLazilyBound());
    }

    public function test_run_restores_lazy_flag(): void
    {
        FortunePageContext::bindLazilyFromId(FortunePage::where('code', 'page-a')->value('id'));
        $this->assertTrue(FortunePageContext::isLazilyBound());

        FortunePageContext::run(FortunePage::where('code', 'page-b')->first(), function () {
            $this->assertFalse(FortunePageContext::isLazilyBound(), 'ใน run() = เจ้าของงานตั้งเอง');
        });

        $this->assertTrue(FortunePageContext::isLazilyBound(), 'ออกจาก run() แล้วต้องคืนสถานะ "ของเดา" เดิม');
    }

    public function test_bubble_recover_cron_sends_second_recipient_with_its_own_page_token(): void
    {
        foreach ([self::PSID_A, self::PSID_B] as $psid) {
            $reading = FortuneReading::where('platform_user_id', $psid)->first();
            $reading->setConversationState('bubble_pending', [
                'platform' => 'facebook',
                'user_id' => $psid,
                'bubbles' => ['กล่องคำทำนายที่ค้างของ '.$psid],
                'tail' => null,
                'tail_qr' => [],
            ]);
            $reading->setConversationState('bubble_pending_at', now()->subMinutes(30)->toIso8601String());
        }

        // เพจ A ต้องถูกกู้ก่อน (อายุมากกว่า) — ให้ลูกค้าเพจสาขาเป็น "คนที่ 2" ของรอบ
        FortuneReading::where('platform_user_id', self::PSID_A)->update(['updated_at' => now()->subMinutes(40)]);
        FortuneReading::where('platform_user_id', self::PSID_B)->update(['updated_at' => now()->subMinutes(20)]);

        $this->artisan('fortune:bubble-recover')->assertSuccessful();

        $tokens = $this->tokensByRecipient();

        $this->assertNotEmpty($tokens[self::PSID_A] ?? [], 'ลูกค้าเพจ A ต้องได้กล่องที่ค้าง');
        $this->assertSame(['TOKEN_A'], array_values(array_unique($tokens[self::PSID_A])));
        $this->assertNotEmpty($tokens[self::PSID_B] ?? [], 'ลูกค้าเพจสาขา B ต้องได้กล่องที่ค้าง');
        $this->assertSame(['TOKEN_B'], array_values(array_unique($tokens[self::PSID_B])),
            'cron กู้คำทำนาย: ผู้รับคนที่ 2 ของเพจสาขาต้องได้ token ของเพจตัวเอง');
        $this->assertNull(FortunePageContext::current(), 'จบรอบแล้วห้ามทิ้ง context ค้าง');
    }
}
