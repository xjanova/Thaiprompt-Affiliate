<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔁 (2026-11-07) รีโพสอัตโนมัติจากเพจหลักลงเพจสาขา
 *
 * เจ้าของสั่ง: "ทำระบบรีโพสจากเพจหลักแบบอัตโนมัติ ใช้ตารางเวลาที่เหลื่อมกัน 30 นาที
 *              ใช้คอนเทนต์จากเพจหลัก โดยไม่ซ้ำกับเพจหลักในแต่ละวัน
 *              ทำปุ่มเปิดปิดไว้หลังบ้าน และรองรับการขยายสาขาในอนาคต"
 *
 * 2 ส่วน:
 *   1. คอลัมน์ตั้งค่าบน fortune_pages — 1 สาขา = 1 ชุดตั้งค่า (เพิ่มสาขาใหม่ไม่ต้องแก้โค้ด)
 *   2. ตาราง fortune_page_reposts — สมุดบันทึกว่าโพสต้นฉบับไหนถูกเอาไปลงสาขาไหนแล้ว
 *      unique (สาขา, โพสต้นฉบับ) = กันโพสซ้ำในระดับฐานข้อมูล ไม่ใช่แค่ในโค้ด
 *
 * ⚠️ repost_enabled default = false (opt-in) — เหมือน auto_post_enabled
 *    เพิ่ม migration แล้วต้องไม่มีเพจไหนเริ่มโพสเองจนกว่าแอดมินกดเปิด
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_pages')) {
            // ⚠️ ALTER TABLE — เช็คทีละคอลัมน์ ห้าม hasTable() + return
            Schema::table('fortune_pages', function (Blueprint $table) {
                if (! Schema::hasColumn('fortune_pages', 'repost_enabled')) {
                    $table->boolean('repost_enabled')->default(false)->after('auto_post_enabled');
                }

                if (! Schema::hasColumn('fortune_pages', 'repost_source_page_id')) {
                    // null = ใช้สาขาหลัก (is_default) ของ facebook เป็นต้นทาง
                    $table->unsignedBigInteger('repost_source_page_id')->nullable()->after('repost_enabled');
                }

                if (! Schema::hasColumn('fortune_pages', 'repost_offset_minutes')) {
                    $table->unsignedSmallInteger('repost_offset_minutes')->default(30)->after('repost_source_page_id');
                }

                if (! Schema::hasColumn('fortune_pages', 'repost_max_per_day')) {
                    // 0 = ไม่จำกัด (ตามจำนวนโพสของเพจต้นทางวันนั้น)
                    $table->unsignedSmallInteger('repost_max_per_day')->default(0)->after('repost_offset_minutes');
                }

                if (! Schema::hasColumn('fortune_pages', 'repost_settings')) {
                    // ค่าละเอียด: pick_order / caption_suffix / exclude_keywords / media_types / lookback_days / lookahead_hours
                    $table->json('repost_settings')->nullable()->after('repost_max_per_day');
                }
            });
        }

        if (! Schema::hasTable('fortune_page_reposts')) {
            Schema::create('fortune_page_reposts', function (Blueprint $table) {
                $table->id();

                // สาขาปลายทาง (เพจที่รับโพส)
                $table->foreignId('fortune_page_id')
                    ->constrained('fortune_pages')
                    ->cascadeOnDelete();

                // สาขาต้นทาง (ไม่ใส่ FK — ลบ/เปลี่ยนต้นทางแล้วประวัติต้องยังอยู่)
                $table->unsignedBigInteger('source_fortune_page_id')->nullable();

                $table->string('source_post_id', 64);           // โพสต้นฉบับ (pageid_postid)
                $table->string('source_object_id', 64)->nullable(); // photo id / video id ของต้นฉบับ
                $table->timestamp('source_created_at')->nullable();
                $table->string('media_type', 16);               // photo / video / text

                // เวลาที่ตั้งให้ขึ้นบนเพจสาขา (= เวลาโพสของต้นทางในช่องนั้น + offset)
                $table->dateTime('slot_at');
                // โพสของต้นทางที่เป็น "ช่องเวลา" ให้รอบนี้ (ไว้โชว์ในหลังบ้านว่าเหลื่อมจากโพสไหน)
                $table->string('slot_source_post_id', 64)->nullable();

                $table->string('target_post_id', 64)->nullable();
                $table->string('target_object_id', 64)->nullable();

                // scheduled = ตั้งเวลาใน Facebook แล้ว · published = ขึ้นหน้าเพจแล้ว
                // failed = ส่งไม่ผ่าน (ช่องเวลาจะถูกเติมด้วยโพสอื่นในรอบถัดไป) · cancelled = แอดมินยกเลิก
                $table->string('status', 16)->default('scheduled');
                $table->text('error')->nullable();
                $table->string('caption_preview', 255)->nullable();
                $table->timestamp('checked_at')->nullable();

                $table->timestamps();

                // 🔒 1 โพสต้นฉบับลงสาขาเดียวกันได้ครั้งเดียวตลอดกาล
                $table->unique(['fortune_page_id', 'source_post_id'], 'fpr_page_source_unique');
                $table->index(['fortune_page_id', 'slot_at'], 'fpr_page_slot_idx');
                $table->index(['status', 'slot_at'], 'fpr_status_slot_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_page_reposts');

        if (! Schema::hasTable('fortune_pages')) {
            return;
        }

        $columns = array_values(array_filter(
            ['repost_enabled', 'repost_source_page_id', 'repost_offset_minutes', 'repost_max_per_day', 'repost_settings'],
            fn ($c) => Schema::hasColumn('fortune_pages', $c)
        ));

        if ($columns !== []) {
            Schema::table('fortune_pages', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
