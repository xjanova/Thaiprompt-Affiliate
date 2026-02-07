<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_marketing_campaigns
     * ระบบแคมเปญการตลาดดูดวงอัตโนมัติ
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_marketing_campaigns')) {
            return;
        }

        Schema::create('fortune_marketing_campaigns', function (Blueprint $table) {
            $table->id();

            // ข้อมูลแคมเปญ
            $table->string('name');
            $table->string('topic')->nullable();
            $table->text('description')->nullable();

            // ข้อความ
            $table->text('message_template')->nullable();
            $table->text('ai_generated_message')->nullable();
            $table->text('ai_prompt')->nullable();
            $table->boolean('use_ai_generate')->default(true);

            // เป้าหมาย
            $table->string('target_audience')->default('all');
            $table->integer('target_limit')->nullable();
            $table->string('target_platform')->default('all');
            $table->json('target_filters')->nullable();

            // กำหนดเวลา
            $table->string('schedule_type')->default('once');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('recurring_cron')->nullable();
            $table->dateTime('recurring_until')->nullable();
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->nullable();

            // สถานะ
            $table->string('status')->default('draft');
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('total_target')->default(0);
            $table->text('last_error')->nullable();

            // ผู้สร้าง
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('scheduled_at');
            $table->index('next_run_at');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_marketing_campaigns');
    }
};
