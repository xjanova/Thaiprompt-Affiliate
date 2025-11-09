<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if payment gateways already exist
        $existingGatewaysCount = PaymentGateway::whereIn('code', [
            'promptpay',
            'bank_transfer',
            'omise'
        ])->count();

        if ($existingGatewaysCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all gateways
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all payment gateways...');

        $gateways = $this->getAllGateways();

        foreach ($gateways as $gateway) {
            PaymentGateway::create($gateway);
        }

        $this->command->info('✅ Payment gateways seeded successfully: ' . count($gateways) . ' gateways');
    }

    /**
     * Update mode - add only missing gateways
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing payment gateways detected!');
        $this->command->info('   Running in UPDATE mode (adding missing gateways only)...');

        $gateways = $this->getAllGateways();
        $added = 0;
        $skipped = 0;

        foreach ($gateways as $gateway) {
            if (!PaymentGateway::where('code', $gateway['code'])->exists()) {
                PaymentGateway::create($gateway);
                $this->command->info("   ➕ Added: {$gateway['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new payment gateways.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing gateways (preserved).");
        }
    }

    /**
     * Get all default payment gateways
     */
    private function getAllGateways(): array
    {
        return [
            // Thai Payment Methods
            [
                'code' => 'promptpay',
                'name' => 'พร้อมเพย์ (PromptPay)',
                'slug' => 'promptpay',
                'description' => 'ชำระเงินผ่าน QR Code พร้อมเพย์ รวดเร็ว ปลอดภัย',
                'instructions' => "1. กรอกเบอร์โทรศัพท์หรือเลขบัตรประชาชนที่ลงทะเบียน PromptPay\n2. ระบุชื่อเจ้าของบัญชี\n3. ระบบจะสร้าง QR Code อัตโนมัติเมื่อลูกค้าฝากเงิน",
                'is_active' => false,
                'is_available' => true,
                'is_coming_soon' => false,
                'supports_deposit' => true,
                'supports_withdrawal' => false,
                'icon' => '💳',
                'color' => '#10B981',
                'category' => 'thai',
                'sort_order' => 1,
                'help_url' => 'https://www.bot.or.th/Thai/PaymentSystems/StandardPS/PromptPay/Pages/default.aspx',
                'fees' => [
                    'deposit_fee_type' => 'percentage',
                    'deposit_fee_amount' => 0,
                    'withdrawal_fee_type' => 'percentage',
                    'withdrawal_fee_amount' => 0,
                ],
                'limits' => [
                    'min_deposit' => 1,
                    'max_deposit' => 50000,
                    'min_withdrawal' => 100,
                    'max_withdrawal' => 50000,
                ],
            ],
            [
                'code' => 'bank_transfer',
                'name' => 'โอนผ่านธนาคาร',
                'slug' => 'bank-transfer',
                'description' => 'โอนเงินผ่านธนาคารและอัพโหลดสลิปยืนยัน',
                'instructions' => "1. กรอกข้อมูลบัญชีธนาคารของคุณ\n2. ลูกค้าจะโอนเงินเข้าบัญชีและอัพโหลดสลิป\n3. แอดมินตรวจสอบและอนุมัติ",
                'is_active' => false,
                'is_available' => true,
                'is_coming_soon' => false,
                'supports_deposit' => true,
                'supports_withdrawal' => true,
                'icon' => '🏦',
                'color' => '#059669',
                'category' => 'bank',
                'sort_order' => 2,
                'fees' => [
                    'deposit_fee_type' => 'fixed',
                    'deposit_fee_amount' => 0,
                    'withdrawal_fee_type' => 'fixed',
                    'withdrawal_fee_amount' => 15,
                ],
                'limits' => [
                    'min_deposit' => 100,
                    'max_deposit' => 500000,
                    'min_withdrawal' => 100,
                    'max_withdrawal' => 100000,
                ],
            ],
            [
                'code' => 'truemoney',
                'name' => 'TrueMoney Wallet',
                'slug' => 'truemoney',
                'description' => 'กระเป๋าเงินอิเล็กทรอนิกส์ TrueMoney',
                'instructions' => "1. สมัครใช้งาน TrueMoney Business\n2. ติดต่อทีมงานเพื่อรับ API credentials\n3. กรอก App ID และ App Secret",
                'is_active' => false,
                'is_available' => true,
                'is_coming_soon' => false,
                'supports_deposit' => true,
                'supports_withdrawal' => true,
                'icon' => '🟠',
                'color' => '#F97316',
                'category' => 'e-wallet',
                'sort_order' => 3,
                'help_url' => 'https://www.truemoney.com/business/',
                'fees' => [
                    'deposit_fee_type' => 'percentage',
                    'deposit_fee_amount' => 1.5,
                    'withdrawal_fee_type' => 'percentage',
                    'withdrawal_fee_amount' => 1.5,
                ],
                'limits' => [
                    'min_deposit' => 1,
                    'max_deposit' => 30000,
                    'min_withdrawal' => 100,
                    'max_withdrawal' => 30000,
                ],
            ],
            [
                'code' => 'omise',
                'name' => 'Omise',
                'slug' => 'omise',
                'description' => 'Payment Gateway ชั้นนำในเอเชียตะวันออกเฉียงใต้ รองรับบัตรเครดิต/เดบิต',
                'instructions' => "1. สมัครบัญชี Omise ที่ https://dashboard.omise.co\n2. ไปที่เมนู API Keys\n3. คัดลอก Public key และ Secret key\n4. ใช้ Test keys สำหรับทดสอบ, Live keys สำหรับใช้งานจริง",
                'is_active' => false,
                'is_available' => true,
                'is_coming_soon' => false,
                'supports_deposit' => true,
                'supports_withdrawal' => false,
                'icon' => '💎',
                'color' => '#06B6D4',
                'category' => 'thai',
                'sort_order' => 4,
                'help_url' => 'https://docs.opn.ooo/',
                'fees' => [
                    'deposit_fee_type' => 'percentage',
                    'deposit_fee_amount' => 2.9,
                    'withdrawal_fee_type' => 'percentage',
                    'withdrawal_fee_amount' => 0,
                ],
                'limits' => [
                    'min_deposit' => 20,
                    'max_deposit' => 1000000,
                    'min_withdrawal' => 100,
                    'max_withdrawal' => 100000,
                ],
            ],

            // International Payment Methods
            [
                'code' => 'stripe',
                'name' => 'Stripe',
                'slug' => 'stripe',
                'description' => 'Payment Gateway ระดับโลก รองรับบัตรเครดิต/เดบิตทั่วโลก',
                'instructions' => "1. สมัครบัญชี Stripe ที่ https://dashboard.stripe.com\n2. ไปที่ Developers → API Keys\n3. คัดลอก Publishable key และ Secret key\n4. ตั้งค่า Webhook: Developers → Webhooks → Add endpoint",
                'is_active' => false,
                'is_available' => true,
                'is_coming_soon' => false,
                'supports_deposit' => true,
                'supports_withdrawal' => false,
                'icon' => '💜',
                'color' => '#8B5CF6',
                'category' => 'international',
                'sort_order' => 10,
                'help_url' => 'https://stripe.com/docs',
                'fees' => [
                    'deposit_fee_type' => 'percentage',
                    'deposit_fee_amount' => 3.25,
                    'withdrawal_fee_type' => 'percentage',
                    'withdrawal_fee_amount' => 0,
                ],
                'limits' => [
                    'min_deposit' => 20,
                    'max_deposit' => 5000000,
                    'min_withdrawal' => 100,
                    'max_withdrawal' => 100000,
                ],
            ],
            [
                'code' => 'paypal',
                'name' => 'PayPal',
                'slug' => 'paypal',
                'description' => 'Payment Gateway ที่ได้รับความนิยมทั่วโลก',
                'instructions' => "1. สมัครบัญชี PayPal Business\n2. ไปที่ PayPal Developer → My Apps & Credentials\n3. สร้างแอพใหม่หรือเลือกแอพที่มีอยู่\n4. คัดลอก Client ID และ Secret",
                'is_active' => false,
                'is_available' => true,
                'is_coming_soon' => false,
                'supports_deposit' => true,
                'supports_withdrawal' => true,
                'icon' => '💵',
                'color' => '#3B82F6',
                'category' => 'international',
                'sort_order' => 11,
                'help_url' => 'https://developer.paypal.com/docs/',
                'fees' => [
                    'deposit_fee_type' => 'percentage',
                    'deposit_fee_amount' => 3.9,
                    'withdrawal_fee_type' => 'percentage',
                    'withdrawal_fee_amount' => 2.0,
                ],
                'limits' => [
                    'min_deposit' => 50,
                    'max_deposit' => 3000000,
                    'min_withdrawal' => 100,
                    'max_withdrawal' => 200000,
                ],
            ],
            [
                'code' => 'razorpay',
                'name' => 'Razorpay',
                'slug' => 'razorpay',
                'description' => 'Payment Gateway ชั้นนำในอินเดีย รองรับหลายสกุลเงิน',
                'instructions' => "1. สมัครบัญชี Razorpay ที่ https://dashboard.razorpay.com\n2. ไปที่ Settings → API Keys\n3. สร้าง API Key ใหม่ (ถ้ายังไม่มี)\n4. คัดลอก Key ID และ Key Secret",
                'is_active' => false,
                'is_available' => true,
                'is_coming_soon' => false,
                'supports_deposit' => true,
                'supports_withdrawal' => false,
                'icon' => '⚡',
                'color' => '#6366F1',
                'category' => 'international',
                'sort_order' => 12,
                'help_url' => 'https://razorpay.com/docs/',
                'fees' => [
                    'deposit_fee_type' => 'percentage',
                    'deposit_fee_amount' => 2.0,
                    'withdrawal_fee_type' => 'percentage',
                    'withdrawal_fee_amount' => 0,
                ],
                'limits' => [
                    'min_deposit' => 50,
                    'max_deposit' => 2000000,
                    'min_withdrawal' => 100,
                    'max_withdrawal' => 100000,
                ],
            ],

            // Coming Soon
            [
                'code' => 'crypto',
                'name' => 'Cryptocurrency',
                'slug' => 'cryptocurrency',
                'description' => 'ชำระเงินด้วย Bitcoin, Ethereum, USDT และสกุลเงินดิจิทัลอื่นๆ',
                'instructions' => "ระบบกำลังอยู่ในระหว่างการพัฒนา\n\nฟีเจอร์ที่จะมี:\n- รองรับ Bitcoin (BTC)\n- รองรับ Ethereum (ETH)\n- รองรับ USDT (Tether)\n- แปลงเงินอัตโนมัติ\n- Wallet Integration",
                'is_active' => false,
                'is_available' => false,
                'is_coming_soon' => true,
                'supports_deposit' => true,
                'supports_withdrawal' => true,
                'icon' => '₿',
                'color' => '#F59E0B',
                'category' => 'crypto',
                'sort_order' => 20,
                'help_url' => null,
                'fees' => [
                    'deposit_fee_type' => 'percentage',
                    'deposit_fee_amount' => 1.0,
                    'withdrawal_fee_type' => 'percentage',
                    'withdrawal_fee_amount' => 1.0,
                ],
                'limits' => [
                    'min_deposit' => 100,
                    'max_deposit' => 10000000,
                    'min_withdrawal' => 500,
                    'max_withdrawal' => 5000000,
                ],
            ],
        ];
    }
}
