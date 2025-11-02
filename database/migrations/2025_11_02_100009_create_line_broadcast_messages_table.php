<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('line_broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อแคมเปญ');
            $table->enum('message_type', ['text', 'flex', 'image', 'video'])->default('text');
            $table->text('content')->nullable()->comment('เนื้อหาข้อความ');
            $table->foreignId('flex_template_id')->nullable()->constrained('line_flex_message_templates')->nullOnDelete();
            $table->json('message_data')->nullable()->comment('ข้อมูลข้อความ JSON');
            $table->enum('target_type', ['all', 'users', 'sellers', 'custom'])->default('all');
            $table->json('target_users')->nullable()->comment('array ของ user IDs');
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_broadcast_messages');
    }
};
