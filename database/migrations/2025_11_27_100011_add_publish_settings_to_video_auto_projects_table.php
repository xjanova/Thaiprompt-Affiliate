<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สำหรับการตั้งค่าการโพสต์และ thumbnail
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง video_auto_projects มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('video_auto_projects')) {
            return;
        }

        Schema::table('video_auto_projects', function (Blueprint $table) {
            // Thumbnail - ใช้สำหรับแสดงว่าเป็นเพลง/วีดีโออะไร
            if (! Schema::hasColumn('video_auto_projects', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('video_thumbnail')
                    ->comment('Path ของ thumbnail ที่สร้างขึ้น');
            }

            if (! Schema::hasColumn('video_auto_projects', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('thumbnail_path')
                    ->comment('URL ของ thumbnail');
            }

            // File Cleanup Settings
            if (! Schema::hasColumn('video_auto_projects', 'delete_source_after_publish')) {
                $table->boolean('delete_source_after_publish')->default(true)->after('thumbnail_url')
                    ->comment('ลบไฟล์ต้นฉบับหลังโพสต์สำเร็จ');
            }

            if (! Schema::hasColumn('video_auto_projects', 'source_files_deleted')) {
                $table->boolean('source_files_deleted')->default(false)->after('delete_source_after_publish')
                    ->comment('ไฟล์ต้นฉบับถูกลบแล้ว');
            }

            if (! Schema::hasColumn('video_auto_projects', 'source_deleted_at')) {
                $table->timestamp('source_deleted_at')->nullable()->after('source_files_deleted')
                    ->comment('เวลาที่ลบไฟล์ต้นฉบับ');
            }

            // Publish Counter
            if (! Schema::hasColumn('video_auto_projects', 'publish_count')) {
                $table->unsignedInteger('publish_count')->default(0)->after('source_deleted_at')
                    ->comment('จำนวนครั้งที่โพสต์สำเร็จ');
            }

            if (! Schema::hasColumn('video_auto_projects', 'last_published_at')) {
                $table->timestamp('last_published_at')->nullable()->after('publish_count')
                    ->comment('เวลาโพสต์ล่าสุด');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('video_auto_projects', function (Blueprint $table) {
            $columns = [
                'thumbnail_path',
                'thumbnail_url',
                'delete_source_after_publish',
                'source_files_deleted',
                'source_deleted_at',
                'publish_count',
                'last_published_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('video_auto_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
