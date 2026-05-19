<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม settings สำหรับ RAG Admin Q&A ใน fortune_telling_settings
 *
 * Fields:
 *   - admin_qa_capture_enabled: เก็บ admin Q&A จาก FB echo (default true)
 *   - admin_qa_rag_enabled: ใช้ RAG inject ใน chat (default true)
 *   - admin_qa_rag_threshold: cosine threshold (default 0.78)
 *   - admin_qa_rag_top_k: จำนวน few-shot examples (default 3)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return!
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'admin_qa_capture_enabled')) {
                $table->boolean('admin_qa_capture_enabled')->default(true)->after('admin_handover_enabled');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'admin_qa_rag_enabled')) {
                $table->boolean('admin_qa_rag_enabled')->default(true)->after('admin_qa_capture_enabled');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'admin_qa_rag_threshold')) {
                $table->decimal('admin_qa_rag_threshold', 3, 2)->default(0.78)->after('admin_qa_rag_enabled');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'admin_qa_rag_top_k')) {
                $table->unsignedTinyInteger('admin_qa_rag_top_k')->default(3)->after('admin_qa_rag_threshold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'admin_qa_capture_enabled',
                'admin_qa_rag_enabled',
                'admin_qa_rag_threshold',
                'admin_qa_rag_top_k',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
