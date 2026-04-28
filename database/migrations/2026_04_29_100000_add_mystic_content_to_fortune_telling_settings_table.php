<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์การตั้งค่า "คอนเทนต์สายมู" + toggle ระบบดวงรายวันเกิดเดิม
     *
     * - daily_horoscope_per_day_enabled: เปิด/ปิดระบบดวงตามวันเกิด (ตี 1-7)
     *   ปิดเป็น default หลัง deploy เพื่อให้ระบบใหม่ทำงานแทน
     * - mystic_content_enabled: เปิด/ปิดระบบโพสคอนเทนต์สายมู/แก้เคล็ด/สิ่งลี้ลับ
     * - mystic_content_schedule: เวลาโพสในรูป JSON ["08:00", "20:00"] (timezone Asia/Bangkok)
     * - mystic_content_caption_min/max: ความยาว caption (default 400-700 ตัวอักษร)
     * - mystic_content_hashtag_count: จำนวน hashtag/โพส (default 6)
     * - tavily_api_key: Tavily Search API key (free 1000/เดือน)
     * - brave_search_api_key: Brave Search API key (fallback ฟรี 2000/เดือน)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // ── Toggle ระบบดวงรายวันเกิด (เดิม)
            if (! Schema::hasColumn('fortune_telling_settings', 'daily_horoscope_per_day_enabled')) {
                $table->boolean('daily_horoscope_per_day_enabled')
                    ->default(false)
                    ->after('discovery_chat_max_turns')
                    ->comment('เปิด/ปิดโพสดวงตามวันเกิด ตี 1-7 (default ปิด เพราะใช้ระบบสายมูแทน)');
            }

            // ── ระบบคอนเทนต์สายมู
            if (! Schema::hasColumn('fortune_telling_settings', 'mystic_content_enabled')) {
                $table->boolean('mystic_content_enabled')
                    ->default(false)
                    ->after('daily_horoscope_per_day_enabled')
                    ->comment('เปิด/ปิดโพสคอนเทนต์สายมู/แก้เคล็ด/สิ่งลี้ลับ');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'mystic_content_schedule')) {
                $table->json('mystic_content_schedule')
                    ->nullable()
                    ->after('mystic_content_enabled')
                    ->comment('เวลาโพส JSON: ["08:00", "20:00"] (Asia/Bangkok)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'mystic_content_caption_min')) {
                $table->unsignedSmallInteger('mystic_content_caption_min')
                    ->default(400)
                    ->after('mystic_content_schedule')
                    ->comment('ความยาว caption ขั้นต่ำ (ตัวอักษร)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'mystic_content_caption_max')) {
                $table->unsignedSmallInteger('mystic_content_caption_max')
                    ->default(700)
                    ->after('mystic_content_caption_min')
                    ->comment('ความยาว caption สูงสุด (ตัวอักษร)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'mystic_content_hashtag_count')) {
                $table->unsignedTinyInteger('mystic_content_hashtag_count')
                    ->default(6)
                    ->after('mystic_content_caption_max')
                    ->comment('จำนวน hashtag/โพส (≤7 กัน reach drop)');
            }

            // ── Web Search API keys
            if (! Schema::hasColumn('fortune_telling_settings', 'tavily_api_key')) {
                $table->string('tavily_api_key', 500)
                    ->nullable()
                    ->after('mystic_content_hashtag_count')
                    ->comment('Tavily Search API key (primary, free 1000/เดือน)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'brave_search_api_key')) {
                $table->string('brave_search_api_key', 500)
                    ->nullable()
                    ->after('tavily_api_key')
                    ->comment('Brave Search API key (fallback, free 2000/เดือน)');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'daily_horoscope_per_day_enabled',
                'mystic_content_enabled',
                'mystic_content_schedule',
                'mystic_content_caption_min',
                'mystic_content_caption_max',
                'mystic_content_hashtag_count',
                'tavily_api_key',
                'brave_search_api_key',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
