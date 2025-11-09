<?php

namespace Database\Seeders;

use App\Models\TwoFactorSetting;
use App\Models\OtpSetting;
use Illuminate\Database\Seeder;

class TwoFactorSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * - Preserves existing 2FA settings
     * - Only updates NULL values in OTP settings (doesn't overwrite existing LINE OTP config)
     */
    public function run(): void
    {
        // Check if 2FA settings already exist
        $existingTwoFactorSetting = TwoFactorSetting::find(1);

        if ($existingTwoFactorSetting) {
            $this->command->warn('⚠️  Two-Factor Authentication settings already exist!');
            $this->command->info('   Skipping to preserve your 2FA configuration.');
        } else {
            // Fresh install - create default 2FA settings
            $this->command->info('🌱 Creating default 2FA settings...');

            TwoFactorSetting::create([
                'id' => 1,
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
                'withdrawal_threshold' => 10000.00,
                'transfer_threshold' => 5000.00,
            ]);

            $this->command->info('✅ Two-Factor Authentication settings created successfully!');
        }

        // Smart update for OTP settings - only set NULL values
        $otpSettings = OtpSetting::first();
        if ($otpSettings) {
            $updates = [];

            if (is_null($otpSettings->enable_line_otp ?? null)) {
                $updates['enable_line_otp'] = false;
            }

            if (is_null($otpSettings->line_otp_message_template ?? null)) {
                $updates['line_otp_message_template'] = 'รหัสยืนยันของคุณคือ: {code}\nใช้งานได้ใน {expiry} นาที';
            }

            if (!empty($updates)) {
                $otpSettings->update($updates);
                $this->command->info('✅ Updated OTP settings with LINE OA support (NULL values only).');
            } else {
                $this->command->info('   ⏭️  OTP settings already configured.');
            }
        }

        // Summary
        $this->command->info('');
        $this->command->info('📌 2FA Configuration:');
        $this->command->info('  - 2FA is disabled by default');
        $this->command->info('  - Required for withdrawals > 10,000 THB');
        $this->command->info('  - Required for transfers > 5,000 THB');
        $this->command->info('  - Supports SMS and LINE OA');
    }
}
