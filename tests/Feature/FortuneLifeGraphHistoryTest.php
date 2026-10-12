<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\LifeGraphService;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 📚 (2026-10-12) อ่านย้อนหลังต้องมีกราฟชีวิต — owner: "บันทึกไว้ให้ลูกค้า · ลูกค้าเก่าพิมพ์อ่านย้อนหลัง
 *   ก็สร้างมาใหม่แล้วบันทึกเข้าไป"
 *
 * ล็อกไว้: รูปที่บันทึกแล้วใช้ซ้ำ (39 + 99) · บิลเก่าไม่มีรูป = วาดใหม่ + บันทึก · ไฟล์หาย = วาดใหม่
 *   ไม่มีวันเกิด = ไม่มีรูป (ไม่ล้ม) · ผลอ่านย้อนหลัง 39 แนบ lifegraph_image_url
 *
 * @group fortune-lifegraph
 */
class FortuneLifeGraphHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();
        Storage::fake('public');
    }

    private function reading(array $attrs = [], array $state = []): FortuneReading
    {
        $r = FortuneReading::create($attrs + [
            'facebook_user_id' => 'psid-hist',
            'facebook_user_name' => 'ทดสอบ',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => 'psid-hist',
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => true,
            'paid_at' => now()->subMonths(3),
            'amount_paid' => 39.00,
            'birth_date' => '1985-01-06',
            'deep_response' => 'คำทำนายเก่า',
        ]);
        foreach ($state as $k => $v) {
            $r->setConversationState($k, $v);
        }

        return $r->fresh();
    }

    public function test_บิลเก่าไม่มีรูป_วาดใหม่แล้วบันทึกเข้าบิล_ครั้งต่อไปใช้รูปเดิม(): void
    {
        $r = $this->reading();
        $url = LifeGraphService::ensureForReading($r);

        $this->assertNotNull($url);
        $this->assertSame($url, $r->fresh()->getConversationState('lifegraph_image_url'), 'ต้องบันทึกเข้าบิล');
        Storage::disk('public')->assertExists(substr((string) parse_url($url, PHP_URL_PATH), strlen('/storage/')));

        $this->assertSame($url, LifeGraphService::ensureForReading($r->fresh()), 'มีรูปแล้วต้องใช้รูปเดิม');
    }

    public function test_บิล99ใช้รูปจากบทสรุป_ไฟล์หายวาดใหม่_ไม่มีวันเกิดไม่มีรูป(): void
    {
        Storage::disk('public')->put('fortune/charts/lifegraph-finale.jpg', 'jpg');
        $finale = '/storage/fortune/charts/lifegraph-finale.jpg';
        $celtic = $this->reading(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS], ['celtic_finale_lifegraph_url' => $finale]);
        $this->assertSame($finale, LifeGraphService::ensureForReading($celtic));

        $gone = $this->reading([], ['lifegraph_image_url' => '/storage/fortune/charts/lifegraph-gone.jpg']);
        $fresh = LifeGraphService::ensureForReading($gone);
        $this->assertNotNull($fresh);
        $this->assertNotSame('/storage/fortune/charts/lifegraph-gone.jpg', $fresh, 'ไฟล์หายต้องวาดใหม่');

        $this->assertNull(LifeGraphService::ensureForReading($this->reading(['birth_date' => null])));
    }

    public function test_อ่านย้อนหลัง39แนบกราฟชีวิต(): void
    {
        $this->reading();
        $svc = new class(FortuneTellingSetting::getSettings()) extends FortuneConversationService
        {
            public function view(string $uid): array
            {
                return $this->handleViewLastReading($uid, 'อ่านย้อนหลัง');
            }
        };

        $res = $svc->view('psid-hist');
        $this->assertSame('view_reading_deep', $res['action']);
        $this->assertNotEmpty($res['lifegraph_image_url']);
    }
}
