<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ Marketing / Smart Hashtags / LINE quota warning
     * ให้แคมเปญดวงรายวันสามารถทำการตลาดอัตโนมัติได้
     *
     * ฟีเจอร์ใหม่:
     * - enable_auto_hashtags: เปิด/ปิด AI สร้าง hashtags อัตโนมัติ
     * - custom_hashtags: hashtags กำหนดเอง (ใส่ทุกโพส)
     * - enable_cta: เปิด/ปิด Call-to-Action ท้ายโพส
     * - cta_text: ข้อความ CTA
     * - enable_engagement_hooks: เปิด/ปิดการสร้างข้อความ engagement
     * - line_quota_warning_threshold: เตือนเมื่อโควต้า LINE เหลือน้อย
     * - line_monthly_quota: โควต้า LINE broadcast ต่อเดือน (500 ฟรี)
     * - line_used_this_month: จำนวน broadcast ที่ใช้ไปเดือนนี้
     */
    public function up(): void
    {
        Schema::table('fortune_horoscope_campaigns', function (Blueprint $table) {
            // === Smart Marketing / Hashtags ===
            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'enable_auto_hashtags', function ($table) {
                $table->boolean('enable_auto_hashtags')->default(true)->after('post_footer_template');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'custom_hashtags', function ($table) {
                $table->text('custom_hashtags')->nullable()->after('enable_auto_hashtags');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'enable_cta', function ($table) {
                $table->boolean('enable_cta')->default(true)->after('custom_hashtags');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'cta_text', function ($table) {
                $table->text('cta_text')->nullable()->after('enable_cta');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'enable_engagement_hooks', function ($table) {
                $table->boolean('enable_engagement_hooks')->default(true)->after('cta_text');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'page_name', function ($table) {
                $table->string('page_name', 255)->nullable()->after('enable_engagement_hooks');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'page_mention', function ($table) {
                $table->string('page_mention', 255)->nullable()->after('page_name');
            });

            // === LINE Quota Management ===
            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'line_monthly_quota', function ($table) {
                $table->unsignedInteger('line_monthly_quota')->default(500)->after('page_mention');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'line_used_this_month', function ($table) {
                $table->unsignedInteger('line_used_this_month')->default(0)->after('line_monthly_quota');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'line_quota_warning_threshold', function ($table) {
                $table->unsignedInteger('line_quota_warning_threshold')->default(50)->after('line_used_this_month');
            });

            $this->safeAddColumn($table, 'fortune_horoscope_campaigns', 'line_quota_reset_at', function ($table) {
                $table->timestamp('line_quota_reset_at')->nullable()->after('line_quota_warning_threshold');
            });
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_horoscope_campaigns', [
            'enable_auto_hashtags',
            'custom_hashtags',
            'enable_cta',
            'cta_text',
            'enable_engagement_hooks',
            'page_name',
            'page_mention',
            'line_monthly_quota',
            'line_used_this_month',
            'line_quota_warning_threshold',
            'line_quota_reset_at',
        ]);
    }
};
