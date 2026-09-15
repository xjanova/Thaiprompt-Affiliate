<?php

namespace Tests\Concerns;

use App\Models\FortuneTellingSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างเฉพาะตารางที่เส้น /api/v1/juntra/server/* และด่านจับคู่ SMS ใช้ — บน sqlite :memory: เท่านั้น
 *
 * ทำไมไม่ใช้ RefreshDatabase: migration ทั้งชุดของโปรเจกต์นี้เขียนแบบ MySQL (ALTER ... MODIFY ...)
 *   รันบน sqlite ไม่ผ่าน และห้ามแตะฐานข้อมูล MySQL ในเครื่องเด็ดขาด
 *   → บังคับ connection เป็น sqlite :memory: ในเทสต์เอง แล้วรัน "ไฟล์ migration จริง" เฉพาะตัวที่เกี่ยว
 *     (ตารางที่ migration จริงเขียนแบบ MySQL-only ค่อยสร้างเองขั้นต่ำ ตามคอลัมน์ที่โค้ดอ่าน)
 */
trait BuildsJuntraServerSchema
{
    protected function useIsolatedSqlite(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        // .env.testing มี APP_KEY ความยาวผิด (ใช้เข้ารหัสไม่ได้) — คีย์ SlipOK (cast encrypted)
        // และ Passport ต้องใช้ encrypter จริง → ตั้งคีย์ทดสอบที่ถูกต้องเฉพาะในเทสต์นี้
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);
        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');

        FortuneTellingSetting::clearSettingsCache();
    }

    protected function buildJuntraServerSchema(): void
    {
        $this->useIsolatedSqlite();

        // throttle:* เรียก $request->user() — guard ของ Passport จะค้นผู้ใช้ (id = null สำหรับ token ของ client)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // ── ตารางที่ migration อื่นจะเติมคอลัมน์ให้ ต้องมีก่อน ─────────────────────
        Schema::create('fortune_telling_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->integer('max_free_readings')->nullable();
            $table->decimal('reading_price', 8, 2)->nullable();
            $table->decimal('deep_reading_price', 8, 2)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('slipok_pool_enabled')->default(false);
            $table->unsignedInteger('slipok_max_checks_per_user')->nullable();
            $table->unsignedInteger('slipok_check_window_hours')->nullable();
            $table->unsignedInteger('slipok_ban_after_rounds')->nullable();
            $table->unsignedInteger('bill_payment_timeout_minutes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fortune_readings', function (Blueprint $table) {
            $table->id();
            $table->string('bill_reference')->nullable();
            $table->string('reading_type')->nullable();
            $table->string('conversation_status')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->unsignedBigInteger('unique_payment_amount_id')->nullable();
            $table->decimal('partial_paid_total', 10, 2)->nullable();
            $table->decimal('partial_target_total', 10, 2)->nullable();
            $table->timestamp('partial_hold_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('promptpay_ref_no')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code')->nullable();
            $table->string('account_number')->nullable();
            $table->string('promptpay_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('sms_checker_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ── ไฟล์ migration จริง ───────────────────────────────────────────────
        foreach ([
            '2016_06_01_000002_create_oauth_access_tokens_table.php',
            '2016_06_01_000004_create_oauth_clients_table.php',
            '2026_07_17_000000_add_skip_authorization_to_oauth_clients.php',
            '2024_01_01_000001_create_sms_payment_tables.php',
            '2026_05_31_120000_add_slipok_verify_to_fortune.php',
            '2026_06_01_000100_add_slip_consumer_audit_to_slip_verifications.php',
            // ของใหม่ในงานนี้
            '2026_09_15_100000_add_external_ref_to_unique_payment_amounts.php',
            '2026_09_15_100100_add_external_status_to_sms_payment_notifications.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }

        // บน MySQL migration 2026_09_15_100100 เพิ่ม 'external' ลง enum ให้แล้ว
        // บน sqlite enum กลายเป็น CHECK ที่ migration แก้ไม่ได้ → จำลองผลเดียวกันด้วยการเปลี่ยนเป็น string
        Schema::table('sms_payment_notifications', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
        });

        DB::table('fortune_telling_settings')->insert([
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-2.5-flash',
            'max_free_readings' => 3,
            'reading_price' => 0,
            'deep_reading_price' => 39,
            'is_enabled' => true,
            'enable_slipok_verify' => true,
            'slipok_branch_id' => 'TESTBRANCH',
            'slipok_api_key' => Crypt::encryptString('TEST-SLIPOK-KEY'),
            'slipok_use_log' => true,
            'slipok_pool_enabled' => false,
            'slipok_max_checks_per_user' => 2,
            'slipok_check_window_hours' => 24,
            'slipok_ban_after_rounds' => 2,
            'bill_payment_timeout_minutes' => 180,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // บัญชีรับเงินของร้าน — ท้าย 4 หลัก 5514 (SlipOK ส่งเลขบัญชีปลายทางแบบ mask มา)
        DB::table('payment_bank_accounts')->insert([
            'bank_code' => 'KBANK',
            'account_number' => '1234565514',
            'promptpay_id' => '0900000002',
            'is_active' => true,
            'sms_checker_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
