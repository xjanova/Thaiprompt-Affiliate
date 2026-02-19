<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ fortune_brand_name สำหรับตั้งชื่อแบรนด์ดูดวง
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_brand_name')) {
                $table->string('fortune_brand_name')->nullable()->default('แม่หมอจันทรา')->after('line_welcome_image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'fortune_brand_name')) {
                $table->dropColumn('fortune_brand_name');
            }
        });
    }
};
