<?php

namespace Database\Seeders;

use App\Models\NFCCard;
use App\Models\NFCTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * NFC Card Seeder
 *
 * สร้างข้อมูลตัวอย่างสำหรับระบบบัตร NFC
 * - บัตรตัวอย่างในหลายสถานะ
 * - ผูกกับ Wallet
 * - มี Transactions ตัวอย่าง
 * - รองรับ TPIX และ Auto Top-up
 *
 * @version 1.0.0
 * @since 2025-11-22
 */
class NFCCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล NFC Cards...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่ (idempotent)
        if (NFCCard::count() > 0) {
            $this->command->warn('⚠️  มีข้อมูล NFC Cards อยู่แล้ว (' . NFCCard::count() . ' การ์ด)');
            $this->command->info('💡 ข้ามการ seed เพื่อป้องกันข้อมูลซ้ำ');
            return;
        }

        // ดึง users สำหรับสร้างบัตร
        $users = User::take(5)->get();

        if ($users->isEmpty()) {
            $this->command->error('❌ ไม่พบ users ในระบบ กรุณารัน UserSeeder ก่อน');
            return;
        }

        $this->command->info('👥 พบ ' . $users->count() . ' users');

        $totalCards = 0;
        $totalTransactions = 0;

        foreach ($users as $user) {
            // สร้างบัตร 2-3 ใบต่อ user
            $cardsCount = rand(2, 3);

            for ($i = 1; $i <= $cardsCount; $i++) {
                $card = $this->createCardForUser($user, $i);
                $totalCards++;

                // สร้าง transactions ตัวอย่าง (70% มี transactions)
                if (rand(1, 100) <= 70) {
                    $transCount = $this->createTransactionsForCard($card);
                    $totalTransactions += $transCount;
                }
            }
        }

        $this->command->info('✅ Seed ข้อมูล NFC Cards สำเร็จ!');
        $this->command->info("   📇 สร้าง {$totalCards} บัตร");
        $this->command->info("   💳 สร้าง {$totalTransactions} ธุรกรรม");

        Log::info('NFCCardSeeder completed', [
            'cards' => $totalCards,
            'transactions' => $totalTransactions,
        ]);
    }

    /**
     * สร้างบัตรสำหรับ user
     *
     * @param User $user
     * @param int $index
     * @return NFCCard
     */
    protected function createCardForUser(User $user, int $index): NFCCard
    {
        // สุ่มประเภทของบัตร
        $cardTypes = [
            ['name' => 'บัตรหลัก', 'type' => NFCCard::TYPE_STANDARD, 'balance' => 5000, 'daily_limit' => 10000, 'monthly_limit' => 100000, 'weight' => 50],
            ['name' => 'บัตรสำรอง', 'type' => NFCCard::TYPE_PREMIUM, 'balance' => 2000, 'daily_limit' => 5000, 'monthly_limit' => 50000, 'weight' => 30],
            ['name' => 'บัตรสะสมแต้ม', 'type' => NFCCard::TYPE_VIP, 'balance' => 1000, 'daily_limit' => 3000, 'monthly_limit' => 30000, 'weight' => 20],
        ];

        $type = $this->weightedRandom($cardTypes);

        // สร้างเลขบัตร 16 หลัก (เริ่มด้วย 5899 สำหรับ NFC)
        $cardNumber = '5899' . str_pad(rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);

        // กำหนดสถานะ
        $statuses = [
            ['status' => NFCCard::STATUS_ACTIVE, 'is_enabled' => true, 'weight' => 70],
            ['status' => NFCCard::STATUS_ACTIVE, 'is_enabled' => false, 'weight' => 15],
            ['status' => NFCCard::STATUS_BLOCKED, 'is_enabled' => false, 'weight' => 10],
            ['status' => NFCCard::STATUS_SUSPENDED, 'is_enabled' => false, 'weight' => 5],
        ];

        $statusConfig = $this->weightedRandom($statuses);

        // ดึง wallet ของ user (ถ้ามี)
        $wallet = Wallet::where('user_id', $user->id)->first();

        // 80% ของบัตรจะผูกกับ wallet
        $walletId = ($wallet && rand(1, 100) <= 80) ? $wallet->id : null;

        // สร้างบัตร
        $card = NFCCard::create([
            'user_id' => $user->id,
            'card_number' => $cardNumber,
            'card_name' => $type['name'] . ' #' . $index,
            'card_type' => $type['type'],
            'balance' => $type['balance'],
            'status' => $statusConfig['status'],
            'is_enabled' => $statusConfig['is_enabled'],
            'issued_by' => 1, // Admin
            // ❌ ลบ issued_at (column ไม่มี) ใช้ created_at แทน
            'expires_at' => now()->addYears(5),

            // ✅ Two-way Encryption Data (required fields)
            'encrypted_data' => base64_encode(json_encode([
                'card_holder' => $user->name,
                'card_number' => $cardNumber,
                'card_type' => $type['type'],
                'issued_date' => now()->toDateString(),
            ])),
            'encryption_key_hash' => hash('sha256', $cardNumber . config('app.key')),
            'card_signature' => hash_hmac('sha256', $cardNumber, config('app.key')),
            'encryption_version' => 1,

            // Card pairing & activation
            'is_paired' => true,
            'paired_at' => now()->subDays(rand(1, 30)),
            'activated_at' => now()->subDays(rand(1, 30)),

            // Spending limits
            'daily_spending_limit' => $type['daily_limit'],
            'monthly_spending_limit' => $type['monthly_limit'],
            'transaction_limit' => min($type['daily_limit'] / 2, 5000),
            'daily_spent' => rand(0, $type['daily_limit'] * 0.3),
            'monthly_spent' => rand(0, $type['monthly_limit'] * 0.2),
            // ✅ แก้ชื่อ column ให้ตรงกับ migration
            'last_reset_daily' => today(),
            'last_reset_monthly' => now()->startOfMonth(),

            // Wallet integration
            'wallet_id' => $walletId,

            // TPIX support (30% รองรับ TPIX)
            'supports_tpix' => rand(1, 100) <= 30,
            'tpix_wallet_address' => rand(1, 100) <= 30 ? '0x' . bin2hex(random_bytes(20)) : null,

            // Auto top-up (50% เปิด auto top-up)
            'auto_topup_enabled' => $walletId && rand(1, 100) <= 50,
            'auto_topup_threshold' => $walletId ? 1000 : null,
            'auto_topup_amount' => $walletId ? 2000 : null,
        ]);

        $this->command->info("   ✓ สร้างบัตร: {$card->card_name} ({$card->masked_card_number})");

        return $card;
    }

    /**
     * สร้าง transactions ตัวอย่างสำหรับบัตร
     *
     * @param NFCCard $card
     * @return int จำนวน transactions ที่สร้าง
     */
    protected function createTransactionsForCard(NFCCard $card): int
    {
        $count = rand(3, 10);
        $balance = $card->balance;

        $transactionTypes = [
            ['type' => NFCTransaction::TYPE_PAYMENT, 'weight' => 60],
            ['type' => NFCTransaction::TYPE_TOPUP, 'weight' => 25],
            ['type' => NFCTransaction::TYPE_REFUND, 'weight' => 10],
            ['type' => NFCTransaction::TYPE_TRANSFER_OUT, 'weight' => 5],
        ];

        for ($i = 0; $i < $count; $i++) {
            $type = $this->weightedRandom($transactionTypes)['type'];
            $amount = match ($type) {
                NFCTransaction::TYPE_PAYMENT => rand(50, 500),
                NFCTransaction::TYPE_TOPUP => rand(500, 2000),
                NFCTransaction::TYPE_REFUND => rand(50, 300),
                NFCTransaction::TYPE_TRANSFER_OUT => rand(100, 1000),
                default => 100,
            };

            // คำนวณ balance_after
            if (in_array($type, [NFCTransaction::TYPE_PAYMENT, NFCTransaction::TYPE_TRANSFER_OUT])) {
                $balanceAfter = $balance - $amount;
            } else {
                $balanceAfter = $balance + $amount;
            }

            // สร้าง transaction
            NFCTransaction::create([
                'nfc_card_id' => $card->id,
                'user_id' => $card->user_id,
                'transaction_id' => 'NFC' . now()->format('YmdHis') . rand(1000, 9999),
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balance,
                'balance_after' => $balanceAfter,
                'status' => NFCTransaction::STATUS_COMPLETED,
                'location' => $this->randomLocation(),
                'ip_address' => $this->randomIP(),
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
                'metadata' => [
                    'merchant' => $this->randomMerchant(),
                    'category' => $this->randomCategory(),
                ],
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            $balance = $balanceAfter;
        }

        return $count;
    }

    /**
     * สุ่มค่าแบบ weighted random
     *
     * @param array $items
     * @return mixed
     */
    protected function weightedRandom(array $items): mixed
    {
        $totalWeight = array_sum(array_column($items, 'weight'));
        $random = rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($items as $item) {
            $currentWeight += $item['weight'];
            if ($random <= $currentWeight) {
                return $item;
            }
        }

        return $items[0];
    }

    /**
     * สุ่มสถานที่
     *
     * @return string
     */
    protected function randomLocation(): string
    {
        $locations = [
            '7-Eleven สาขาสยาม',
            'Central World ชั้น 3',
            'Big C Supercenter',
            'Lotus สาขาลาดพร้าว',
            'ตลาดนัดจตุจักร',
            'MBK Center',
            'Terminal 21',
            'Starbucks สาขาอโศก',
            'McDonald\'s สาขาสุขุมวิท',
            'KFC สาขาซีคอนสแควร์',
        ];

        return $locations[array_rand($locations)];
    }

    /**
     * สุ่ม IP address
     *
     * @return string
     */
    protected function randomIP(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }

    /**
     * สุ่มชื่อร้านค้า
     *
     * @return string
     */
    protected function randomMerchant(): string
    {
        $merchants = [
            '7-Eleven',
            'Starbucks',
            'McDonald\'s',
            'KFC',
            'Pizza Hut',
            'The Pizza Company',
            'Central Department Store',
            'Big C',
            'Lotus',
            'Tops Market',
        ];

        return $merchants[array_rand($merchants)];
    }

    /**
     * สุ่มหมวดหมู่
     *
     * @return string
     */
    protected function randomCategory(): string
    {
        $categories = [
            'อาหาร/เครื่องดื่ม',
            'ช้อปปิ้ง',
            'บันเทิง',
            'การเดินทาง',
            'สุขภาพ/ความงาม',
            'การศึกษา',
            'บริการ',
            'สินค้าอุปโภคบริโภค',
        ];

        return $categories[array_rand($categories)];
    }
}
