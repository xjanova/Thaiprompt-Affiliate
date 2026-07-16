<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ skip_authorization ใน oauth_clients (Passport)
     *
     * ใช้ข้ามหน้า consent เฉพาะ client ที่เชื่อถือ (juntraweb SSO) — ดู
     * App\Models\Passport\Client::skipsAuthorization()
     *
     * timestamp 2026_* เรียงหลัง migration สร้างตาราง oauth_clients ของ Passport
     * (2016_*) ใน migrate --force รอบเดียวกัน + guard hasTable/hasColumn กัน
     * ลำดับ/รันซ้ำ (idempotent)
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('oauth_clients')) {
            return;
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            if (! Schema::hasColumn('oauth_clients', 'skip_authorization')) {
                $table->boolean('skip_authorization')->default(false)->after('revoked');
            }
        });
    }

    /**
     * ลบคอลัมน์
     *
     * @return void
     */
    public function down(): void
    {
        if (! Schema::hasTable('oauth_clients')) {
            return;
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            if (Schema::hasColumn('oauth_clients', 'skip_authorization')) {
                $table->dropColumn('skip_authorization');
            }
        });
    }
};
