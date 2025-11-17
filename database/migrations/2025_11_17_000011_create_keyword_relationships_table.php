<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง keyword_relationships สำหรับบันทึก relationship ระหว่าง keywords
     *
     * ค้นหาและจัดเก็บความสัมพันธ์ระหว่าง keywords
     * ใช้สำหรับ auto-discovery ของ related keywords
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('keyword_relationships')) {
            return;
        }

        Schema::create('keyword_relationships', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('source_keyword_id')
                ->constrained('line_bot_keywords')
                ->onDelete('cascade'); // Origin keyword

            $table->foreignId('related_keyword_id')
                ->constrained('line_bot_keywords')
                ->onDelete('cascade'); // Related keyword

            // Relationship strength
            $table->float('strength_score')->default(0.5); // 0-1, how strong relationship is
            $table->integer('co_occurrence_frequency')->default(0); // Times mentioned together
            $table->integer('shared_user_count')->default(0); // Users mentioning both

            // Relationship type
            $table->enum('relationship_type', [
                'SYNONYM',              // Same meaning
                'ANTONYM',              // Opposite meaning
                'CAUSE_EFFECT',         // One causes the other
                'PART_WHOLE',           // Part of or contains
                'SEQUENTIAL',           // One follows the other
                'CONTEXTUAL',           // Similar context
                'CATEGORY',             // Same category
                'ALTERNATIVE'           // Alternative solution
            ])->index();

            // Discovery method
            $table->enum('discovery_method', [
                'CO_OCCURRENCE',        // Found by co-occurrence
                'ENTITY_MATCH',         // Shared entities
                'INTENT_MATCH',         // Shared intent
                'CLUSTER_MATCH',        // In same cluster
                'SEMANTIC',             // Semantic analysis
                'MANUAL'                // Manually added
            ])->default('CO_OCCURRENCE');

            // Additional data
            $table->json('supporting_messages')->nullable(); // Message IDs showing relationship
            $table->json('contextual_info')->nullable(); // Context where relationship appears
            $table->integer('days_tracked')->default(0); // How many days tracked
            $table->float('confidence')->default(0.5); // Confidence in relationship

            // Status
            $table->boolean('is_verified')->default(false); // Manually verified
            $table->boolean('is_active')->default(true); // Active relationship
            $table->integer('usage_count')->default(0); // Times this relationship helped

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['source_keyword_id', 'strength_score']);
            $table->index(['relationship_type', 'is_active']);
            $table->unique(['source_keyword_id', 'related_keyword_id'], 'unique_keyword_pair');
        });
    }

    /**
     * ลบตาราง keyword_relationships
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_relationships');
    }
};
