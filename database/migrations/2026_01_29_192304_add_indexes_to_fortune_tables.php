<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม indexes สำหรับเพิ่มประสิทธิภาพ query
     *
     * Indexes ที่เพิ่ม:
     * - fortune_readings: facebook_user_id, user_id, created_at, is_paid
     * - fortune_categories: slug, is_active
     * - Composite indexes สำหรับ common queries
     *
     * @return void
     */
    public function up(): void
    {
        // ===== fortune_readings =====
        Schema::table('fortune_readings', function (Blueprint $table) {
            // Single column indexes
            if (!$this->indexExists('fortune_readings', 'fortune_readings_facebook_user_id_index')) {
                $table->index('facebook_user_id', 'fortune_readings_facebook_user_id_index');
            }

            if (!$this->indexExists('fortune_readings', 'fortune_readings_user_id_index')) {
                $table->index('user_id', 'fortune_readings_user_id_index');
            }

            if (!$this->indexExists('fortune_readings', 'fortune_readings_ai_provider_index')) {
                $table->index('ai_provider', 'fortune_readings_ai_provider_index');
            }

            if (!$this->indexExists('fortune_readings', 'fortune_readings_is_paid_index')) {
                $table->index('is_paid', 'fortune_readings_is_paid_index');
            }

            if (!$this->indexExists('fortune_readings', 'fortune_readings_created_at_index')) {
                $table->index('created_at', 'fortune_readings_created_at_index');
            }

            // Composite indexes สำหรับ common queries
            // Query: ดึงการทำนายของ user ในวันนี้
            if (!$this->indexExists('fortune_readings', 'fortune_readings_fb_user_created_idx')) {
                $table->index(['facebook_user_id', 'created_at'], 'fortune_readings_fb_user_created_idx');
            }

            // Query: ดึงการทำนายแบบฟรี/จ่ายเงิน
            if (!$this->indexExists('fortune_readings', 'fortune_readings_paid_created_idx')) {
                $table->index(['is_paid', 'created_at'], 'fortune_readings_paid_created_idx');
            }

            // Query: สถิติการใช้งานแต่ละ provider
            if (!$this->indexExists('fortune_readings', 'fortune_readings_provider_created_idx')) {
                $table->index(['ai_provider', 'created_at'], 'fortune_readings_provider_created_idx');
            }
        });

        // ===== fortune_categories =====
        Schema::table('fortune_categories', function (Blueprint $table) {
            // Index สำหรับ slug (ใช้ search หมวดหมู่)
            if (!$this->indexExists('fortune_categories', 'fortune_categories_slug_unique')) {
                $table->unique('slug', 'fortune_categories_slug_unique');
            }

            // Index สำหรับ is_active (filter เฉพาะที่ active)
            if (!$this->indexExists('fortune_categories', 'fortune_categories_is_active_index')) {
                $table->index('is_active', 'fortune_categories_is_active_index');
            }

            // Composite index: active + order (สำหรับ list หมวดหมู่)
            if (!$this->indexExists('fortune_categories', 'fortune_categories_active_order_idx')) {
                $table->index(['is_active', 'order'], 'fortune_categories_active_order_idx');
            }
        });

        // เพิ่ม index ให้ user_id ใน fortune_readings (ถ้ายังไม่มี)
        if (Schema::hasColumn('fortune_readings', 'user_id')) {
            DB::statement('ALTER TABLE fortune_readings ADD INDEX IF NOT EXISTS idx_user_date (user_id, created_at)');
        }
    }

    /**
     * ลบ indexes
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            // Drop indexes
            $indexes = [
                'fortune_readings_facebook_user_id_index',
                'fortune_readings_user_id_index',
                'fortune_readings_ai_provider_index',
                'fortune_readings_is_paid_index',
                'fortune_readings_created_at_index',
                'fortune_readings_fb_user_created_idx',
                'fortune_readings_paid_created_idx',
                'fortune_readings_provider_created_idx',
            ];

            foreach ($indexes as $index) {
                if ($this->indexExists('fortune_readings', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('fortune_categories', function (Blueprint $table) {
            $indexes = [
                'fortune_categories_slug_unique',
                'fortune_categories_is_active_index',
                'fortune_categories_active_order_idx',
            ];

            foreach ($indexes as $index) {
                if ($this->indexExists('fortune_categories', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    /**
     * ตรวจสอบว่า index มีอยู่แล้วหรือไม่
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = ?
             AND table_name = ?
             AND index_name = ?",
            [$database, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
