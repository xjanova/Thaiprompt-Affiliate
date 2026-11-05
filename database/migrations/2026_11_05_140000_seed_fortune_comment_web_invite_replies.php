<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌐 (2026-11-05) คลังขอบคุณ + ชวนดูดวงที่เว็บ จันทรา.online 100 แบบ (หมวด web_invite)
     *
     * USER SPEC: ในระบบคอมเมนต์ เพิ่มลิงก์เชิญมาดูดวงที่เว็บ จันทรา.online ทำให้ไม่ผิดกฎเฟซบุ๊ก 100 ข้อความ
     * ใส่ผ่าน migration เหมือนหมวด appreciation (deploy.sh รัน migrate ให้อัตโนมัติ)
     *
     * ข้อความอยู่ใน database/data/fortune_comment_web_invite_replies.txt (บรรทัดละ 1 แบบ)
     * ทุกแบบ: ขอบคุณก่อนแล้วค่อยชวน · {web_link} จุดเดียว · ไม่ขอให้กดไลก์/แชร์/แท็ก
     *         ไม่เร่งเร้า ไม่บอกราคา ไม่อ้าง "ฟรี" (ตัวคุมความถี่อยู่ที่ ProcessCommentEngagement)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_comment_replies')) {
            return;
        }

        $file = database_path('data/fortune_comment_web_invite_replies.txt');
        if (! is_file($file)) {
            return;
        }

        $lines = array_values(array_unique(array_filter(
            array_map('trim', file($file, FILE_IGNORE_NEW_LINES)),
            fn ($line) => $line !== '' && str_contains($line, '{web_link}')
        )));

        $existing = DB::table('fortune_comment_replies')
            ->where('category', 'web_invite')
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
                'category' => 'web_invite',
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
            DB::table('fortune_comment_replies')->where('category', 'web_invite')->delete();
        }
    }
};
