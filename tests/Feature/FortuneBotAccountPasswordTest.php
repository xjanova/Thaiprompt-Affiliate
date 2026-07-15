<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\MlmPlan;
use App\Models\User;
use App\Services\FortuneAffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 🔒 ทดสอบว่าบัญชีที่บอทสมัครให้อัตโนมัติ ไม่ใช้รหัสผ่านคงที่
 *
 * ปัญหาเดิม (2026-07-16): ทุกบัญชีถูกตั้งรหัส '12345678' เหมือนกันหมด (664 ใบบน prod)
 * + อีเมลเดาได้จากสูตร line_{uid}@thaiprompt.local
 * + LoginController ไม่บล็อกอีเมล .local
 * → ใครรู้ LINE UID ของลูกค้า ล็อกอินเป็นคนนั้นได้ พร้อมเข้าถึงวอลเลต
 *
 * @group security
 */
class FortuneBotAccountPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        FortuneTellingSetting::create([
            'facebook_app_id' => 'test-'.uniqid(),
            'facebook_page_id' => 'test-'.uniqid(),
            'is_enabled' => true,
        ]);
    }

    /**
     * บัญชีที่บอทสร้าง ต้องไม่ใช้รหัส 12345678 อีก
     */
    public function test_bot_created_account_does_not_use_the_hardcoded_password(): void
    {
        $user = $this->createViaBot('U'.str_repeat('a', 32));

        $this->assertFalse(
            Hash::check('12345678', $user->password),
            'บัญชีที่บอทสร้างต้องไม่ใช้รหัส 12345678 — ถ้าเทสต์นี้แดง แปลว่าช่องโหว่กลับมาแล้ว'
        );
    }

    /**
     * รหัสของแต่ละบัญชีต้องไม่ซ้ำกัน (สุ่มจริง ไม่ใช่ค่าคงที่ตัวใหม่)
     */
    public function test_each_bot_account_gets_a_different_password(): void
    {
        $a = $this->createViaBot('U'.str_repeat('b', 32));
        $b = $this->createViaBot('U'.str_repeat('c', 32));

        $this->assertNotSame(
            $a->password,
            $b->password,
            'รหัสต้องสุ่มรายบัญชี — hash ซ้ำกันแปลว่ายังเป็นค่าคงที่'
        );
    }

    /**
     * ลูกค้าต้องยังเข้าระบบผ่าน LINE ได้ (line_user_id ถูกบันทึก)
     * — ถ้าสุ่มรหัสแล้วไม่มี OAuth ลูกค้าจะเข้าไม่ได้ตลอดกาล
     */
    public function test_bot_account_keeps_an_oauth_route_in(): void
    {
        $uid = 'U'.str_repeat('d', 32);
        $user = $this->createViaBot($uid);

        $this->assertSame($uid, $user->line_user_id, 'ต้องเก็บ line_user_id ไว้ ไม่งั้นลูกค้าเข้าระบบไม่ได้เลย');
    }

    /**
     * คำสั่งสุ่มรหัสบัญชีเก่า: ต้องแตะเฉพาะใบที่ยังเป็น 12345678
     */
    public function test_rotate_command_only_touches_weak_passwords(): void
    {
        // ใบที่ยังอ่อน + มี OAuth → ต้องโดนสุ่มใหม่
        $weak = User::create([
            'name' => 'Weak',
            'email' => 'line_WEAK@thaiprompt.local',
            'password' => Hash::make('12345678'),
            'line_user_id' => 'U_WEAK',
        ]);

        // ใบที่ลูกค้าเปลี่ยนรหัสเองแล้ว → ห้ามแตะ
        $strong = User::create([
            'name' => 'Strong',
            'email' => 'line_STRONG@thaiprompt.local',
            'password' => Hash::make('a-real-password-set-by-the-user'),
            'line_user_id' => 'U_STRONG',
        ]);
        $strongHashBefore = $strong->password;

        $this->artisan('fortune:rotate-bot-passwords')->assertExitCode(0);

        $this->assertFalse(
            Hash::check('12345678', $weak->fresh()->password),
            'ใบที่อ่อนต้องถูกสุ่มใหม่'
        );
        $this->assertSame(
            $strongHashBefore,
            $strong->fresh()->password,
            'ห้ามแตะบัญชีที่ลูกค้าตั้งรหัสเองแล้ว'
        );
    }

    /**
     * บัญชีที่ไม่มี OAuth เลย ต้องไม่ถูกสุ่มรหัส (ไม่งั้นเจ้าของเข้าไม่ได้ถาวร)
     */
    public function test_rotate_command_skips_accounts_with_no_oauth_route(): void
    {
        $orphan = User::create([
            'name' => 'Orphan',
            'email' => 'fb_ORPHAN@thaiprompt.local',
            'password' => Hash::make('12345678'),
        ]);
        $before = $orphan->password;

        $this->artisan('fortune:rotate-bot-passwords')->assertExitCode(0);

        $this->assertSame(
            $before,
            $orphan->fresh()->password,
            'ไม่มี OAuth = สุ่มรหัสแล้วเข้าไม่ได้ตลอดกาล → ต้องข้าม'
        );
    }

    /**
     * เรียก createUserFromPlatform ผ่าน reflection (เป็น protected)
     */
    private function createViaBot(string $lineUserId): User
    {
        $svc = new FortuneAffiliateService(FortuneTellingSetting::getSettings());
        $m = new \ReflectionMethod($svc, 'createUserFromPlatform');
        $m->setAccessible(true);

        return $m->invoke($svc, $lineUserId, 'line', ['name' => 'ลูกค้าทดสอบ'], null);
    }
}
