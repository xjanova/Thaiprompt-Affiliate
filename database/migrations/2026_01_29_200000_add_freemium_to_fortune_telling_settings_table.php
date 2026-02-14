<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ระบบ Freemium สำหรับดูดวง
     *
     * รองรับ:
     * - คำทำนายเชิงลึก (deep reading) ที่ต้องจ่ายเงิน
     * - ขอรับคำทำนายก่อนจ่ายทีหลัง (try before buy)
     * - ดูดวงฟรีวันละ 1 ครั้ง ต้องการดูเพิ่มต้องจ่าย/สมัครสมาชิก
     * - ระบบสมัครสมาชิกรายเดือน/รายปี
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // === ระบบคำทำนายเชิงลึก ===
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_deep_reading')) {
                $table->boolean('enable_deep_reading')
                    ->default(true)
                    ->after('reading_price')
                    ->comment('เปิดใช้งานคำทำนายเชิงลึก (ต้องจ่ายเงิน/สมัครสมาชิก)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'deep_reading_price')) {
                $table->decimal('deep_reading_price', 10, 2)
                    ->default(99.00)
                    ->after('enable_deep_reading')
                    ->comment('ราคาคำทำนายเชิงลึกต่อครั้ง (บาท)');
            }

            // === ระบบขอรับคำทำนายก่อนจ่าย ===
            if (! Schema::hasColumn('fortune_telling_settings', 'allow_try_before_buy')) {
                $table->boolean('allow_try_before_buy')
                    ->default(true)
                    ->after('deep_reading_price')
                    ->comment('อนุญาตให้ขอรับคำทำนายเชิงลึก 1 ครั้ง/วัน ก่อนจ่ายเงิน');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'free_deep_per_day')) {
                $table->integer('free_deep_per_day')
                    ->default(1)
                    ->after('allow_try_before_buy')
                    ->comment('จำนวนคำทำนายเชิงลึกฟรีต่อวัน (ก่อนจ่ายเงิน)');
            }

            // === ระบบสมัครสมาชิก ===
            if (! Schema::hasColumn('fortune_telling_settings', 'subscription_enabled')) {
                $table->boolean('subscription_enabled')
                    ->default(true)
                    ->after('free_deep_per_day')
                    ->comment('เปิดใช้งานระบบสมัครสมาชิก');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'subscription_monthly_price')) {
                $table->decimal('subscription_monthly_price', 10, 2)
                    ->default(199.00)
                    ->after('subscription_enabled')
                    ->comment('ราคาสมัครสมาชิกรายเดือน (บาท)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'subscription_yearly_price')) {
                $table->decimal('subscription_yearly_price', 10, 2)
                    ->default(1990.00)
                    ->after('subscription_monthly_price')
                    ->comment('ราคาสมัครสมาชิกรายปี (บาท)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'subscription_benefits')) {
                $table->text('subscription_benefits')
                    ->nullable()
                    ->after('subscription_yearly_price')
                    ->comment('สิทธิประโยชน์ของสมาชิก (แสดงให้ผู้ใช้เห็น)');
            }

            // === Prompt Templates แยกระดับ ===
            if (! Schema::hasColumn('fortune_telling_settings', 'basic_prompt_template')) {
                $table->text('basic_prompt_template')
                    ->nullable()
                    ->after('prompt_template')
                    ->comment('Prompt สำหรับคำทำนายพื้นฐาน (สั้น กระชับ)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'deep_prompt_template')) {
                $table->text('deep_prompt_template')
                    ->nullable()
                    ->after('basic_prompt_template')
                    ->comment('Prompt สำหรับคำทำนายเชิงลึก (ละเอียด ลึกซึ้ง)');
            }

            // === ข้อความอัตโนมัติเพิ่มเติม ===
            if (! Schema::hasColumn('fortune_telling_settings', 'subscription_message')) {
                $table->text('subscription_message')
                    ->nullable()
                    ->after('payment_message')
                    ->comment('ข้อความแนะนำสมัครสมาชิก');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'try_before_buy_message')) {
                $table->text('try_before_buy_message')
                    ->nullable()
                    ->after('subscription_message')
                    ->comment('ข้อความหลังทดลองฟรี (แนะนำให้จ่ายเงินเพื่อดูเพิ่ม)');
            }
        });
    }

    /**
     * ลบคอลัมน์ระบบ Freemium
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'enable_deep_reading',
                'deep_reading_price',
                'allow_try_before_buy',
                'free_deep_per_day',
                'subscription_enabled',
                'subscription_monthly_price',
                'subscription_yearly_price',
                'subscription_benefits',
                'basic_prompt_template',
                'deep_prompt_template',
                'subscription_message',
                'try_before_buy_message',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
