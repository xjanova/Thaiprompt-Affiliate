<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    use SafeMigration;

    /** เก็บหนึ่งแถวต่อข้อความ/การเข้าจากลิงก์ กัน webhook ส่งซ้ำ */
    public function up(): void
    {
        $this->safeCreateTable('fortune_story_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 64)->unique();
            $table->unsignedBigInteger('fortune_page_id')->nullable()->index();
            $table->string('facebook_page_id', 191)->nullable();
            $table->string('facebook_user_id', 191)->index();
            $table->string('source', 20)->default('story');
            $table->string('event_type', 20);
            $table->string('story_id', 191)->nullable()->index();
            $table->text('story_url')->nullable();
            $table->string('story_slug', 191)->nullable()->index();
            $table->timestamp('inbound_at')->nullable();
            $table->timestamp('welcomed_at')->nullable();
            $table->timestamps();
        });
    }

    /** ลบเฉพาะตารางติดตามสตอรี่เมื่อย้อน migration */
    public function down(): void
    {
        $this->safeDropTable('fortune_story_events');
    }
};
