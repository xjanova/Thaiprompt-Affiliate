<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ points, is_automatic, requirements ในตาราง forum_trophies
     *
     * คอลัมน์เหล่านี้ถูกใช้ในหน้า admin แต่ยังไม่มีในตาราง:
     * - points: คะแนนที่ผู้ใช้จะได้รับเมื่อได้รับถ้วยรางวัล
     * - is_automatic: มอบรางวัลอัตโนมัติเมื่อผู้ใช้ทำตามเงื่อนไข
     * - requirements: เงื่อนไขอัตโนมัติ (JSON) เช่น min_threads, min_posts
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('forum_trophies', function (Blueprint $table) {
            $this->safeAddColumn($table, 'forum_trophies', 'points', function ($table) {
                $table->integer('points')->default(0)->after('is_active');
            });

            $this->safeAddColumn($table, 'forum_trophies', 'is_automatic', function ($table) {
                $table->boolean('is_automatic')->default(false)->after('points');
            });

            $this->safeAddColumn($table, 'forum_trophies', 'requirements', function ($table) {
                $table->json('requirements')->nullable()->after('is_automatic');
            });
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        $this->safeDropColumn('forum_trophies', ['points', 'is_automatic', 'requirements']);
    }
};
