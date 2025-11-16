<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง site_settings สำหรับเก็บการตั้งค่าเว็บไซต์
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('site_settings')) {
            return;
        }

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // ชื่อเว็บไซต์
            $table->string('site_name')->default('TP-Affiliate');
            $table->text('site_description')->nullable();

            // โลโก้ (แปลงเป็น WebP อัตโนมัติ)
            $table->string('logo')->nullable(); // เก็บ path: site-settings/logo_xxxxx.webp
            $table->string('logo_dark')->nullable(); // โลโก้สำหรับ dark mode
            $table->boolean('logo_spin')->default(false); // ให้โลโก้หมุนหรือไม่

            // Favicon (แปลงเป็น WebP อัตโนมัติ)
            $table->string('favicon')->nullable(); // เก็บ path: site-settings/favicon_xxxxx.webp

            // SEO Settings
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            // สี Theme (optional - สำหรับ custom branding)
            $table->string('primary_color')->default('#8B5CF6'); // Purple
            $table->string('secondary_color')->default('#EC4899'); // Pink

            // ข้อมูลติดต่อ
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('contact_address')->nullable();

            // Social Media Links
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('line_url')->nullable();
            $table->string('youtube_url')->nullable();

            // Analytics & Tracking
            $table->text('google_analytics_id')->nullable();
            $table->text('facebook_pixel_id')->nullable();
            $table->text('google_tag_manager_id')->nullable();

            // Maintenance Mode
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();

            // Custom Scripts
            $table->text('header_scripts')->nullable(); // Scripts ที่ต้องการใส่ใน <head>
            $table->text('footer_scripts')->nullable(); // Scripts ที่ต้องการใส่ก่อน </body>

            $table->timestamps();
        });

        // สร้างข้อมูลเริ่มต้น
        DB::table('site_settings')->insert([
            'site_name' => 'TP-Affiliate',
            'site_description' => 'ระบบ Affiliate Marketing ที่ทรงพลังที่สุด',
            'logo_spin' => false,
            'primary_color' => '#8B5CF6',
            'secondary_color' => '#EC4899',
            'maintenance_mode' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * ลบตาราง site_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
