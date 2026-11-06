<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🙏 (2026-11-05) คลังคำขอบคุณที่ร่วมแสดงความเห็น 200 แบบ (หมวด appreciation)
     *
     * USER SPEC: ตอบคอมเมนต์ทุกคน ไม่จำกัดครั้ง ด้วยคำขอบคุณที่หลากหลาย
     * ใส่ผ่าน migration (ไม่ใช่ seeder) เพราะ deploy.sh รัน migrate ให้อัตโนมัติ
     * ⇒ ขึ้น prod แล้วคลังพร้อมใช้ทันที ไม่ต้องมีคนตามไปรัน db:seed
     *
     * ข้อความอยู่ใน database/data/fortune_comment_appreciation_replies.txt (บรรทัดละ 1 แบบ)
     * ทุกแบบ: ไม่มี {name} · ไม่ขอให้กดไลก์/แชร์/แท็ก/พิมพ์คำ (engagement bait ของ Meta)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_comment_replies')) {
            return;
        }

        $file = database_path('data/fortune_comment_appreciation_replies.txt');
        if (! is_file($file)) {
            return;
        }

        $lines = array_values(array_unique(array_filter(
            array_map('trim', file($file, FILE_IGNORE_NEW_LINES)),
            fn ($line) => $line !== ''
        )));

        $existing = DB::table('fortune_comment_replies')
            ->where('category', 'appreciation')
            ->pluck('message')
            ->all();

        $now = now();
        $rows = [];
        foreach ($lines as $i => $message) {
            if (in_array($message, $existing, true)) {
                continue;
            }
            $rows[] = [
                'message' => $message,
                'category' => 'appreciation',
                'locale' => 'th',
                'is_active' => true,
                'sort_order' => $i + 1,
                'use_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('fortune_comment_replies')->insert($chunk);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fortune_comment_replies')) {
            DB::table('fortune_comment_replies')->where('category', 'appreciation')->delete();
        }
    }
};
