<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💳 (2026-05-09) เพิ่มตั้งค่า Stripe payment ใน fortune_telling_settings
 *
 * Settings ทั้งหมด admin ปรับได้จาก Admin → Fortune → Settings
 *
 * Columns:
 *   - enable_stripe_payment (bool): kill switch (default false ปิดไว้รอ test)
 *   - stripe_service_fee (decimal): ค่าบริการ +THB ต่อบิล (default 15.00)
 *   - stripe_session_expiry_minutes (int): Checkout Session อายุ (default 30 นาที)
 *   - stripe_account_id (string): Stripe acct_xxx สำหรับ build dashboard URL
 *   - stripe_test_mode (bool): test mode = true → ใช้ key ขึ้นต้น sk_test_xxx
 *   - stripe_secret_key (string, encrypted): API secret key (sk_live_xxx / sk_test_xxx)
 *   - stripe_publishable_key (string): Publishable key (pk_xxx) สำหรับ admin UI ลิงก์
 *   - stripe_webhook_secret (string, encrypted): Webhook signing secret (whsec_xxx)
 *   - stripe_product_deep_id (string): Stripe Product ID สำหรับ Deep 39฿
 *   - stripe_product_celtic_id (string): Stripe Product ID สำหรับ Celtic 99฿
 *
 * 🔒 secret_key + webhook_secret จะ encrypt ผ่าน Eloquent cast 'encrypted' ใน Model
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // Kill switch
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_stripe_payment')) {
                $table->boolean('enable_stripe_payment')
                    ->default(false)
                    ->comment('เปิดให้ลูกค้าเลือกชำระผ่าน Stripe (บัตรต่างประเทศ)');
            }

            // ค่าบริการ
            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_service_fee')) {
                $table->decimal('stripe_service_fee', 8, 2)
                    ->default(15.00)
                    ->comment('ค่าบริการบัตรต่างประเทศ (บวกเพิ่มจากราคาแพ็กเกจ, default 15฿)');
            }

            // อายุ Checkout Session
            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_session_expiry_minutes')) {
                $table->unsignedInteger('stripe_session_expiry_minutes')
                    ->default(30)
                    ->comment('อายุ Stripe Checkout Session (นาที, Stripe min=30)');
            }

            // Stripe account info
            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_account_id')) {
                $table->string('stripe_account_id', 64)
                    ->nullable()
                    ->comment('Stripe Account ID (acct_xxx) สำหรับสร้าง dashboard URL');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_test_mode')) {
                $table->boolean('stripe_test_mode')
                    ->default(true)
                    ->comment('Stripe test mode (true=ใช้ test keys, false=live)');
            }

            // API credentials (encrypted via model cast)
            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_secret_key')) {
                $table->text('stripe_secret_key')
                    ->nullable()
                    ->comment('Stripe Secret Key encrypted (sk_live_xxx / sk_test_xxx)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_publishable_key')) {
                $table->string('stripe_publishable_key', 255)
                    ->nullable()
                    ->comment('Stripe Publishable Key (pk_xxx, ไม่ใช่ความลับ)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_webhook_secret')) {
                $table->text('stripe_webhook_secret')
                    ->nullable()
                    ->comment('Stripe Webhook signing secret encrypted (whsec_xxx)');
            }

            // Existing Stripe products (จาก Stripe MCP detect)
            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_product_deep_id')) {
                $table->string('stripe_product_deep_id', 64)
                    ->nullable()
                    ->comment('Stripe Product ID สำหรับ Deep 39฿ (default: prod_UU1wXx9DI4s2gq)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'stripe_product_celtic_id')) {
                $table->string('stripe_product_celtic_id', 64)
                    ->nullable()
                    ->comment('Stripe Product ID สำหรับ Celtic 99฿ (default: prod_UU1zVarkNVzkpp)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'stripe_product_celtic_id',
                'stripe_product_deep_id',
                'stripe_webhook_secret',
                'stripe_publishable_key',
                'stripe_secret_key',
                'stripe_test_mode',
                'stripe_account_id',
                'stripe_session_expiry_minutes',
                'stripe_service_fee',
                'enable_stripe_payment',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
