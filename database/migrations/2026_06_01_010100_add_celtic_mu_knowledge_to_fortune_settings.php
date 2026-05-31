<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🧠 (2026-06-01) เพิ่ม enable_celtic_mu_knowledge toggle
     *
     * เปิด/ปิดการ inject องค์ความรู้สายมู (ฮวงจุ้ย/เจ้าที่/องค์เทพ/ไสยศาสตร์)
     *   จากคลัง RAG เข้า prompt Celtic 99 เมื่อลูกค้าถามหัวข้อนั้นๆ
     *
     * default = true. admin ปิดได้ผ่าน:
     *   UPDATE fortune_telling_settings SET enable_celtic_mu_knowledge = 0
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_mu_knowledge')) {
                $table->boolean('enable_celtic_mu_knowledge')
                    ->default(true)
                    ->after('enable_celtic_health_tome');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_mu_knowledge')) {
                $table->dropColumn('enable_celtic_mu_knowledge');
            }
        });
    }
};
