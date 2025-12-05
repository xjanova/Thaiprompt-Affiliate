<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * แก้ไข Schema Drift สำหรับตาราง LINE
     *
     * สร้างตารางที่ขาดหายไป:
     * - line_avatars
     * - line_flex_message_templates
     * - line_chat_widget_settings
     *
     * @return void
     */
    public function up(): void
    {
        // 1. สร้างตาราง line_avatars ก่อน (เพราะ line_chat_widget_settings มี FK ไปหา)
        if (!Schema::hasTable('line_avatars')) {
            Schema::create('line_avatars', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['image', 'gif', 'lottie', 'video'])->default('image');
                $table->string('file_path')->comment('path ของไฟล์');
                $table->string('file_url')->nullable()->comment('URL สาธารณะ');
                $table->integer('file_size')->nullable()->comment('ขนาดไฟล์ bytes');
                $table->string('source_type')->nullable()->comment('upload, url, line, default');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });

            echo "✓ Created table: line_avatars\n";
        } else {
            echo "→ Table line_avatars already exists, skipping...\n";
        }

        // 2. สร้างตาราง line_flex_message_templates
        if (!Schema::hasTable('line_flex_message_templates')) {
            Schema::create('line_flex_message_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('ชื่อเทมเพลต');
                $table->string('category')->default('general')->comment('หมวดหมู่: promotion, info, product, etc.');
                $table->text('description')->nullable();
                $table->string('thumbnail')->nullable()->comment('รูปตัวอย่าง');
                $table->json('flex_content')->comment('JSON ของ Flex Message');
                $table->boolean('is_seed')->default(false)->comment('เป็นตัวอย่างจากระบบ');
                $table->boolean('is_active')->default(true);
                $table->integer('usage_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['category', 'is_active']);
            });

            echo "✓ Created table: line_flex_message_templates\n";
        } else {
            echo "→ Table line_flex_message_templates already exists, skipping...\n";
        }

        // 3. สร้างตาราง line_chat_widget_settings
        if (!Schema::hasTable('line_chat_widget_settings')) {
            Schema::create('line_chat_widget_settings', function (Blueprint $table) {
                $table->id();
                $table->string('name')->default('Support Chat');
                $table->enum('position', ['bottom-right', 'bottom-left', 'top-right', 'top-left'])->default('bottom-right');
                $table->string('primary_color')->default('#06C755')->comment('สี LINE เขียว');
                $table->string('secondary_color')->default('#FFFFFF');
                $table->string('welcome_message')->default('สวัสดีค่ะ! มีอะไรให้ช่วยไหมคะ?');
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

            // เพิ่ม foreign key หลังจากสร้างตาราง
            Schema::table('line_chat_widget_settings', function (Blueprint $table) {
                $table->foreign('avatar_id')
                    ->references('id')
                    ->on('line_avatars')
                    ->onDelete('set null');
            });

            echo "✓ Created table: line_chat_widget_settings\n";
        } else {
            echo "→ Table line_chat_widget_settings already exists, skipping...\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ลบตามลำดับ FK (ลบ line_chat_widget_settings ก่อน)
        Schema::dropIfExists('line_chat_widget_settings');
        Schema::dropIfExists('line_flex_message_templates');
        Schema::dropIfExists('line_avatars');
    }
};
