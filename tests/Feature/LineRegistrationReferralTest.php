<?php

namespace Tests\Feature;

use App\Models\LineRegistrationSession;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 🔗 (2026-11-11) ลิงก์เชิญ /line/registration?ref=<รหัส> ที่รหัสไม่มีในระบบ ต้องไม่ขึ้นหน้า 500
 *
 * prod v4.0.674: รหัสที่ไม่ตรง member_code ไหล fallback ไปค้น affiliates.referral_code
 *   แต่ prod ไม่มีตาราง affiliates แล้ว → SQLSTATE 1146 → หน้า 500
 *   (schema ที่ใช้ทดสอบก็ไม่มีตารางนี้เหมือน prod เลยจำลองได้ตรง)
 *
 * ที่ต้องเป็น: รหัสผิด → สมัครต่อได้ใต้ sponsor default + บอกเป็นภาษาไทยว่าไม่พบรหัสเชิญ
 *              รหัสถูก → ได้ sponsor คนนั้นเหมือนเดิม
 */
class LineRegistrationReferralTest extends TestCase
{
    use RefreshDatabase;

    /** ข้อความที่หน้าเว็บใช้บอกว่ารหัสเชิญใช้ไม่ได้ */
    private const NOT_FOUND_TEXT = 'ไม่พบรหัสเชิญนี้ในระบบ';

    private MlmPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutVite();

        $this->plan = MlmPlan::create([
            'name' => 'Hybrid', 'name_th' => 'ไฮบริด', 'slug' => 'hybrid-'.Str::random(5),
            'type' => 'hybrid', 'is_active' => true, 'is_default' => true,
        ]);
    }

    /** สร้างสมาชิก MLM ที่ active พร้อม user */
    private function makeMember(string $code, string $name, array $userAttrs = []): MlmMember
    {
        $user = User::factory()->create(array_merge(['name' => $name], $userAttrs));

        return MlmMember::create([
            'user_id' => $user->id, 'mlm_plan_id' => $this->plan->id, 'member_code' => $code,
            'status' => 'active', 'is_qualified' => true, 'joined_at' => now()->subMonth(),
        ]);
    }

    /** Super Admin ที่ createSession ใช้เป็น sponsor default (หาจากอีเมลนี้ก่อน) */
    private function makeAdmin(): MlmMember
    {
        return $this->makeMember('ADMIN-0001', 'ผู้ดูแลระบบ', ['email' => 'superadmin@thaiprompt.com']);
    }

    public function test_unknown_ref_code_shows_page_with_thai_notice_instead_of_500(): void
    {
        // sponsor default (Super Admin) ที่ระบบใช้แทนเมื่อหาเจ้าของรหัสไม่เจอ
        $admin = $this->makeAdmin();
        $this->assertFalse(Schema::hasTable('affiliates'), 'ต้องจำลองแบบ prod ที่ไม่มีตาราง affiliates');

        $response = $this->get('/line/registration?ref=NOPE-9999');

        $response->assertOk();
        $response->assertSee(self::NOT_FOUND_TEXT);

        $session = LineRegistrationSession::latest('id')->first();
        $this->assertSame('NOPE-9999', $session->referral_code);
        $this->assertSame($admin->user_id, $session->sponsor_user_id);
        $this->assertSame($admin->id, $session->sponsor_mlm_member_id);
        $this->assertTrue($session->metadata['referral_not_found']);
    }

    public function test_malformed_ref_code_is_dropped_and_page_still_loads(): void
    {
        $this->makeAdmin();
        $junk = str_repeat('ก', 300)."'; DROP TABLE users; --";

        $response = $this->get('/line/registration?ref='.urlencode($junk));

        $response->assertOk();
        $response->assertSee(self::NOT_FOUND_TEXT);
        // รหัสขยะไม่ถูกเก็บลง DB
        $this->assertNull(LineRegistrationSession::latest('id')->first()->referral_code);
    }

    public function test_ref_sent_as_array_does_not_crash(): void
    {
        $this->makeAdmin();

        $this->get('/line/registration?ref[]=ABC')->assertOk();
    }

    public function test_valid_ref_code_still_links_to_that_sponsor(): void
    {
        $this->makeAdmin();
        $sponsor = $this->makeMember('MLMABCD1234', 'คุณผู้แนะนำ');

        $response = $this->get('/line/registration?ref=MLMABCD1234');

        $response->assertOk();
        $response->assertSee('คุณผู้แนะนำ');
        $response->assertDontSee(self::NOT_FOUND_TEXT);

        $session = LineRegistrationSession::latest('id')->first();
        $this->assertSame('MLMABCD1234', $session->referral_code);
        $this->assertSame($sponsor->user_id, $session->sponsor_user_id);
        $this->assertSame($sponsor->id, $session->sponsor_mlm_member_id);
        $this->assertNull($session->metadata);
    }

    public function test_no_ref_code_shows_no_notice(): void
    {
        $this->makeAdmin();

        $response = $this->get('/line/registration');

        $response->assertOk();
        $response->assertDontSee(self::NOT_FOUND_TEXT);
    }
}
