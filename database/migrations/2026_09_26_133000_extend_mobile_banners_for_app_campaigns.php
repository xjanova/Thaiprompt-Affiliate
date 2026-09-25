<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แบนเนอร์แคมเปญในแอป (2026-09-26 เปิดตัวตลาดสด/ไรเดอร์/ร้านค้า)
 *
 * ต่อยอดตาราง mobile_banners เดิม (แอปเรียก /api/v1/mobile/banners อยู่แล้ว) แทนการสร้างตารางใหม่
 * — ตาราง app_banners มีอยู่แล้วแต่เป็นแบนเนอร์ "ประกาศฉุกเฉิน" คนละระบบ จึงไม่ใช้ชื่อนั้น
 *
 * คอลัมน์เดิม: title, image, link, link_type, link_target, position (= placement), sort_order,
 *              is_active, start_date (= starts_at), end_date (= ends_at), view_count (= impressions), click_count
 * คอลัมน์ใหม่:
 *   - subtitle       ข้อความรอง (รูปไม่มีตัวหนังสือ แอป/เว็บวางข้อความทับเอง)
 *   - cta_label      ข้อความบนปุ่ม เช่น "สั่งเลย"
 *   - cta_type       screen (เปิดหน้าจอในแอป) | url (เปิดลิงก์เว็บ) | null (ไม่มีปุ่ม)
 *   - cta_value      ชื่อหน้าจอ เช่น taladsod / ลิงก์ เช่น /taladsod/start/seller
 *   - audience       all | buyer | rider | merchant
 *   - campaign_key   รหัสแคมเปญ (ไม่ซ้ำ) ใช้ให้ data migration ใส่แบนเนอร์เปิดตัวแบบไม่ซ้ำ
 *   - created_by / updated_by  แอดมินที่สร้าง/แก้ล่าสุด
 *
 * position เป็น varchar(20) อยู่แล้ว (migration 2025_12_21) → ใส่ taladsod/rider/merchant ได้เลยไม่ต้องแก้ enum
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('mobile_banners')) {
            return;
        }

        Schema::table('mobile_banners', function (Blueprint $table) {
            $this->safeAddColumn($table, 'mobile_banners', 'subtitle', function (Blueprint $table) {
                $table->string('subtitle', 255)->nullable()->after('title')->comment('ข้อความรองบนแบนเนอร์');
            });
            $this->safeAddColumn($table, 'mobile_banners', 'cta_label', function (Blueprint $table) {
                $table->string('cta_label', 60)->nullable()->after('image')->comment('ข้อความบนปุ่ม');
            });
            $this->safeAddColumn($table, 'mobile_banners', 'cta_type', function (Blueprint $table) {
                $table->string('cta_type', 20)->nullable()->after('cta_label')->comment('screen | url | null');
            });
            $this->safeAddColumn($table, 'mobile_banners', 'cta_value', function (Blueprint $table) {
                $table->string('cta_value', 500)->nullable()->after('cta_type')->comment('ชื่อหน้าจอแอป หรือ ลิงก์');
            });
            $this->safeAddColumn($table, 'mobile_banners', 'audience', function (Blueprint $table) {
                $table->string('audience', 20)->default('all')->after('position')->comment('all | buyer | rider | merchant');
            });
            $this->safeAddColumn($table, 'mobile_banners', 'campaign_key', function (Blueprint $table) {
                $table->string('campaign_key', 64)->nullable()->after('audience')->comment('รหัสแคมเปญ (ไม่ซ้ำ)');
            });
            $this->safeAddColumn($table, 'mobile_banners', 'created_by', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('click_count');
            });
            $this->safeAddColumn($table, 'mobile_banners', 'updated_by', function (Blueprint $table) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            });
        });

        $this->safeAddIndex('mobile_banners', 'campaign_key', 'mobile_banners_campaign_key_unique', 'unique');
        $this->safeAddIndex('mobile_banners', ['position', 'is_active', 'sort_order'], 'mobile_banners_placement_active_sort_idx');
    }

    public function down(): void
    {
        if (! Schema::hasTable('mobile_banners')) {
            return;
        }

        $this->safeDropIndex('mobile_banners', 'mobile_banners_placement_active_sort_idx');
        $this->safeDropIndex('mobile_banners', 'mobile_banners_campaign_key_unique');
        $this->safeDropColumn('mobile_banners', [
            'subtitle', 'cta_label', 'cta_type', 'cta_value', 'audience', 'campaign_key', 'created_by', 'updated_by',
        ]);
    }
};
