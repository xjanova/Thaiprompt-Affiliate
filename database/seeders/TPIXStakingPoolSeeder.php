<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TPIXToken;
use App\Models\User;
use Carbon\Carbon;

/**
 * TPIX Staking Pool Seeder
 *
 * สร้างข้อมูล Staking Pools เริ่มต้นสำหรับระบบ TPIX Staking
 */
class TPIXStakingPoolSeeder extends Seeder
{
    /**
     * สร้างข้อมูล Staking Pools เริ่มต้น
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล TPIX Staking Pools...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (DB::table('tpix_staking_pools')->exists()) {
            $this->command->info('⚠️  ข้อมูล Staking Pools มีอยู่แล้ว ข้าม...');
            return;
        }

        // ดึง TPIX Token (สมมติว่า TPIX เป็น token แรก หรือมี symbol เป็น 'TPIX')
        $tpixToken = TPIXToken::where('symbol', 'TPIX')->first();

        if (!$tpixToken) {
            // หากไม่มี TPIX Token ให้สร้างตัวอย่างแบบง่าย
            $this->command->warn('⚠️  ไม่พบ TPIX Token ในระบบ กำลังสร้าง...');

            $adminUser = User::where('is_admin', true)->first() ?? User::first();

            if (!$adminUser) {
                $this->command->error('❌ ไม่พบผู้ใช้ในระบบ กรุณา seed ผู้ใช้ก่อน');
                return;
            }

            $tpixToken = TPIXToken::create([
                'creator_id' => $adminUser->id,
                'name' => 'Thaiprompt Index',
                'symbol' => 'TPIX',
                'description' => 'โทเค็นหลักของระบบ Thaiprompt Affiliate',
                'contract_address' => '0x0000000000000000000000000000000000000000',
                'decimals' => 18,
                'total_supply' => '7000000000',
                'initial_supply' => '7000000000',
                'is_mintable' => false,
                'is_burnable' => true,
                'is_pausable' => false,
                'is_freezable' => false,
                'has_max_supply' => true,
                'max_supply' => '7000000000',
                'initial_price_tpix' => '1',
                'current_price_tpix' => '20',
                'status' => 'active',
                'is_verified' => true,
                'is_listed' => true,
                'is_featured' => true,
            ]);

            $this->command->info('✅ สร้าง TPIX Token สำเร็จ');
        }

        // สร้าง creator (admin user)
        $creator = User::where('is_admin', true)->first() ?? User::first();

        if (!$creator) {
            $this->command->error('❌ ไม่พบผู้ใช้ในระบบ กรุณา seed ผู้ใช้ก่อน');
            return;
        }

        $now = Carbon::now();

        // สร้าง Staking Pools หลายระดับ
        $pools = [
            [
                'token_id' => $tpixToken->id,
                'creator_id' => $creator->id,
                'name' => 'TPIX Flexible - รายวัน',
                'description' => 'Staking แบบยืดหยุ่น ถอนได้ทุกเมื่อ ผลตอบแทน 5% ต่อปี',
                'contract_address' => null,
                'apy' => 5.00,
                'lock_period_days' => 0, // ไม่มีการล็อค
                'min_stake_amount' => '10.00000000',
                'max_stake_amount' => '100000.00000000',
                'pool_cap' => '1000000.00000000',
                'total_staked' => '0.00000000',
                'max_stakers' => null,
                'current_stakers' => 0,
                'reward_token_type' => 'same',
                'reward_token_id' => $tpixToken->id,
                'total_rewards_distributed' => '0.00000000',
                'pending_rewards' => '0.00000000',
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'token_id' => $tpixToken->id,
                'creator_id' => $creator->id,
                'name' => 'TPIX Locked 30 วัน',
                'description' => 'Staking ล็อค 30 วัน ผลตอบแทน 12% ต่อปี',
                'contract_address' => null,
                'apy' => 12.00,
                'lock_period_days' => 30,
                'min_stake_amount' => '100.00000000',
                'max_stake_amount' => '500000.00000000',
                'pool_cap' => '5000000.00000000',
                'total_staked' => '0.00000000',
                'max_stakers' => null,
                'current_stakers' => 0,
                'reward_token_type' => 'same',
                'reward_token_id' => $tpixToken->id,
                'total_rewards_distributed' => '0.00000000',
                'pending_rewards' => '0.00000000',
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'token_id' => $tpixToken->id,
                'creator_id' => $creator->id,
                'name' => 'TPIX Locked 90 วัน',
                'description' => 'Staking ล็อค 90 วัน ผลตอบแทน 18% ต่อปี',
                'contract_address' => null,
                'apy' => 18.00,
                'lock_period_days' => 90,
                'min_stake_amount' => '500.00000000',
                'max_stake_amount' => '1000000.00000000',
                'pool_cap' => '10000000.00000000',
                'total_staked' => '0.00000000',
                'max_stakers' => null,
                'current_stakers' => 0,
                'reward_token_type' => 'same',
                'reward_token_id' => $tpixToken->id,
                'total_rewards_distributed' => '0.00000000',
                'pending_rewards' => '0.00000000',
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'token_id' => $tpixToken->id,
                'creator_id' => $creator->id,
                'name' => 'TPIX Locked 180 วัน',
                'description' => 'Staking ล็อค 180 วัน ผลตอบแทน 25% ต่อปี',
                'contract_address' => null,
                'apy' => 25.00,
                'lock_period_days' => 180,
                'min_stake_amount' => '1000.00000000',
                'max_stake_amount' => '5000000.00000000',
                'pool_cap' => '50000000.00000000',
                'total_staked' => '0.00000000',
                'max_stakers' => null,
                'current_stakers' => 0,
                'reward_token_type' => 'same',
                'reward_token_id' => $tpixToken->id,
                'total_rewards_distributed' => '0.00000000',
                'pending_rewards' => '0.00000000',
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'token_id' => $tpixToken->id,
                'creator_id' => $creator->id,
                'name' => 'TPIX Locked 365 วัน - ผลตอบแทนสูงสุด',
                'description' => 'Staking ล็อค 1 ปีเต็ม ผลตอบแทน 36% ต่อปี - สูงสุด!',
                'contract_address' => null,
                'apy' => 36.00,
                'lock_period_days' => 365,
                'min_stake_amount' => '5000.00000000',
                'max_stake_amount' => '10000000.00000000',
                'pool_cap' => '100000000.00000000',
                'total_staked' => '0.00000000',
                'max_stakers' => 1000,
                'current_stakers' => 0,
                'reward_token_type' => 'same',
                'reward_token_id' => $tpixToken->id,
                'total_rewards_distributed' => '0.00000000',
                'pending_rewards' => '0.00000000',
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => $now->copy()->addYear(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert pools
        DB::table('tpix_staking_pools')->insert($pools);

        $this->command->info('✅ Seed ข้อมูล TPIX Staking Pools สำเร็จ! สร้าง ' . count($pools) . ' pools');
        $this->command->info('');
        $this->command->info('📊 Pools ที่สร้าง:');
        foreach ($pools as $pool) {
            $this->command->info('   - ' . $pool['name'] . ' (APY: ' . $pool['apy'] . '%)');
        }
    }
}
