<?php

namespace Database\Seeders;

use App\Models\Affiliate;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoCommissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้างข้อมูล commissions สำหรับกราฟเดสบอร์ด
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating demo commissions...');

        $affiliates = Affiliate::with('user')->get();

        if ($affiliates->count() === 0) {
            $this->command->warn('⚠️ No affiliates found. Please run DemoAffiliatesSeeder first.');
            return;
        }

        // สร้างข้อมูล commission ย้อนหลัง 6 เดือน
        $statuses = ['pending', 'approved', 'paid'];
        $types = ['direct', 'indirect', 'bonus']; // ใช้ค่าที่ตรงกับ enum ในตาราง
        $commissionsCreated = 0;

        foreach ($affiliates as $affiliate) {
            // สร้าง 10-20 commissions ต่อ affiliate
            $commissionsCount = rand(10, 20);

            for ($i = 0; $i < $commissionsCount; $i++) {
                // สุ่มวันที่ย้อนหลัง 180 วัน
                $createdAt = Carbon::now()->subDays(rand(0, 180));
                $status = $statuses[array_rand($statuses)];
                $type = $types[array_rand($types)];
                $amount = rand(100, 5000);
                $percentage = rand(5, 20);

                $commissionData = [
                    'affiliate_id' => $affiliate->id,
                    'user_id' => $affiliate->user_id,
                    'order_id' => 'ORD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10)),
                    'amount' => $amount,
                    'percentage' => $percentage,
                    'type' => $type,
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                // เพิ่ม approved_at ถ้า status เป็น approved หรือ paid
                if (in_array($status, ['approved', 'paid'])) {
                    $commissionData['approved_at'] = $createdAt->copy()->addDays(rand(1, 5));
                }

                // เพิ่ม paid_at ถ้า status เป็น paid
                if ($status === 'paid') {
                    $commissionData['paid_at'] = $createdAt->copy()->addDays(rand(5, 15));
                }

                // เพิ่ม notes บางครั้ง
                if (rand(0, 1)) {
                    $notes = [
                        'Direct commission from purchase',
                        'Indirect commission from downline',
                        'Monthly performance bonus',
                        'Special promotion commission',
                        'Quarterly bonus',
                    ];
                    $commissionData['notes'] = $notes[array_rand($notes)];
                }

                Commission::create($commissionData);
                $commissionsCreated++;
            }
        }

        $this->command->info("✅ Created {$commissionsCreated} demo commissions");

        // สร้างสถิติ
        $pendingCount = Commission::where('status', 'pending')->count();
        $approvedCount = Commission::where('status', 'approved')->count();
        $paidCount = Commission::where('status', 'paid')->count();
        $totalAmount = Commission::sum('amount');

        $this->command->info('');
        $this->command->info('📊 Commission Statistics:');
        $this->command->info("   Pending: {$pendingCount}");
        $this->command->info("   Approved: {$approvedCount}");
        $this->command->info("   Paid: {$paidCount}");
        $this->command->info("   Total Amount: ฿" . number_format($totalAmount, 2));
        $this->command->info('');
    }
}
