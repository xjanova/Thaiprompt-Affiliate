<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง keyword_activity_logs
     *
     * สำหรับบันทึก keyword usage และ analytics
     */
    public function up(): void
    {
        if (Schema::hasTable('keyword_activity_logs')) {
            return;
        }

        Schema::create('keyword_activity_logs', function (Blueprint $table) {
            $table->id();

            // Keyword reference
            $table->unsignedBigInteger('keyword_id')->nullable()->index();
            $table->string('keyword_name')->nullable();

            // User info
            $table->string('line_user_id')->index();

            // Message & context
            $table->text('user_message');
            $table->string('response_type')->nullable();
            $table->string('category')->nullable();
            $table->integer('priority')->nullable();

            // Action type
            $table->enum('action_type', ['matched', 'no_match', 'tested', 'error'])->default('matched');

            // Timestamps
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['line_user_id', 'created_at']);
            $table->index(['keyword_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_activity_logs');
    }
};
