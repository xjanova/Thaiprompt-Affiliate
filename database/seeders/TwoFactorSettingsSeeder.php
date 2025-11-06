<?php

namespace Database\Seeders;

use App\Models\TwoFactorSetting;
use App\Models\OtpSetting;
use Illuminate\Database\Seeder;

class TwoFactorSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default 2FA settings
        TwoFactorSetting::updateOrCreate(
            ['id' => 1],
            [
                'enabled' => false,
                'require_on_login' => false,
                'require_on_withdrawal' => true,
                'require_on_transfer' => true,
                'require_on_profile_change' => false,
                'require_on_password_change' => false,
                'require_on_payment_method_change' => false,
                'allow_sms' => true,
                'allow_line' => true,
                'allow_email' => false,
                'default_method' => 'sms',
                'grace_period_minutes' => 15,
                'allow_remember_device' => true,
                'remember_device_days' => 30,
                'withdrawal_threshold' => 10000.00, // Require 2FA for withdrawals above 10,000 THB
                'transfer_threshold' => 5000.00,    // Require 2FA for transfers above 5,000 THB
            ]
        );

        // Update OTP settings to include LINE OA support
        $otpSettings = OtpSetting::first();
        if ($otpSettings) {
            $otpSettings->update([
                'enable_line_otp' => false,
                'line_otp_message_template' => 'รหัสยืนยันของคุณคือ: {code}\nใช้งานได้ใน {expiry} นาที',
            ]);
        }

        $this->command->info('✓ Two-Factor Authentication settings seeded successfully!');
        $this->command->info('  - 2FA is disabled by default');
        $this->command->info('  - Required for withdrawals > 10,000 THB');
        $this->command->info('  - Required for transfers > 5,000 THB');
        $this->command->info('  - Supports SMS and LINE OA');
    }
}
