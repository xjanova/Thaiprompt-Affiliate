<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🕊️ (2026-11-05) คลังคำตอบตามโทนคอมเมนต์
     *
     * - letting_go (100 แบบ): คอมเมนต์อโหสิกรรม / ไม่สน / ไม่แคร์ / ไม่ให้ค่า / เจ็บใจ — โทนสงบ ให้กำลังใจ
     * - neutral (40 แบบ): คอมเมนต์ตำหนิเพจ — โทนกลางสุภาพ ไม่แก้ตัว ไม่ขาย
     *
     * เหตุ: คอมเมนต์ราว 1 ใน 4 เป็นแนวปล่อยวาง แต่เคยได้คำขอบคุณร่าเริง ("เห็นแล้วใจฟูเลยค่ะ")
     * ข้อความอยู่ใน database/data/fortune_comment_{category}_replies.txt (บรรทัดละ 1 แบบ) · idempotent
     */
    protected const CATEGORIES = ['letting_go', 'neutral'];

    public function up(): void
    {
        if (! Schema::hasTable('fortune_comment_replies')) {
            return;
        }

        foreach (self::CATEGORIES as $category) {
            $file = database_path("data/fortune_comment_{$category}_replies.txt");
            if (! is_file($file)) {
                continue;
            }

            $lines = array_values(array_unique(array_filter(
                array_map('trim', file($file, FILE_IGNORE_NEW_LINES)),
                fn ($line) => $line !== ''
            )));

            $existing = DB::table('fortune_comment_replies')
                ->where('category', $category)
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
                    'category' => $category,
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
    }

    public function down(): void
    {
        if (Schema::hasTable('fortune_comment_replies')) {
            DB::table('fortune_comment_replies')->whereIn('category', self::CATEGORIES)->delete();
        }
    }
};
