<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // เพิ่มคอลัมน์ในตาราง settings
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $table->boolean('comment_engagement_enabled')->default(false)->after('respond_in_comment');
            $table->string('comment_engagement_mode', 20)->default('ai')->after('comment_engagement_enabled'); // 'ai' or 'template'
            $table->text('comment_reply_template')->nullable()->after('comment_engagement_mode');
            $table->text('comment_dm_template')->nullable()->after('comment_reply_template');
            $table->text('comment_engagement_prompt')->nullable()->after('comment_dm_template');
        });

        // สร้างตาราง tracking การ engage
        Schema::create('fortune_comment_engagements', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_user_id', 100)->index();
            $table->string('facebook_post_id', 100)->index();
            $table->string('facebook_comment_id', 100);
            $table->text('comment_text')->nullable();
            $table->text('comment_reply')->nullable();
            $table->text('dm_message')->nullable();
            $table->json('user_profile')->nullable();
            $table->timestamp('engaged_at')->nullable();
            $table->timestamps();

            // ป้องกัน engage ซ้ำคนเดิมในโพสต์เดิม
            $table->unique(['facebook_user_id', 'facebook_post_id'], 'unique_user_post_engagement');
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $table->dropColumn([
                'comment_engagement_enabled',
                'comment_engagement_mode',
                'comment_reply_template',
                'comment_dm_template',
                'comment_engagement_prompt',
            ]);
        });

        Schema::dropIfExists('fortune_comment_engagements');
    }
};
