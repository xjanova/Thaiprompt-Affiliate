<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มฟีลด์สำหรับฟีเจอร์ Reset Template
     *
     * เพิ่มคอลัมน์:
     * - is_default: ระบุว่าเป็น template ต้นฉบับจาก seeder หรือไม่
     * - original_template_key: template key ต้นฉบับ (สำหรับ duplicate)
     * - category: หมวดหมู่ของ template
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('line_signup_templates', function (Blueprint $table) {
            // ✅ ใช้ SafeMigration trait เพื่อความปลอดภัย

            // เพิ่มคอลัมน์ is_default - ระบุว่าเป็น template ต้นฉบับหรือไม่
            $this->safeAddColumn($table, 'line_signup_templates', 'is_default', function($table) {
                $table->boolean('is_default')
                    ->default(false)
                    ->after('is_active')
                    ->comment('Template ต้นฉบับจาก seeder (สามารถ reset ได้)');
            });

            // เพิ่มคอลัมน์ original_template_key - สำหรับ template ที่ duplicate มา
            $this->safeAddColumn($table, 'line_signup_templates', 'original_template_key', function($table) {
                $table->string('original_template_key')
                    ->nullable()
                    ->after('is_default')
                    ->comment('Template key ต้นฉบับที่ถูก duplicate มา');
            });

            // เพิ่มคอลัมน์ category - หมวดหมู่ของ template
            $this->safeAddColumn($table, 'line_signup_templates', 'category', function($table) {
                $table->string('category')
                    ->nullable()
                    ->after('original_template_key')
                    ->comment('หมวดหมู่ เช่น welcome, earning, success_story, training, guide');
            });
        });

        // เพิ่ม indexes
        $this->safeAddIndex('line_signup_templates', 'is_default');
        $this->safeAddIndex('line_signup_templates', 'category');
    }

    /**
     * ลบฟีลด์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        // ลบ indexes ก่อน
        $this->safeDropIndex('line_signup_templates', 'line_signup_templates_is_default_index');
        $this->safeDropIndex('line_signup_templates', 'line_signup_templates_category_index');

        // ลบคอลัมน์
        $this->safeDropColumn('line_signup_templates', [
            'is_default',
            'original_template_key',
            'category',
        ]);
    }
};
