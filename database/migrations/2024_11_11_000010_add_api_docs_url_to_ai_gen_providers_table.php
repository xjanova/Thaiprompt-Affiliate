<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ api_docs_url ในตาราง ai_gen_providers
     * สำหรับเก็บลิ้งค์ไปหน้าสมัคร/จัดการ API key ของแต่ละ provider
     */
    public function up(): void
    {
        Schema::table('ai_gen_providers', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_gen_providers', 'api_docs_url')) {
                $table->string('api_docs_url')->nullable()->after('logo_url');
            }
        });
    }

    /**
     * ลบคอลัมน์ api_docs_url
     */
    public function down(): void
    {
        Schema::table('ai_gen_providers', function (Blueprint $table) {
            if (Schema::hasColumn('ai_gen_providers', 'api_docs_url')) {
                $table->dropColumn('api_docs_url');
            }
        });
    }
};
