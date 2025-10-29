<?php

namespace Database\Seeders;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAffiliatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้างข้อมูล affiliates พร้อมโครงสร้างแบบ tree
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating demo affiliates...');

        // Get affiliate users
        $affiliateUsers = User::where('role', 'affiliate')->get();

        if ($affiliateUsers->count() === 0) {
            $this->command->warn('⚠️ No affiliate users found. Please run DemoUsersSeeder first.');
            return;
        }

        // Create parent affiliates (Level 1)
        $parentAffiliates = [];
        foreach ($affiliateUsers->take(2) as $user) {
            $affiliate = Affiliate::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'parent_id' => null,
                    'referral_code' => Affiliate::generateReferralCode(),
                    'level' => 1,
                    'total_referrals' => rand(5, 15),
                    'total_earnings' => rand(10000, 50000),
                    'status' => 'active',
                ]
            );
            $parentAffiliates[] = $affiliate;

            // Update user's affiliate_id
            $user->affiliate_id = $affiliate->id;
            $user->save();
        }
        $this->command->info('✅ Created 2 parent affiliates (Level 1)');

        // Create child affiliates (Level 2)
        $childAffiliates = [];
        foreach ($affiliateUsers->skip(2)->take(3) as $index => $user) {
            $parentIndex = $index % count($parentAffiliates);
            $affiliate = Affiliate::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'parent_id' => $parentAffiliates[$parentIndex]->id,
                    'referral_code' => Affiliate::generateReferralCode(),
                    'level' => 2,
                    'total_referrals' => rand(2, 8),
                    'total_earnings' => rand(5000, 20000),
                    'status' => 'active',
                ]
            );
            $childAffiliates[] = $affiliate;

            // Update user's affiliate_id
            $user->affiliate_id = $affiliate->id;
            $user->save();
        }
        $this->command->info('✅ Created 3 child affiliates (Level 2)');

        // Assign remaining users to affiliates
        $regularUsers = User::where('role', 'user')->whereNull('affiliate_id')->get();
        foreach ($regularUsers as $index => $user) {
            $affiliateIndex = $index % $affiliateUsers->count();
            $affiliate = Affiliate::where('user_id', $affiliateUsers[$affiliateIndex]->id)->first();
            if ($affiliate) {
                $user->affiliate_id = $affiliate->id;
                $user->save();
            }
        }
        $this->command->info('✅ Assigned users to affiliates');

        $this->command->info('');
    }
}
