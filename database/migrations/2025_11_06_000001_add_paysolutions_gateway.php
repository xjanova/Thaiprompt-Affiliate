<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert PaySolutions payment gateway
        DB::table('payment_gateways')->insert([
            'code' => 'paysolutions',
            'name' => 'PaySolutions',
            'slug' => 'paysolutions',
            'description' => 'PaySolutions Payment Gateway - รองรับการชำระเงินหลากหลายรูปแบบ',
            'instructions' => 'ชำระเงินผ่าน PaySolutions ด้วย QR Code, บัตรเครดิต, e-Wallet และอื่นๆ',
            'is_active' => false, // Admin needs to configure first
            'is_available' => true,
            'is_coming_soon' => false,
            'supports_deposit' => true,
            'supports_withdrawal' => false, // Can be enabled later
            'config' => json_encode([
                'api_version' => 'v1',
                'timeout' => 30,
                'supported_methods' => [
                    'qr' => 'QR Code Payment',
                    'card' => 'Credit/Debit Card',
                    'bank_transfer' => 'Bank Transfer',
                    'ewallet' => 'E-Wallet',
                    'installment' => 'Installment',
                ],
            ]),
            'credentials' => json_encode([
                // Will be filled by admin in settings
                'merchant_id' => '',
                'api_key' => '',
                'secret_key' => '',
                'webhook_secret' => '',
            ]),
            'fees' => json_encode([
                'type' => 'percentage',
                'deposit_fee' => 2.5, // 2.5% fee
                'fixed_fee' => 0,
                'min_fee' => 5, // Minimum 5 THB
                'withdrawal_fee' => 0,
            ]),
            'limits' => json_encode([
                'min_amount' => 10,
                'max_amount' => 1000000, // 1M THB
                'daily_limit' => 5000000, // 5M THB per day
            ]),
            'icon' => '💳',
            'logo_url' => 'https://paysolutions.asia/images/logo.png',
            'color' => '#4F46E5',
            'help_url' => 'https://api-docs.paysolutions.asia/',
            'test_mode' => true,
            'test_credentials' => json_encode([
                'merchant_id' => 'TEST_MERCHANT',
                'api_key' => 'test_api_key',
                'secret_key' => 'test_secret_key',
                'webhook_secret' => 'test_webhook_secret',
            ]),
            'sort_order' => 10,
            'category' => 'thai',
            'metadata' => json_encode([
                'provider' => 'paysolutions',
                'country' => 'TH',
                'documentation' => 'https://api-docs.paysolutions.asia/docs/api/overviews',
                'support_email' => 'support@paysolutions.asia',
                'features' => [
                    'qr_payment' => true,
                    'card_payment' => true,
                    'bank_transfer' => true,
                    'ewallet' => true,
                    'installment' => true,
                    'refund' => true,
                    'webhook' => true,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_gateways')->where('code', 'paysolutions')->delete();
    }
};
