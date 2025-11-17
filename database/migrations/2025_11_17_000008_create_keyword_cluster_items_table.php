<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง keyword_cluster_items เป็น junction table
     * เพื่อเชื่อมระหว่าง keywords กับ clusters
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('keyword_cluster_items')) {
            return;
        }

        Schema::create('keyword_cluster_items', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('keyword_cluster_id')
                ->constrained('keyword_clusters')
                ->onDelete('cascade');

            $table->foreignId('line_bot_keyword_id')
                ->constrained('line_bot_keywords')
                ->onDelete('cascade');

            // Relationship metadata
            $table->enum('relationship_type', [
                'PRIMARY',      // คำสำคัญหลักของกลุ่ม
                'SYNONYM',      // คำพ้องความหมาย
                'RELATED',      // เกี่ยวข้อง
                'VARIANT',      // รูปแบบอื่น
                'SIMILAR'       // คล้ายกัน
            ])->default('RELATED')->index();

            $table->float('relevance_score')->default(1.0); // ความเกี่ยวข้อง (0-1)
            $table->integer('co_occurrence_count')->default(0); // จำนวนครั้งที่ปรากฏพร้อมกัน

            // Metadata
            $table->json('context_data')->nullable(); // ข้อมูลบริบท
            $table->text('notes')->nullable(); // หมายเหตุ

            // Timestamps
            $table->timestamps();

            // Unique constraint
            $table->unique(['keyword_cluster_id', 'line_bot_keyword_id'], 'cluster_keyword_unique');

            // Indexes
            $table->index(['keyword_cluster_id', 'relationship_type']);
        });
    }

    /**
     * ลบตาราง keyword_cluster_items
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_cluster_items');
    }
};
