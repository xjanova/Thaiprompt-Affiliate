<?php

namespace App\Console\Commands;

use App\Models\MlmMember;
use App\Models\Wallet;
use Illuminate\Console\Command;

/**
 * สร้าง Wallet ย้อนหลังให้ MlmMember ทุกคนที่ยังไม่มี
 */
class FortuneCreateWallets extends Command
{
    protected $signature = 'fortune:create-wallets';

    protected $description = 'สร้าง Wallet ให้ MlmMember ทุกคนที่ยังไม่มี';

    public function handle(): int
    {
        $members = MlmMember::all();
        $created = 0;

        foreach ($members as $member) {
            $wallet = Wallet::where('user_id', $member->user_id)->first();
            if (! $wallet) {
                Wallet::create([
                    'user_id' => $member->user_id,
                    'balance' => 0,
                    'currency' => 'THB',
                    'status' => 'active',
                ]);
                $created++;
                $this->info("✅ สร้าง Wallet: user #{$member->user_id} ({$member->member_code})");
            }
        }

        $total = Wallet::count();
        $this->info("เสร็จ: สร้างใหม่ {$created} wallets | ทั้งหมด {$total} wallets");

        return 0;
    }
}
