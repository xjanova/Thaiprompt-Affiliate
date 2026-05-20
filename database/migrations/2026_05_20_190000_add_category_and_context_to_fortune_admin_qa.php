<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม category + context columns ใน fortune_admin_qa
 *
 * เป้าหมาย:
 *   1. category — แบ่งประเภทคำถาม-คำตอบให้ AI ค้นถูกบริบทเร็วขึ้น (pre-filter ก่อน cosine)
 *   2. page_id — รู้ว่าเพจไหนตอบ (ถ้ามีหลายเพจ)
 *   3. reading_id / reading_type — ผูกกับ FortuneReading เดิม รู้ว่าคุยตอนแพคเกจไหน
 *
 * รายการ category (derive จาก state machine ตอน capture):
 *   - pre_purchase, birthdate_help, question_input, pre_payment,
 *     payment_confirm, post_reading, celtic_picking, general
 *
 * Pre-filter retrieve flow:
 *   FortuneAIService → classify ลูกค้าปัจจุบัน → AdminQARetriever filter category
 *   → cosine loop เฉพาะ rows ที่ category ตรง = เร็วขึ้น 5-10x ตอนข้อมูลโต
 *
 * ⚠️ ALTER TABLE — ใช้ Schema::hasColumn() เช็ค ห้าม Schema::hasTable() + return
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fortune_admin_qa', function (Blueprint $table) {
            // 🏷 หมวดของคู่ Q&A — derive จาก state machine ตอน capture
            if (! Schema::hasColumn('fortune_admin_qa', 'category')) {
                $table->string('category', 32)->nullable()->after('a_text');
            }

            // 📄 page_id ของเพจที่ตอบ (FB sender.id ใน echo event = Page ID)
            if (! Schema::hasColumn('fortune_admin_qa', 'page_id')) {
                $table->string('page_id', 64)->nullable()->after('source_user_id');
            }

            // 🔗 ผูกกับ reading ที่ลูกค้ามี ณ ขณะ admin ตอบ
            //    nullable เพราะลูกค้าอาจไม่มี reading active (chitchat ทั่วไป)
            if (! Schema::hasColumn('fortune_admin_qa', 'reading_id')) {
                $table->unsignedBigInteger('reading_id')->nullable()->after('page_id');
            }

            // 🎫 ประเภทแพคเกจตอนนั้น (basic, deep, celtic_cross, free_card)
            if (! Schema::hasColumn('fortune_admin_qa', 'reading_type')) {
                $table->string('reading_type', 32)->nullable()->after('reading_id');
            }
        });

        // 🔍 Indexes — เพิ่มแยกเพื่อ safe re-run
        Schema::table('fortune_admin_qa', function (Blueprint $table) {
            // index category อย่างเดียว สำหรับ filter retrieve
            if (! $this->indexExists('fortune_admin_qa', 'fortune_admin_qa_category_index')) {
                $table->index('category', 'fortune_admin_qa_category_index');
            }

            // composite index สำหรับ retrieve hot path: WHERE is_active=1 AND category=?
            if (! $this->indexExists('fortune_admin_qa', 'admin_qa_active_cat_idx')) {
                $table->index(['is_active', 'category'], 'admin_qa_active_cat_idx');
            }

            // reading_id index (debug/analytics)
            if (! $this->indexExists('fortune_admin_qa', 'fortune_admin_qa_reading_id_index')) {
                $table->index('reading_id', 'fortune_admin_qa_reading_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_admin_qa', function (Blueprint $table) {
            // ลบ indexes ก่อน columns
            if ($this->indexExists('fortune_admin_qa', 'admin_qa_active_cat_idx')) {
                $table->dropIndex('admin_qa_active_cat_idx');
            }
            if ($this->indexExists('fortune_admin_qa', 'fortune_admin_qa_category_index')) {
                $table->dropIndex('fortune_admin_qa_category_index');
            }
            if ($this->indexExists('fortune_admin_qa', 'fortune_admin_qa_reading_id_index')) {
                $table->dropIndex('fortune_admin_qa_reading_id_index');
            }

            foreach (['category', 'page_id', 'reading_id', 'reading_type'] as $col) {
                if (Schema::hasColumn('fortune_admin_qa', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * เช็คว่ามี index หรือยัง (ป้องกัน re-run ล้มเหลว)
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            $result = $connection->select(
                'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                [$database, $table, $indexName]
            );

            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
