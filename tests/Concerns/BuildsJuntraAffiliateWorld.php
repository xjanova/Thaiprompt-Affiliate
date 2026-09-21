<?php

namespace Tests\Concerns;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

/**
 * ผังแม่หมอพร้อมใช้สำหรับเทสต์เส้น /juntra/server/affiliate/* — ใช้คู่กับ RefreshDatabase
 *
 * user id 1 = Super Admin = ผู้แนะนำเริ่มต้น (ROOT0001) · อัตราบิลจันทรา 10% / 5%
 * ทุกคำขอแนบ token ของเซิร์ฟเวอร์จันทราแล้ว (ใช้ withoutToken() เมื่ออยากทดสอบว่าไม่มี token)
 */
trait BuildsJuntraAffiliateWorld
{
    protected MlmPlan $plan;

    protected MlmMember $rootMember;

    protected function buildJuntraAffiliateWorld(): void
    {
        // ปิดระบบรักษายอด — ทุกคนที่ status=active ถือว่า active ('0' ไม่ใช่ 'false')
        MlmGlobalSetting::updateOrCreate(
            ['key' => 'volume_retention_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'retention']
        );
        Cache::flush();

        $this->plan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        $admin = User::factory()->create(['id' => 1, 'name' => 'แอดมิน']);
        $this->rootMember = $this->member($admin, null, 'ROOT0001');

        FortuneTellingSetting::create([
            'facebook_app_id' => 'test-app-'.uniqid(),
            'facebook_page_id' => 'test-page-'.uniqid(),
            'is_enabled' => true,
            'deep_reading_price' => 39,
            'fortune_affiliate_enabled' => true,
            'fortune_commission_mode' => 'static',
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 10,
            'fortune_level2_enabled' => true,
            'fortune_level2_commission_type' => 'fixed',
            'fortune_level2_commission_amount' => 5,
            'fortune_juntra_l1_percent' => 10,
            'fortune_juntra_l2_enabled' => true,
            'fortune_juntra_l2_percent' => 5,
        ]);
        FortuneTellingSetting::clearSettingsCache();

        // .env.testing มี APP_KEY ความยาวผิด — guard ของ Passport ต้องใช้ encrypter จริง
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);
        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');

        Passport::actingAsClient(
            app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users'),
            [],
            'api-oauth'
        );
        // ประตู 'juntra.server' ตอบ 401 ทันทีเมื่อไม่มี Bearer
        $this->withToken('server-token');
    }

    protected function bill(int $billId, int $userRef, float $amount, ?string $referral = null)
    {
        return $this->postJson('/api/v1/juntra/server/affiliate/bills', array_filter([
            'bill_id' => $billId,
            'user_ref' => $userRef,
            'name' => "ลูกค้า {$userRef}",
            'amount' => $amount,
            'product' => 'tarot_celtic',
            'paid_at' => now()->toIso8601String(),
            'referral_code' => $referral,
        ], fn ($v) => $v !== null));
    }

    protected function readingId(int $billId): int
    {
        return (int) FortuneReading::where('bill_reference', 'JW-'.$billId)->value('id');
    }

    /** @return array{0: User, 1: MlmMember} */
    protected function activeMember(string $code, ?MlmMember $sponsor = null): array
    {
        $user = User::factory()->create();

        return [$user, $this->member($user, $sponsor ?? $this->rootMember, $code)];
    }

    protected function member(User $user, ?MlmMember $sponsor, string $code): MlmMember
    {
        return MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => $code,
            'unilevel_sponsor_id' => $sponsor?->id,
            'unilevel_level' => $sponsor ? ((int) $sponsor->unilevel_level + 1) : 0,
            'unilevel_path' => $sponsor ? ($sponsor->unilevel_path.'/'.$sponsor->id) : '',
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now(),
        ]);
    }
}
