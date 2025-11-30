<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_chat_widget_settings
     * ตารางการตั้งค่า LINE Chat Widget
     */
    public function up(): void
    {
        // Skip if table already exists (from SQL import)
        if (Schema::hasTable('line_chat_widget_settings')) {
            echo "Table line_chat_widget_settings already exists, skipping...\n";
            return;
        }

        Schema::create('line_chat_widget_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Support Chat');
            $table->enum('position', ['bottom-right', 'bottom-left', 'top-right', 'top-left'])->default('bottom-right');
            $table->string('primary_color')->default('#06C755')->comment('สี LINE เขียว');
            $table->string('secondary_color')->default('#FFFFFF');
            $table->string('welcome_message')->default('สวัสดีค่ะ! มีอะไรให้ช่วยไหมคะ?');
            // ⚠️ ใช้ unsignedBigInteger แทน foreignId()->constrained()
            // เพราะ line_avatars ถูกสร้างใน migration 100008 (หลัง 100007)
            $table->unsignedBigInteger('avatar_id')->nullable();
            $table->boolean('show_on_pages')->default(true);
            $table->json('enabled_pages')->nullable()->comment('หน้าที่เปิดใช้: ["home", "products", "all"]');
            $table->boolean('enable_sound')->default(true);
            $table->boolean('enable_notifications')->default(true);
            $table->boolean('auto_open')->default(false);
            $table->integer('auto_open_delay')->default(3000)->comment('milliseconds');
            $table->text('offline_message')->nullable();
            $table->boolean('enable_user_chat')->default(true)->comment('แชทกับผู้ใช้');
            $table->boolean('enable_seller_chat')->default(true)->comment('แชทกับผู้ขาย');
            $table->boolean('enable_ai_chat')->default(true)->comment('แชทกับ AI Bot');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // เพิ่ม foreign key ถ้า line_avatars มีอยู่แล้ว
        if (Schema::hasTable('line_avatars')) {
            Schema::table('line_chat_widget_settings', function (Blueprint $table) {
                $table->foreign('avatar_id')
                    ->references('id')
                    ->on('line_avatars')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_chat_widget_settings');
    }
};
