<?php

namespace Database\Seeders;

use App\Models\LineSignupReward;
use App\Models\CouponTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class LineSignupRewardSeeder extends Seeder
{
    /**
     * คอลัมน์พิเศษที่ไม่ควรมีใน template table
     * แต่อาจจะมีอยู่ใน database (ก่อน migration)
     *
     * @var array
     */
    protected $extraColumns = [];

    /**
     * สร้างข้อมูลตัวอย่างสำหรับระบบรางวัลการสมัครสมาชิก
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed รางวัลการสมัครสมาชิก...');

        // เช็คว่าตารางมี columns พิเศษที่ไม่ควรมีหรือไม่
        $this->detectExtraColumns();

        if (!empty($this->extraColumns)) {
            $this->command->warn('⚠️  WARNING: ตาราง line_signup_rewards มี columns พิเศษที่ไม่ควรมี:');
            foreach ($this->extraColumns as $column) {
                $this->command->warn("   - {$column}");
            }
            $this->command->warn('');
            $this->command->info('ℹ️  Seeder จะเพิ่มค่า NULL ชั่วคราวสำหรับ columns เหล่านี้');
            $this->command->warn('💡 แนะนำให้ลบ columns พิเศษหลังจาก seed เสร็จ:');
            $this->command->warn('   1. Run: php artisan migrate --force');
            $this->command->warn('   2. หรือใช้: mysql < database/sql/cleanup_line_signup_rewards_table.sql');
            $this->command->warn('');
        }

        // สร้าง Coupon Template ก่อน
        $this->createCouponTemplates();

        // สร้างรางวัลสำหรับสมัครฟรี
        $this->createFreeSignupRewards();

        // สร้างรางวัลสำหรับสมัครด้วยแพคเกจ
        $this->createPackageRewards();

        $this->command->info('✅ Seed รางวัลการสมัครสมาชิกสำเร็จ!');
    }

    /**
     * ตรวจหา columns พิเศษที่ไม่ควรมีใน template table
     *
     * เช็คโดยเปรียบเทียบกับ schema ที่ควรจะเป็น (ตาม migration)
     *
     * @return void
     */
    protected function detectExtraColumns(): void
    {
        // รายการ columns ที่ควรมีตาม migration definition
        $expectedColumns = [
            'id',
            'name',
            'description',
            'signup_type',
            'package_ids',
            'reward_type',
            'amount',
            'coupon_code',
            'coupon_template_id',
            'benefit_data',
            'product_id',
            'icon',
            'badge_color',
            'display_order',
            'is_time_limited',
            'start_date',
            'end_date',
            'is_active',
            'is_stackable',
            'notify_user',
            'notification_message',
            'total_claimed',
            'max_claims',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        // ดึงรายการ columns ที่มีอยู่จริงในตาราง
        $actualColumns = Schema::getColumnListing('line_signup_rewards');

        // หา columns ที่มีอยู่แต่ไม่ควรมี
        $this->extraColumns = array_diff($actualColumns, $expectedColumns);
    }

    /**
     * เพิ่มค่า NULL ชั่วคราวสำหรับ columns พิเศษที่มีอยู่
     *
     * Columns พิเศษ (user_id, session_id, etc.) ไม่ควรมีในตาราง template
     * แต่ถ้ายังมีอยู่ (ก่อน migration) จะเพิ่มค่า NULL ชั่วคราว
     *
     * ⚠️ แนะนำให้ run migration เพื่อลบ columns พิเศษหลังจาก seed
     *
     * @param array $data
     * @return array
     */
    protected function addExtraColumnsIfExist(array $data): array
    {
        // ถ้าไม่มี columns พิเศษ ให้คืนค่าเดิม
        if (empty($this->extraColumns)) {
            return $data;
        }

        // เพิ่มค่า NULL สำหรับ columns พิเศษที่มีอยู่
        foreach ($this->extraColumns as $column) {
            // กำหนดค่า default ตามประเภท column
            $data[$column] = match($column) {
                'user_id', 'session_id' => null,
                'reward_name', 'reward_description' => null,
                'reward_amount' => null,
                'status' => 'template', // ค่าพิเศษเพื่อบอกว่าเป็น template
                'granted_at', 'claimed_at', 'expires_at' => null,
                default => null,
            };
        }

        return $data;
    }

    /**
     * สร้าง Coupon Templates
     */
    protected function createCouponTemplates(): void
    {
        // Template สำหรับฟรี
        CouponTemplate::updateOrCreate(
            ['code_prefix' => 'WELCOME10'],
            [
                'name' => 'ส่วนลด 10% สำหรับสมาชิกใหม่',
                'description' => 'คูปองต้อนรับสำหรับสมาชิกฟรี',
                'owner_type' => 'admin',
                'code_format' => 'random',
                'code_length' => 8,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_purchase' => 500,
                'max_discount' => 100,
                'usage_limit' => 1,
                'validity_days' => 30,
                'is_active' => true,
            ]
        );

        // Template สำหรับแพคเกจ
        CouponTemplate::updateOrCreate(
            ['code_prefix' => 'PREMIUM20'],
            [
                'name' => 'ส่วนลด 20% สำหรับสมาชิกพรีเมี่ยม',
                'description' => 'คูปองพิเศษสำหรับสมาชิกแพคเกจ',
                'owner_type' => 'admin',
                'code_format' => 'random',
                'code_length' => 10,
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_purchase' => 0,
                'max_discount' => 500,
                'usage_limit' => 3,
                'validity_days' => 60,
                'is_active' => true,
            ]
        );
    }

    /**
     * สร้างรางวัลสำหรับสมัครฟรี
     */
    protected function createFreeSignupRewards(): void
    {
        // 1. แต้ม Wallet
        LineSignupReward::updateOrCreate(
            [
                'name' => 'แต้มต้อนรับสมาชิกฟรี',
                'signup_type' => 'free',
                'reward_type' => 'wallet_points',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'รับแต้มฟรีทันทีที่สมัครสมาชิก',
                'amount' => 100,
                'icon' => 'fa-wallet',
                'badge_color' => '#10B981',
                'display_order' => 1,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '🎉 ยินดีต้อนรับ! คุณได้รับแต้ม 100 แต้ม',
            ])
        );

        // 2. เหรียญ TPIX
        LineSignupReward::updateOrCreate(
            [
                'name' => 'เหรียญ TPIX ฟรี',
                'signup_type' => 'free',
                'reward_type' => 'tpix_tokens',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'เหรียญ TPIX สำหรับเริ่มต้น',
                'amount' => 10,
                'icon' => 'fa-coins',
                'badge_color' => '#F59E0B',
                'display_order' => 2,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '🪙 คุณได้รับเหรียญ TPIX 10 เหรียญ',
            ])
        );

        // 3. คูปองส่วนลด
        $template = CouponTemplate::where('code_prefix', 'WELCOME10')->first();
        if ($template) {
            LineSignupReward::updateOrCreate(
                [
                    'name' => 'คูปองส่วนลด 10%',
                    'signup_type' => 'free',
                    'reward_type' => 'coupon',
                ],
                $this->addExtraColumnsIfExist([
                    'description' => 'ส่วนลด 10% สำหรับการซื้อครั้งแรก (ขั้นต่ำ 500 บาท)',
                    'coupon_template_id' => $template->id,
                    'icon' => 'fa-ticket-alt',
                    'badge_color' => '#EF4444',
                    'display_order' => 3,
                    'is_active' => true,
                    'is_stackable' => true,
                    'notify_user' => true,
                    'notification_message' => '🎫 คุณได้รับคูปองส่วนลด 10%',
                ])
            );
        }

        // 4. คะแนน XP
        LineSignupReward::updateOrCreate(
            [
                'name' => 'คะแนนประสบการณ์',
                'signup_type' => 'free',
                'reward_type' => 'experience_points',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'คะแนน XP สำหรับปลดล็อคความสามารถ',
                'amount' => 50,
                'icon' => 'fa-trophy',
                'badge_color' => '#8B5CF6',
                'display_order' => 4,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => false,
            ])
        );
    }

    /**
     * สร้างรางวัลสำหรับสมัครด้วยแพคเกจ
     */
    protected function createPackageRewards(): void
    {
        // 1. แต้ม Wallet (แพคเกจ)
        LineSignupReward::updateOrCreate(
            [
                'name' => 'แต้มโบนัสแพคเกจ',
                'signup_type' => 'package',
                'reward_type' => 'wallet_points',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'แต้มโบนัสพิเศษสำหรับสมาชิกแพคเกจ',
                'amount' => 500,
                'icon' => 'fa-wallet',
                'badge_color' => '#10B981',
                'display_order' => 10,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '💎 คุณได้รับแต้มโบนัส 500 แต้ม',
            ])
        );

        // 2. เหรียญ TPIX (แพคเกจ)
        LineSignupReward::updateOrCreate(
            [
                'name' => 'เหรียญ TPIX พรีเมี่ยม',
                'signup_type' => 'package',
                'reward_type' => 'tpix_tokens',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'เหรียญ TPIX จำนวนมากสำหรับสมาชิกแพคเกจ',
                'amount' => 50,
                'icon' => 'fa-coins',
                'badge_color' => '#F59E0B',
                'display_order' => 11,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '🪙 คุณได้รับเหรียญ TPIX 50 เหรียญ',
            ])
        );

        // 3. คูปองพรีเมี่ยม
        $template = CouponTemplate::where('code_prefix', 'PREMIUM20')->first();
        if ($template) {
            LineSignupReward::updateOrCreate(
                [
                    'name' => 'คูปองพรีเมี่ยม 20%',
                    'signup_type' => 'package',
                    'reward_type' => 'coupon',
                ],
                $this->addExtraColumnsIfExist([
                    'description' => 'ส่วนลด 20% ใช้ได้ 3 ครั้ง ไม่มีขั้นต่ำ',
                    'coupon_template_id' => $template->id,
                    'icon' => 'fa-ticket-alt',
                    'badge_color' => '#EF4444',
                    'display_order' => 12,
                    'is_active' => true,
                    'is_stackable' => true,
                    'notify_user' => true,
                    'notification_message' => '🎫 คุณได้รับคูปองพรีเมี่ยม 20%',
                ])
            );
        }

        // 4. คะแนน Rank
        LineSignupReward::updateOrCreate(
            [
                'name' => 'คะแนน Rank โบนัส',
                'signup_type' => 'package',
                'reward_type' => 'rank_points',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'คะแนนช่วยให้เลื่อนระดับเร็วขึ้น',
                'amount' => 10,
                'icon' => 'fa-star',
                'badge_color' => '#FBBF24',
                'display_order' => 13,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '⭐ คุณได้รับคะแนน Rank +10',
            ])
        );

        // 5. คะแนน XP (แพคเกจ)
        LineSignupReward::updateOrCreate(
            [
                'name' => 'คะแนนประสบการณ์พิเศษ',
                'signup_type' => 'package',
                'reward_type' => 'experience_points',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'คะแนน XP เพิ่มเติมสำหรับสมาชิกแพคเกจ',
                'amount' => 200,
                'icon' => 'fa-trophy',
                'badge_color' => '#8B5CF6',
                'display_order' => 14,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => false,
            ])
        );

        // 6. ลูกทีมฟรี (สำหรับแพคเกจระดับสูง)
        LineSignupReward::updateOrCreate(
            [
                'name' => 'ดาวน์ไลน์ฟรี',
                'signup_type' => 'package',
                'reward_type' => 'free_downlines',
            ],
            $this->addExtraColumnsIfExist([
                'description' => 'รับดาวน์ไลน์ฟรีจากระบบ (เฉพาะแพคเกจระดับสูง)',
                'amount' => 3,
                'package_ids' => [4, 5], // Gold, Diamond (ตัวอย่าง)
                'icon' => 'fa-users',
                'badge_color' => '#3B82F6',
                'display_order' => 15,
                'is_active' => true,
                'is_stackable' => false,
                'notify_user' => true,
                'notification_message' => '👥 คุณได้รับสิทธิ์ดาวน์ไลน์ฟรี 3 คน',
            ])
        );
    }
}
