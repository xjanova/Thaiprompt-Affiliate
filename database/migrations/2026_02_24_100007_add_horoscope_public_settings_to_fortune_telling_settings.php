<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ตั้งค่าระบบดูดวงสาธารณะ ในตาราง fortune_telling_settings
     *
     * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return
     * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // เปิด/ปิด ระบบดูดวงสาธารณะ
            if (! Schema::hasColumn('fortune_telling_settings', 'horoscope_public_enabled')) {
                $table->boolean('horoscope_public_enabled')
                    ->default(true)
                    ->after('is_active')
                    ->comment('เปิด/ปิดระบบดูดวงสาธารณะ');
            }

            // จำกัดการใช้งานฟรีต่อวัน
            if (! Schema::hasColumn('fortune_telling_settings', 'horoscope_free_daily_limit')) {
                $table->unsignedSmallInteger('horoscope_free_daily_limit')
                    ->default(5)
                    ->after('horoscope_public_enabled')
                    ->comment('จำนวนครั้งดูดวงฟรีต่อวัน');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'horoscope_dream_free_limit')) {
                $table->unsignedSmallInteger('horoscope_dream_free_limit')
                    ->default(3)
                    ->after('horoscope_free_daily_limit')
                    ->comment('จำนวนครั้งทำนายฝันฟรีต่อวัน');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'horoscope_numerology_free_limit')) {
                $table->unsignedSmallInteger('horoscope_numerology_free_limit')
                    ->default(3)
                    ->after('horoscope_dream_free_limit')
                    ->comment('จำนวนครั้งเลขศาสตร์ฟรีต่อวัน');
            }

            // SEO settings
            if (! Schema::hasColumn('fortune_telling_settings', 'horoscope_seo_title_th')) {
                $table->string('horoscope_seo_title_th', 255)
                    ->nullable()
                    ->after('horoscope_numerology_free_limit')
                    ->comment('SEO title ภาษาไทย');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'horoscope_seo_description_th')) {
                $table->text('horoscope_seo_description_th')
                    ->nullable()
                    ->after('horoscope_seo_title_th')
                    ->comment('SEO description ภาษาไทย');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'horoscope_public_enabled',
                'horoscope_free_daily_limit',
                'horoscope_dream_free_limit',
                'horoscope_numerology_free_limit',
                'horoscope_seo_title_th',
                'horoscope_seo_description_th',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
