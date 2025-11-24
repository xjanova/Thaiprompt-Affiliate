<?php

namespace Database\Seeders;

use App\Models\LineSignupReward;
use App\Models\CouponTemplate;
use Illuminate\Database\Schema\Blueprint;
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

        // เช็คและลบ columns พิเศษอัตโนมัติ (ถ้ามี)
        $this->cleanupExtraColumns();

        // สร้าง Coupon Template ก่อน
        $this->createCouponTemplates();

        // สร้างรางวัลสำหรับสมัครฟรี
        $this->createFreeSignupRewards();

        // สร้างรางวัลสำหรับสมัครด้วยแพคเกจ
        $this->createPackageRewards();

        $this->command->info('✅ Seed รางวัลการสมัครสมาชิกสำเร็จ!');
    }

    /**
     * เช็คและลบ columns พิเศษที่ไม่ควรมีอัตโนมัติ
     *
     * Columns พิเศษควรอยู่ใน `line_signup_reward_claims` ไม่ใช่ `line_signup_rewards`
     * Template table ควรเก็บเฉพาณข้อมูลการกำหนดรางวัล ไม่ใช่ข้อมูลการรับรางวัล
     *
     * @return void
     */
    protected function cleanupExtraColumns(): void
    {
        // ตรวจหา columns พิเศษ
        $this->detectExtraColumns();

        // ถ้าไม่มี columns พิเศษ ไม่ต้องทำอะไร
        if (empty($this->extraColumns)) {
            return;
        }

        $this->command->warn('⚠️  พบ columns พิเศษที่ไม่ควรมีใน template table:');
        foreach ($this->extraColumns as $column) {
            $this->command->warn("   - {$column}");
        }
        $this->command->info('');
        $this->command->info('🔧 กำลังลบ columns พิเศษอัตโนมัติ...');

        // ลบ columns พิเศษทีละตัว
        Schema::table('line_signup_rewards', function (Blueprint $table) {
            // 1. ลบ indexes ที่เกี่ยวข้องก่อน
            $this->dropRelatedIndexes($table);

            // 2. ลบ foreign keys
            $this->dropRelatedForeignKeys($table);
        });

        // 3. ลบ columns
        foreach ($this->extraColumns as $column) {
            if (Schema::hasColumn('line_signup_rewards', $column)) {
                Schema::table('line_signup_rewards', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
                $this->command->info("   ✓ ลบ column: {$column}");
            }
        }

        $this->command->info('✅ ลบ columns พิเศษเรียบร้อย!');
        $this->command->info('');

        // รีเซ็ต extraColumns หลังจากลบแล้ว
        $this->extraColumns = [];
    }

    /**
     * ลบ indexes ที่เกี่ยวข้องกับ columns พิเศษ
     *
     * @param Blueprint $table
     * @return void
     */
    protected function dropRelatedIndexes(Blueprint $table): void
    {
        $indexesToDrop = [
            'idx_user_status',
            'idx_session_status',
            'line_signup_rewards_user_id_index',
            'line_signup_rewards_session_id_index',
            'line_signup_rewards_status_index',
        ];

        foreach ($indexesToDrop as $indexName) {
            try {
                $table->dropIndex($indexName);
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }
        }

        // ลบ single column indexes
        foreach ($this->extraColumns as $column) {
            try {
                $table->dropIndex([$column]);
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }
        }
    }

    /**
     * ลบ foreign keys ที่เกี่ยวข้องกับ columns พิเศษ
     *
     * @param Blueprint $table
     * @return void
     */
    protected function dropRelatedForeignKeys(Blueprint $table): void
    {
        $foreignKeys = [
            'line_signup_rewards_user_id_foreign',
            'line_signup_rewards_session_id_foreign',
        ];

        foreach ($foreignKeys as $fkName) {
            try {
                $table->dropForeign($fkName);
            } catch (\Exception $e) {
                // Ignore if FK doesn't exist
            }
        }

        // ลบ foreign keys โดยใช้ column names
        foreach (['user_id', 'session_id'] as $column) {
            if (in_array($column, $this->extraColumns)) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
            }
        }
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
            [
                'description' => 'รับแต้มฟรีทันทีที่สมัครสมาชิก',
                'amount' => 100,
                'icon' => 'fa-wallet',
                'badge_color' => '#10B981',
                'display_order' => 1,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '🎉 ยินดีต้อนรับ! คุณได้รับแต้ม 100 แต้ม',
            ]
        );

        // 2. เหรียญ TPIX
        LineSignupReward::updateOrCreate(
            [
                'name' => 'เหรียญ TPIX ฟรี',
                'signup_type' => 'free',
                'reward_type' => 'tpix_tokens',
            ],
            [
                'description' => 'เหรียญ TPIX สำหรับเริ่มต้น',
                'amount' => 10,
                'icon' => 'fa-coins',
                'badge_color' => '#F59E0B',
                'display_order' => 2,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '🪙 คุณได้รับเหรียญ TPIX 10 เหรียญ',
            ]
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
                [
                    'description' => 'ส่วนลด 10% สำหรับการซื้อครั้งแรก (ขั้นต่ำ 500 บาท)',
                    'coupon_template_id' => $template->id,
                    'icon' => 'fa-ticket-alt',
                    'badge_color' => '#EF4444',
                    'display_order' => 3,
                    'is_active' => true,
                    'is_stackable' => true,
                    'notify_user' => true,
                    'notification_message' => '🎫 คุณได้รับคูปองส่วนลด 10%',
                ]
            );
        }

        // 4. คะแนน XP
        LineSignupReward::updateOrCreate(
            [
                'name' => 'คะแนนประสบการณ์',
                'signup_type' => 'free',
                'reward_type' => 'experience_points',
            ],
            [
                'description' => 'คะแนน XP สำหรับปลดล็อคความสามารถ',
                'amount' => 50,
                'icon' => 'fa-trophy',
                'badge_color' => '#8B5CF6',
                'display_order' => 4,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => false,
            ]
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
            [
                'description' => 'แต้มโบนัสพิเศษสำหรับสมาชิกแพคเกจ',
                'amount' => 500,
                'icon' => 'fa-wallet',
                'badge_color' => '#10B981',
                'display_order' => 10,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '💎 คุณได้รับแต้มโบนัส 500 แต้ม',
            ]
        );

        // 2. เหรียญ TPIX (แพคเกจ)
        LineSignupReward::updateOrCreate(
            [
                'name' => 'เหรียญ TPIX พรีเมี่ยม',
                'signup_type' => 'package',
                'reward_type' => 'tpix_tokens',
            ],
            [
                'description' => 'เหรียญ TPIX จำนวนมากสำหรับสมาชิกแพคเกจ',
                'amount' => 50,
                'icon' => 'fa-coins',
                'badge_color' => '#F59E0B',
                'display_order' => 11,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '🪙 คุณได้รับเหรียญ TPIX 50 เหรียญ',
            ]
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
                [
                    'description' => 'ส่วนลด 20% ใช้ได้ 3 ครั้ง ไม่มีขั้นต่ำ',
                    'coupon_template_id' => $template->id,
                    'icon' => 'fa-ticket-alt',
                    'badge_color' => '#EF4444',
                    'display_order' => 12,
                    'is_active' => true,
                    'is_stackable' => true,
                    'notify_user' => true,
                    'notification_message' => '🎫 คุณได้รับคูปองพรีเมี่ยม 20%',
                ]
            );
        }

        // 4. คะแนน Rank
        LineSignupReward::updateOrCreate(
            [
                'name' => 'คะแนน Rank โบนัส',
                'signup_type' => 'package',
                'reward_type' => 'rank_points',
            ],
            [
                'description' => 'คะแนนช่วยให้เลื่อนระดับเร็วขึ้น',
                'amount' => 10,
                'icon' => 'fa-star',
                'badge_color' => '#FBBF24',
                'display_order' => 13,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => true,
                'notification_message' => '⭐ คุณได้รับคะแนน Rank +10',
            ]
        );

        // 5. คะแนน XP (แพคเกจ)
        LineSignupReward::updateOrCreate(
            [
                'name' => 'คะแนนประสบการณ์พิเศษ',
                'signup_type' => 'package',
                'reward_type' => 'experience_points',
            ],
            [
                'description' => 'คะแนน XP เพิ่มเติมสำหรับสมาชิกแพคเกจ',
                'amount' => 200,
                'icon' => 'fa-trophy',
                'badge_color' => '#8B5CF6',
                'display_order' => 14,
                'is_active' => true,
                'is_stackable' => true,
                'notify_user' => false,
            ]
        );

        // 6. ลูกทีมฟรี (สำหรับแพคเกจระดับสูง)
        LineSignupReward::updateOrCreate(
            [
                'name' => 'ดาวน์ไลน์ฟรี',
                'signup_type' => 'package',
                'reward_type' => 'free_downlines',
            ],
            [
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
            ]
        );
    }
}
