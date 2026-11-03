<?php

namespace Tests\Feature\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * ตัวช่วยสร้างผัง G → S → C (ผู้แนะนำชั้นหลาน → ผู้แนะนำตรง → ลูกค้า) สำหรับเทสต์ค่าแนะนำดูดวง
 *
 * ตั้งค่าแบบเดียวกับ prod: โหมดแยกแพคเกจ Celtic 99 = 10/2, Deep 39 = 5 (ไม่มีชั้นหลาน)
 * แถวตั้งค่าใช้ "แถวแรก" ของตาราง เพราะโค้ดจริงหลายจุดอ่าน FortuneTellingSetting::getSettings()/first()
 */
trait BuildsFortuneCommissionChain
{
    protected FortuneTellingSetting $settings;

    protected MlmPlan $mlmPlan;

    protected function setUpFortuneChain(array $settingOverrides = []): void
    {
        // ปิดระบบรักษายอด — ค่าแนะนำดูดวงใช้กฎ "เคยมีบิลที่ชำระแล้ว" อย่างเดียว
        MlmGlobalSetting::updateOrCreate(
            ['key' => 'volume_retention_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'retention']
        );
        Cache::flush();

        $this->mlmPlan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->settings = FortuneTellingSetting::getGlobalSettings();
        $this->settings->forceFill(array_merge([
            'is_enabled' => true,
            'reading_price' => 29,
            'deep_reading_price' => 39,
            'celtic_cross_price' => 99,
            'fortune_affiliate_enabled' => true,
            'fortune_commission_mode' => 'static',
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 10,
            'fortune_level2_enabled' => true,
            'fortune_level2_commission_type' => 'fixed',
            'fortune_level2_commission_amount' => 1,
            'fortune_pkg_rates_enabled' => true,
            'fortune_deep_l1_amount' => 5,
            'fortune_deep_l2_enabled' => false,
            'fortune_deep_l2_amount' => 0,
            'fortune_celtic_l1_amount' => 10,
            'fortune_celtic_l2_enabled' => true,
            'fortune_celtic_l2_amount' => 2,
            'fortune_central_fallback_enabled' => false,
        ], $settingOverrides))->save();
        FortuneTellingSetting::clearSettingsCache();
        $this->settings = FortuneTellingSetting::getGlobalSettings();
    }

    /**
     * สร้าง User + MlmMember + Wallet ที่มีสิทธิ์รับค่าแนะนำ (มีบิลเก่าที่จ่ายแล้วหนึ่งใบ)
     *
     * @return array{0: User, 1: MlmMember}
     */
    protected function makeChainMember(?MlmMember $sponsor = null, array $userAttrs = [], bool $paidBefore = true): array
    {
        $user = User::factory()->create($userAttrs);

        $member = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->mlmPlan->id,
            'member_code' => 'TM'.strtoupper(Str::random(7)),
            'unilevel_sponsor_id' => $sponsor?->id,
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subDays(30),
        ]);

        Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        if ($paidBefore) {
            $this->makeBill($user->id, FortuneReading::READING_TYPE_DEEP, 39, [
                'conversation_status' => FortuneReading::STATUS_COMPLETED,
            ])->forceFill(['paid_at' => now()->subDays(400)])->save();
        }

        return [$user, $member];
    }

    /**
     * บิลดูดวงที่ชำระแล้ว
     */
    protected function makeBill(int $userId, string $type, float $amount, array $extra = []): FortuneReading
    {
        return FortuneReading::create(array_merge([
            'user_id' => $userId,
            'facebook_user_id' => 'test_fb_'.uniqid(),
            'reading_type' => $type,
            'questions' => ['ทดสอบ #'.uniqid()],
            'ai_provider' => 'test',
            'is_paid' => true,
            'amount_paid' => $amount,
            'paid_at' => now(),
            'conversation_status' => FortuneReading::STATUS_PAID,
            'platform' => 'test',
        ], $extra));
    }

    protected function balanceOf(int $userId): float
    {
        return (float) Wallet::where('user_id', $userId)->value('balance');
    }
}
