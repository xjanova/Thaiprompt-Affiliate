<?php

namespace Tests\Feature\User;

use App\Models\TwoFactorSetting;
use App\Models\TwoFactorUserSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * หน้า 2FA ฝั่งสมาชิกในธีมโนวา (ตั้งค่า · ยืนยัน · รหัสกู้คืน)
 *
 * ล็อกสิ่งที่หน้าต้องบอกผู้ใช้ให้ถูก: สถานะ, อุปกรณ์ที่เชื่อถือ, รายการที่กำลังยืนยัน, ตัวเลือกจำอุปกรณ์ตามที่แอดมินตั้ง
 */
class TwoFactorNovaPagesTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->create(['role' => 'user', 'is_super_admin' => false]);
    }

    /** URL ที่ฝังในสคริปต์ผ่าน @js (json_encode escape "/" เป็น "\/") */
    private function inScript(string $url): string
    {
        return trim(json_encode($url), '"');
    }

    private function adminSettings(bool $remember, int $days = 30): void
    {
        TwoFactorSetting::query()->create(['allow_remember_device' => $remember, 'remember_device_days' => $days]);
        Cache::forget('two_factor_settings');
    }

    public function test_setup_page_walks_a_new_member_through_enabling(): void
    {
        $this->actingAs($this->member())->get(route('user.two-factor.setup'))
            ->assertOk()
            ->assertSee('nvm-body', false)
            ->assertSee('เปิดใช้ใน 3 ขั้น')
            ->assertSee($this->inScript(route('user.two-factor.generate-authenticator')), false)
            ->assertSee(route('user.two-factor.enable'), false)
            ->assertDontSee('ปิด 2FA</button>', false);
    }

    public function test_setup_page_lists_trusted_devices_and_warns_when_codes_run_low(): void
    {
        $user = $this->member();
        $settings = TwoFactorUserSetting::getOrCreateForUser($user);
        $settings->enabled = true;
        $settings->recovery_codes = [bcrypt('AAAA-BBBB'), bcrypt('CCCC-DDDD')];
        // ชื่ออุปกรณ์มาจาก user agent — ต้องถูก escape
        $settings->trusted_devices = ['fp1' => ['name' => '<b>iPhone</b>', 'added_at' => now()->subDay()->toDateTimeString(), 'last_used_at' => now()->toDateTimeString(), 'ip' => '10.0.0.1']];
        $settings->save();

        $res = $this->actingAs($user)->get(route('user.two-factor.setup'))->assertOk();

        $res->assertSee('รหัสกู้คืนเหลือแค่ 2 รหัส')
            ->assertSee(route('user.two-factor.regenerate-recovery-codes'), false)
            ->assertSee(route('user.two-factor.remove-all-trusted-devices'), false)
            ->assertSee(route('user.two-factor.disable'), false)
            ->assertDontSee('<b>iPhone</b>', false);
        $this->assertStringContainsString('10.0.0.1', $res->getContent());
    }

    public function test_verify_page_names_the_action_being_confirmed(): void
    {
        $this->adminSettings(true, 14);

        $this->actingAs($this->member())
            ->get(route('user.two-factor.verify', ['action' => 'withdrawal', 'redirect' => '/user/wallet']))
            ->assertOk()
            ->assertSee('รายการ: ถอนเงิน')
            ->assertSee('จำอุปกรณ์นี้ 14 วัน')
            ->assertSee($this->inScript(route('user.two-factor.verify-code')), false)
            ->assertSee($this->inScript(route('user.two-factor.verify-recovery-code')), false);
    }

    public function test_verify_page_hides_remember_device_when_admin_turned_it_off(): void
    {
        $this->adminSettings(false);

        $this->actingAs($this->member())->get(route('user.two-factor.verify'))
            ->assertOk()
            ->assertDontSee('จำอุปกรณ์นี้');
    }

    public function test_verify_page_never_embeds_an_unsafe_redirect(): void
    {
        $res = $this->actingAs($this->member())
            ->get(route('user.two-factor.verify', ['redirect' => 'javascript:alert(1)']))
            ->assertOk();

        $this->assertStringNotContainsString('javascript:alert', $res->getContent());
    }

    public function test_recovery_codes_page_shows_fresh_codes_with_save_actions(): void
    {
        $user = $this->member();
        TwoFactorUserSetting::getOrCreateForUser($user)->forceFill(['enabled' => true])->save();

        $this->actingAs($user)->withSession(['recovery_codes' => ['AB12-CD34', 'EF56-GH78']])
            ->get(route('user.two-factor.recovery-codes'))
            ->assertOk()
            ->assertSee('AB12-CD34')
            ->assertSee('ดาวน์โหลด .txt')
            ->assertSee('ฉันเก็บรหัสทั้ง 2 รหัสไว้ในที่ปลอดภัยแล้ว');
    }
}
